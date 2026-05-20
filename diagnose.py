import requests
import json
from rich.console import Console

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY"
PHONE = "+252634370911"

console = Console()

def get_headers():
    return {
        "apikey": SUPABASE_KEY,
        "Authorization": f"Bearer {SUPABASE_KEY}",
        "Content-Type": "application/json"
    }

def check_deployment():
    console.print(f"[bold cyan]🔍 Diagnosing {PHONE}...[/bold cyan]")

    # 1. Check Devices
    # Note: URL encoding + is %2B
    encoded_phone = PHONE.replace("+", "%2B")
    url = f"{SUPABASE_URL}/rest/v1/user_devices?user_phone=eq.{encoded_phone}&select=*"
    resp = requests.get(url, headers=get_headers())
    devices = resp.json()
    
    if devices:
        console.print(f"[green]✅ Found {len(devices)} device(s):[/green]")
        for d in devices:
            console.print(f"   - {d['platform']} | Token: {d['fcm_token'][:20]}... | Active: {d['is_active']}")
    else:
        console.print(f"[red]❌ No devices found! The APK did not register a token.[/red]")

    # 2. Check Messages
    url = f"{SUPABASE_URL}/rest/v1/message_recipients?parent_phone=eq.{encoded_phone}&select=*,messages(*)"
    resp = requests.get(url, headers=get_headers())
    msgs = resp.json()

    if msgs:
        console.print(f"[green]✅ Found {len(msgs)} message(s):[/green]")
        for m in msgs:
            console.print(f"   - ID: {m['id']} | Status: {m['status']} | Title: {m['messages']['title']}")
    else:
        console.print(f"[red]❌ No messages found in DB for this phone.[/red]")

    # 3. Create a NEW Test Message (Force Push)
    if devices:
        console.print("\n[bold yellow]⚡ Creating a NEW Test Message to trigger Push...[/bold yellow]")
        # Insert message
        url_msg = f"{SUPABASE_URL}/rest/v1/messages"
        msg_payload = {
            "title": "APK Verification",
            "body": "This is a real push notification test.",
            "type": "general",
            "school_id": 1
        }
        r1 = requests.post(url_msg, headers=get_headers(), json=msg_payload, params={"select": "*"})
        # Supabase returns 201 Created but maybe not the body if Prefer header isn't set, 
        # but let's assume we can query it or it returns. 
        # Actually simplest is to just rely on the script assuming it worked if 201.
        
        # We need the ID to link it.
        # Let's adjust to use 'return=representation' header
        headers = get_headers()
        headers['Prefer'] = 'return=representation'
        r1 = requests.post(url_msg, headers=headers, json=msg_payload)
        
        if r1.status_code == 201:
            new_msg = r1.json()[0]
            console.print(f"   Created Message ID: {new_msg['id']}")
            
            # Link to parent
            url_link = f"{SUPABASE_URL}/rest/v1/message_recipients"
            link_payload = {
                "message_id": new_msg['id'],
                "parent_phone": PHONE,
                "status": "pending"
            }
            requests.post(url_link, headers=get_headers(), json=link_payload)
            console.print(f"[green]   ✅ Linked to {PHONE}. Dispatcher should pick it up in 5s![/green]")
        else:
             console.print(f"[red]Failed to create message: {r1.text}[/red]")

if __name__ == "__main__":
    check_deployment()
