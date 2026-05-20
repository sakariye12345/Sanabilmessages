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

def reset():
    console.print(f"[bold cyan]🔄 Resetting Simulation for {PHONE}...[/bold cyan]")

    # 1. Find the message(s) linked to this parent
    # Note: URL encoding + is %2B
    encoded_phone = PHONE.replace("+", "%2B")
    url = f"{SUPABASE_URL}/rest/v1/message_recipients?parent_phone=eq.{encoded_phone}&select=*"
    
    resp = requests.get(url, headers=get_headers())
    msgs = resp.json()
    
    if not msgs:
        console.print("[red]❌ No messages found to reset.[/red]")
        return

    # 2. Reset them all to 'pending'
    for m in msgs:
        mid = m['id']
        update_url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{mid}"
        r = requests.patch(update_url, headers=get_headers(), json={"status": "pending"})
        
        if r.status_code in [200, 204]:
            console.print(f"[green]✅ Reset Message {mid} to PENDING[/green]")
        else:
            console.print(f"[red]❌ Failed to reset {mid}: {r.text}[/red]")

    console.print("\n[bold yellow]👀 Watch the Dispatcher Terminal NOW![/bold yellow]")

if __name__ == "__main__":
    reset()
