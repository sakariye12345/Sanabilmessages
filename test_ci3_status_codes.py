import requests
import json
from rich.console import Console

console = Console()

# Configuration (Copied from bridge_service.py)
CI3_BASE_URL = "https://schoolsfls443dr4rsm53m.shihaab.tech"
CI3_TOKEN = "3e8ea952f2a06672"


def get_ci3_headers():
    return {
        "Authorization": CI3_TOKEN,
        "Content-Type": "application/json"
    }

def probe_ci3_status(ci3_id, status_to_try):
    url = f"{CI3_BASE_URL}/messages/update_status"
    payload = {
        "contact_id": ci3_id,
        "sent_status": status_to_try
    }
    
    console.print(f"👉 Testing status: [bold cyan]'{status_to_try}'[/bold cyan] for ID: {ci3_id}")
    try:
        resp = requests.post(url, headers=get_ci3_headers(), json=payload, timeout=10)
        console.print(f"   Response Code: {resp.status_code}")
        console.print(f"   Response Body: {resp.text}")
        
        if resp.status_code == 200:
             console.print("[green]   ✅ HTTP 200 OK - Please check CI3 Dashboard![/green]")
        else:
             console.print("[red]   ❌ Failed[/red]")
             
    except Exception as e:
        console.print(f"[red]   Exception: {e}[/red]")
    console.print("-" * 30)

if __name__ == "__main__":
    # We need a valid CI3 ID to test with.
    # I'll ask the user to provide one, or pick a likely candidate from previous logs if possible.
    # For now, let's use a placeholder that the user can edit, or better, try to find a recent valid one.
    
    # Let's try to find a real CI3 ID from the database if possible?
    # No, let's just use a hardcoded one for the loop and ask the user to input one if it fails.
    # The user's screenshot had IDs. Let's ask them to pick one.
    
    # Auto-fetch a valid ID from Supabase
    SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
    SUPABASE_KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY"
    
    headers = {
        "apikey": SUPABASE_KEY,
        "Authorization": f"Bearer {SUPABASE_KEY}",
    }
    
    print("Fetching a valid CI3 ID from Supabase...")
    try:
        r = requests.get(f"{SUPABASE_URL}/rest/v1/message_recipients?ci3_id=not.is.null&limit=1&order=created_at.desc", headers=headers)
        if r.status_code == 200 and r.json():
            target_id = r.json()[0]['ci3_id']
            print(f"Found CI3 ID: {target_id}")
        else:
            target_id = input("Could not fetch ID. Please enter one manually: ")
    except:
         target_id = input("Error fetching ID. Please enter one manually: ")

    statuses_to_test = [
        # Standard strings
        'seen', 'read', 'delivered', 'sent',
        'Seen', 'Read', 'Delivered', 'Sent',
        'READ', 'SEEN',
        # Synonyms
        'viewed', 'opened', 'received', 'acknowledged',
        # Integers (sent as strings and ints)
        '0', '1', '2', '3', '4', 
        2, 3, 4
    ]
    
    for s in statuses_to_test:
        probe_ci3_status(target_id, s)
