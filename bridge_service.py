import requests
import time
from rich.console import Console
from datetime import datetime

# ==========================================
# CONFIGURATION
# ==========================================
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY"

CI3_BASE_URL = "https://schoolsfls443dr4rsm53m.shihaab.tech"
CI3_TOKEN = "3e8ea952f2a06672"

POLL_INTERVAL = 5
console = Console()

def get_supabase_headers():
    return {
        "apikey": SUPABASE_KEY,
        "Authorization": f"Bearer {SUPABASE_KEY}",
        "Content-Type": "application/json",
        "Prefer": "return=representation"
    }

def get_ci3_headers():
    return {
        "Authorization": CI3_TOKEN,
        "Content-Type": "application/json"
    }

# ==========================================
# 1. DOWNSTREAM: CI3 -> SUPABASE
# ==========================================
def fetch_ci3_messages():
    """Fetch pending messages from CI3 Queue."""
    try:
        url = f"{CI3_BASE_URL}/messages/contacts"
        resp = requests.get(url, headers=get_ci3_headers(), timeout=10)
        if resp.status_code == 200:
            msgs = resp.json()
            # Filter: Only process those NOT 'sent' or 'failed' (Double check)
            pending = [m for m in msgs if m.get('sent_status') not in ['sent', 'failed']]
            return pending
        else:
            console.print(f"[red]CI3 Fetch Error: {resp.status_code}[/red]")
            return []
    except Exception as e:
        console.print(f"[red]CI3 Network Error: {e}[/red]")
        return []

def insert_into_supabase(ci3_msg):
    """Insert message into Supabase and return the Recipient ID."""
    
    # 1. Extract Data
    phone = ci3_msg.get('phone')
    text = ci3_msg.get('message', '').replace("<br />", "\n")
    ci3_id = str(ci3_msg.get('id'))
    
    if not phone or not text:
        return None

    console.print(f"   [cyan]Processing Msg: {ci3_id} -> {phone}[/cyan]")

    # 0. Check for Duplicates (Idempotency)
    try:
        dup_check_url = f"{SUPABASE_URL}/rest/v1/message_recipients?ci3_id=eq.{ci3_id}&select=id"
        dup_resp = requests.get(dup_check_url, headers=get_supabase_headers())
        if dup_resp.status_code == 200 and len(dup_resp.json()) > 0:
             console.print(f"   [yellow]⚠️ Skipped Duplicate (CI3 ID: {ci3_id})[/yellow]")
             return None
    except Exception as e:
        console.print(f"   [red]Error checking duplicates: {e}[/red]")
        return None

    # 2. Normalize Phone
    if not phone.startswith('+'):
        phone = f"+{phone}"

    # 3. Smart Type Detection (Infer from Content)
    lower_text = text.lower()
    msg_type = "general"
    msg_title = "School Notice"
    
    # Keywords map (Somali)
    if any(k in lower_text for k in ['maqan', 'absent', 'ha joogo']):
        msg_type = "absence"
        msg_title = "Absence Alert"
    elif any(k in lower_text for k in ['imtixaan', 'exam', 'natiijo', 'result']):
        msg_type = "exam"
        msg_title = "Exam Result"
    elif any(k in lower_text for k in ['lacag', 'fee', 'biil']):
        msg_type = "finance"
        msg_title = "Fee Notice"
    elif any(k in lower_text for k in ['fasax', 'holiday']):
        msg_type = "notice"
        msg_title = "Holiday Announcement"

    # 4. Create Message Content (Table: messages)
    msg_payload = {
        "school_id": 1, 
        "type": msg_type, # <-- Smart Type
        "title": msg_title, # <-- Smart Title
        "body": text,
        "created_at": datetime.utcnow().isoformat()
    }
    
    # Check if this exact message content was recently added to avoid duplication?
    # For now, we rely on the flow consuming the CI3 queue locally.
    
    url_msg = f"{SUPABASE_URL}/rest/v1/messages"
    resp_msg = requests.post(url_msg, headers=get_supabase_headers(), json=msg_payload)
    
    if resp_msg.status_code != 201:
        console.print(f"   [red]Failed to insert message body: {resp_msg.text}[/red]")
        return None
        
    new_msg_id = resp_msg.json()[0]['id']
    
    # 4. Create Recipient Link (Table: message_recipients)
    # We store the ci3_id here
    rcpt_payload = {
        "message_id": new_msg_id,
        "parent_phone": phone,
        "status": "pending",
        "ci3_id": ci3_id,           # <--- LINK TO CI3
        "is_synced_to_ci3": False   # Needs sync once status changes
    }
    
    url_rcpt = f"{SUPABASE_URL}/rest/v1/message_recipients"
    resp_rcpt = requests.post(url_rcpt, headers=get_supabase_headers(), json=rcpt_payload)
    
    if resp_rcpt.status_code == 201:
        recip_id = resp_rcpt.json()[0]['id']
        console.print(f"   [green]✅ Inserted into Supabase (ID: {recip_id})[/green]")
        return recip_id
    elif resp_rcpt.status_code == 409 or "23503" in resp_rcpt.text:
         # Foreign Key Violation: Parent not in allowed_parents
         console.print(f"   [yellow]⚠️ Skipped: Parent {phone} not in 'allowed_parents' table.[/yellow]")
         # We should probably update CI3 to say 'failed' or 'invalid' here so it doesn't retry forever?
         # For logic flow, we return None, but maybe we should flag CI3.
         update_ci3_status(ci3_id, 'invalid')
         return None
    else:
        console.print(f"   [red]Failed to insert recipient: {resp_rcpt.text}[/red]")
        return None

def wait_for_delivery(recipient_id):
    """Block until Supabase status changes from 'pending' (Sequential Delivery)."""
    console.print("   [dim]⏳ Waiting for Dispatcher to send... (Sequential Mode)[/dim]")
    
    for _ in range(20): # Wait up to 20 seconds (Dispatcher runs every 5s)
        time.sleep(1)
        try:
            url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{recipient_id}&select=status"
            resp = requests.get(url, headers=get_supabase_headers())
            if resp.status_code == 200:
                data = resp.json()
                if data and data[0]['status'] != 'pending':
                    new_status = data[0]['status']
                    console.print(f"   [bold green]🚀 Delivered! Status: {new_status}[/bold green]")
                    return new_status
        except:
            pass
            
    console.print("   [yellow]⚠️ Timeout matching Dispatcher. Moving on...[/yellow]")
    return 'pending' # Give up waiting, but don't fail the whole loop

# ==========================================
# 2. UPSTREAM: SUPABASE -> CI3
# ==========================================
def update_ci3_status(ci3_id, status):
    """Call CI3 API to update status."""
    url = f"{CI3_BASE_URL}/messages/update_status"
    # Map Supabase status to CI3 status
    # Map Supabase status to CI3 status
    # Default to 'sent' because CI3 Dashboard shows 'Unknown' for 'read'/'seen'
    # User can override this via .env if CI3 is updated to support 'read'
    target_read_status = "sent" 
    
    ci3_status = status
    if status == 'seen':
        ci3_status = target_read_status

    payload = {
        "contact_id": ci3_id,
        "sent_status": ci3_status 
    }
    try:
        resp = requests.post(url, headers=get_ci3_headers(), json=payload, timeout=10)
        if resp.status_code != 200:
             console.print(f"[red]CI3 Update Failed: {resp.text}[/red]")
    except Exception as e:
        console.print(f"[red]CI3 Update Network Error: {e}[/red]")

def sync_status_changes():
    """Find local changes not yet synced to CI3."""
    # We look for items that have a CI3 ID but are NOT marked as synced
    # NOTE: This requires the Trigger to set is_synced_to_ci3 = False on updates
    url = f"{SUPABASE_URL}/rest/v1/message_recipients?is_synced_to_ci3=eq.false&ci3_id=not.is.null&select=id,status,ci3_id"
    
    try:
        resp = requests.get(url, headers=get_supabase_headers())
        if resp.status_code == 200:
            items = resp.json()
            if items:
                console.print(f"[bold magenta]🔄 Syncing {len(items)} status updates to CI3...[/bold magenta]")
                
            for item in items:
                # 1. Update CI3
                update_ci3_status(item['ci3_id'], item['status'])
                
                # 2. Mark as Synced Locally
                patch_url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{item['id']}"
                requests.patch(patch_url, headers=get_supabase_headers(), json={"is_synced_to_ci3": True})
                
    except Exception as e:
        console.print(f"[red]Sync Error: {e}[/red]")


# ==========================================
# MAIN LOOP
# ==========================================
def run_bridge():
    console.print("[bold purple]🌉 Sanabil Production Bridge Started[/bold purple]")
    console.print("[dim]Syncing CI3 <--> Supabase...[/dim]")

    while True:
        # 1. Downstream (Sequential Import)
        # We manually check CI3 queue
        pending_ci3 = fetch_ci3_messages()
        
        if pending_ci3:
            console.print(f"[bold]📥 Found {len(pending_ci3)} new messages from CI3[/bold]")
            for msg in pending_ci3:
                recip_id = insert_into_supabase(msg)
                
                if recip_id:
                    # BLOCKING WAIT for Dispatcher (Sequential Requirement)
                    final_status = wait_for_delivery(recip_id)
                    
                    # Immediate Upstream Sync for 'Sent'
                    update_ci3_status(msg['id'], final_status)
                    
                    # Mark as synced so the background sync doesn't redo it
                    patch_url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{recip_id}"
                    requests.patch(patch_url, headers=get_supabase_headers(), json={"is_synced_to_ci3": True})

        # 2. Upstream (Background Sync for 'Seen')
        # This catches when a user reads a message later
        sync_status_changes()
        
        time.sleep(POLL_INTERVAL)

if __name__ == "__main__":
    run_bridge()
