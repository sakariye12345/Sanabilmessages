import requests
import time
import json
from rich.console import Console
from datetime import datetime

# ==========================================
# CONFIGURATION
# ==========================================
# We use the same keys as before (assuming they are still valid)
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY"
TEST_PHONE = "+252634370911" # The phone number we saw in the schema mock data

console = Console()

def get_headers():
    return {
        "apikey": SUPABASE_KEY,
        "Authorization": f"Bearer {SUPABASE_KEY}",
        "Content-Type": "application/json",
        "Prefer": "return=representation"
    }

def verify_webhook_setup():
    console.print("[bold cyan]🚀 Verifying Cloud Webhook & Function Setup[/bold cyan]")
    
    # 1. Create a Test Message
    console.print("\n[yellow]Step 1: Inserting Test Message...[/yellow]")
    msg_payload = {
        "school_id": 1,
        "type": "notice",
        "title": "Cloud Verify",
        "body": f"This is a test message to verify the Cloud Webhook at {datetime.now().strftime('%H:%M:%S')}",
        "created_at": datetime.utcnow().isoformat()
    }
    
    try:
        url_msg = f"{SUPABASE_URL}/rest/v1/messages"
        resp_msg = requests.post(url_msg, headers=get_headers(), json=msg_payload)
        
        if resp_msg.status_code != 201:
            console.print(f"[red]❌ Failed to insert message. Status: {resp_msg.status_code}[/red]")
            console.print(f"Response: {resp_msg.text}")
            return
            
        new_msg = resp_msg.json()[0]
        msg_id = new_msg['id']
        console.print(f"[green]✅ Message inserted (ID: {msg_id})[/green]")
        
    except Exception as e:
        console.print(f"[red]❌ Error inserting message: {e}[/red]")
        return

    # 2. Insert Recipient (Trigger the Webhook)
    console.print("\n[yellow]Step 2: Inserting Recipient (Should trigger Webhook)...[/yellow]")
    rcpt_payload = {
        "message_id": msg_id,
        "parent_phone": TEST_PHONE,
        "status": "pending", # <--- This should change if Webhook works
        "is_synced_to_ci3": False
    }
    
    try:
        url_rcpt = f"{SUPABASE_URL}/rest/v1/message_recipients"
        resp_rcpt = requests.post(url_rcpt, headers=get_headers(), json=rcpt_payload)
        
        if resp_rcpt.status_code != 201:
            console.print(f"[red]❌ Failed to insert recipient. Status: {resp_rcpt.status_code}[/red]")
            console.print(f"Response: {resp_rcpt.text}")
            return
            
        rcpt = resp_rcpt.json()[0]
        rcpt_id = rcpt['id']
        console.print(f"[green]✅ Recipient inserted (ID: {rcpt_id}) with status 'pending'[/green]")
        
    except Exception as e:
        console.print(f"[red]❌ Error inserting recipient: {e}[/red]")
        return

    # 3. Watch for Status Change
    console.print("\n[yellow]Step 3: Watching for Status Change (Timeout: 15s)...[/yellow]")
    console.print("[dim]If the Webhook is working, 'pending' will change to 'sent' or 'failed' automatically.[/dim]")
    
    start_time = time.time()
    final_status = 'pending'
    
    while time.time() - start_time < 15:
        try:
            url_check = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{rcpt_id}&select=status,error"
            resp_check = requests.get(url_check, headers=get_headers())
            
            if resp_check.status_code == 200:
                data = resp_check.json()
                if data:
                    current_status = data[0]['status']
                    error_msg = data[0].get('error')
                    
                    if current_status != 'pending':
                        final_status = current_status
                        console.print(f"\n[bold green]🎉 SUCCESS! Status changed to: '{final_status}'[/bold green]")
                        if error_msg:
                            console.print(f"[red]Note: There was an error reported: {error_msg}[/red]")
                            console.print("[dim](This means the function ran, but maybe Push failed? That is still a success for the Webhook setup!)[/dim]")
                        break
            
            time.sleep(1)
            console.print(".", end="", end_flush=True)
            
        except Exception as e:
            pass
            
    if final_status == 'pending':
        console.print("\n[bold red]❌ TIMEOUT: Status remained 'pending'.[/bold red]")
        console.print("Possible Causes:")
        console.print("1. Webhook is not configured in Supabase Dashboard.")
        console.print("2. Edge Function is not deployed.")
        console.print("3. Database Triggers are disabled.")
    else:
        console.print(f"\n[bold blue]Verification Complete.[/bold blue]")

if __name__ == "__main__":
    verify_webhook_setup()
