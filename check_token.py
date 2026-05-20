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

def check_token():
    encoded_phone = PHONE.replace("+", "%2B")
    url = f"{SUPABASE_URL}/rest/v1/user_devices?user_phone=eq.{encoded_phone}&select=fcm_token"
    
    resp = requests.get(url, headers=get_headers())
    devices = resp.json()
    
    if devices:
        token = devices[0]['fcm_token']
        console.print(f"[bold]Token:[/bold] {token}")
        if "SIMULATED" in token:
            console.print("[red]⚠️ WARNING: Using SIMULATED Token![/red]")
        else:
            console.print("[green]✅ Valid Expo Token Format[/green]")
    else:
        console.print("[red]No token found[/red]")

if __name__ == "__main__":
    check_token()
