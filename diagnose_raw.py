import requests
from rich.console import Console

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY"
console = Console()

def get_headers():
    return {
        "apikey": SUPABASE_KEY,
        "Authorization": f"Bearer {SUPABASE_KEY}",
        "Content-Type": "application/json"
    }

def diagnose_raw():
    console.print("[bold cyan]🔍 INSPECTING RAW DATA...[/bold cyan]")

    # 1. Check User Devices (To see how the App registered the phone)
    url_devices = f"{SUPABASE_URL}/rest/v1/user_devices?select=user_phone,platform,fcm_token&limit=5"
    resp = requests.get(url_devices, headers=get_headers())
    console.print("\n[bold]1. Registered Devices (from App):[/bold]")
    console.print(resp.json())

    # 2. Check Messages (To see how the Admin posted the phone & status)
    url_msgs = f"{SUPABASE_URL}/rest/v1/message_recipients?select=id,parent_phone,status&limit=5&order=id.desc"
    resp = requests.get(url_msgs, headers=get_headers())
    console.print("\n[bold]2. Message Queue (from Admin/Import):[/bold]")
    console.print(resp.json())

if __name__ == "__main__":
    diagnose_raw()
