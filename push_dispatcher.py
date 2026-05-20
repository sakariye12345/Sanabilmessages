import requests
import time
import json
from rich.console import Console

# ==========================================
# CONFIGURATION
# ==========================================
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY"
EXPO_PUSH_API = "https://exp.host/--/api/v2/push/send"

POLL_INTERVAL = 5 # Seconds

console = Console()

def get_headers():
    return {
        "apikey": SUPABASE_KEY,
        "Authorization": f"Bearer {SUPABASE_KEY}",
        "Content-Type": "application/json"
    }

def fetch_pending_messages():
    """Fetch all recipients who haven't received the message yet."""
    url = f"{SUPABASE_URL}/rest/v1/message_recipients?status=eq.pending&select=*,messages(*)"
    try:
        resp = requests.get(url, headers=get_headers())
        if resp.status_code == 200:
            return resp.json()
        else:
            console.print(f"[red]DB Error: {resp.text}[/red]")
            return []
    except Exception as e:
        console.print(f"[red]Network Error: {e}[/red]")
        return []

def get_user_tokens(phone):
    """Get all active FCM/Expo tokens for a user."""
    # Note: URL encoding + is %2B
    encoded_phone = phone.replace("+", "%2B")
    url = f"{SUPABASE_URL}/rest/v1/user_devices?user_phone=eq.{encoded_phone}&is_active=eq.true&select=fcm_token"
    try:
        resp = requests.get(url, headers=get_headers())
        if resp.status_code == 200:
            return [r['fcm_token'] for r in resp.json()]
        return []
    except:
        return []

def mark_as_sent(recipient_id):
    """Update status to 'sent'."""
    url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{recipient_id}"
    url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{recipient_id}"
    resp = requests.patch(url, headers=get_headers(), json={"status": "sent", "sent_at": "now()"})
    if resp.status_code not in [200, 204]:
        console.print(f"[red]DB Update Failed: {resp.status_code} - {resp.text}[/red]")

def mark_as_failed(recipient_id, error):
    """Update status to 'failed'."""
    url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{recipient_id}"
    requests.patch(url, headers=get_headers(), json={"status": "failed", "error": str(error)})

def send_push_notification(tokens, message_data):
    """Send to Expo Push API."""
    if not tokens: return False

    payload = []
    for token in tokens:
        if not token: continue
        
        # SIMULATION BYPASS for Expo Go
        if "SIMULATED" in token:
            console.print(f"   [bold yellow]🔔 SIMULATION: Push sent to {token}[/bold yellow]")
            # We pretend it worked
            continue

        payload.append({
            "to": token,
            "title": message_data['title'],
            "body": message_data['body'],
            "data": {"message_id": message_data['id'], "type": message_data['type']},
            "sound": "default"
        })

    # If all tokens were simulated, return success immediately
    if not payload:
        return True

    try:
        resp = requests.post(EXPO_PUSH_API, json=payload)
        console.print(f"   [dim]Expo Response: {resp.status_code}[/dim]")
        return resp.status_code == 200
    except Exception as e:
        console.print(f"   [red]Push Failed: {e}[/red]")
        return False

def run_dispatcher():
    console.print("[bold cyan]🚀 Sanabil Push Dispatcher Started[/bold cyan]")
    console.print(f"[dim]Polling {SUPABASE_URL} every {POLL_INTERVAL}s...[/dim]")

    while True:
        pending = fetch_pending_messages()
        
        if pending:
            console.print(f"[bold green]Found {len(pending)} pending messages![/bold green]")
            
            for item in pending:
                recipient_id = item['id']
                phone = item['parent_phone']
                message = item['messages'] # Joined data
                
                if not message:
                    console.print(f"[yellow]Skipping {recipient_id}: No message data linked[/yellow]")
                    continue

                console.print(f"Processing: {message['title']} -> {phone}")
                
                # 1. Get Tokens
                tokens = get_user_tokens(phone)
                if not tokens:
                    console.print(f"   [yellow]No devices found for {phone}. Marking as FAILED.[/yellow]")
                    mark_as_failed(recipient_id, "No active devices") 
                    # CRITICAL: This unblocks the Bridge waiting loop
                    continue

                # 2. Send Push
                success = send_push_notification(tokens, message)
                
                # 3. Update Status
                if success:
                    mark_as_sent(recipient_id)
                    console.print(f"   [green]✅ Sent successfully[/green]")
                else:
                    mark_as_failed(recipient_id, "Expo API Failed")
                    console.print(f"   [red]❌ Send failed[/red]")
        
        time.sleep(POLL_INTERVAL)

if __name__ == "__main__":
    try:
        run_dispatcher()
    except KeyboardInterrupt:
        console.print("[bold red]Dispatcher Stopped[/bold red]")
