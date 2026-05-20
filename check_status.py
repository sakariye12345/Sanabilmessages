import requests
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

def check_status():
    # Note: URL encoding + is %2B
    encoded_phone = PHONE.replace("+", "%2B")
    url = f"{SUPABASE_URL}/rest/v1/message_recipients?parent_phone=eq.{encoded_phone}&select=id,status,parent_phone,sent_at"
    
    resp = requests.get(url, headers=get_headers())
    msgs = resp.json()
    
    if msgs:
        for m in msgs:
            color = "green" if m['status'] == 'sent' else "red"
            console.print(f"Message {m['id']}: [{color}]{m['status']}[/{color}] (Sent At: {m['sent_at']})")
    else:
        console.print("[red]No messages found.[/red]")

if __name__ == "__main__":
    check_status()
