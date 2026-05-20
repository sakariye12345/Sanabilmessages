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

def verify_fix():
    console.print("[bold cyan]👀 VERIFYING DATA...[/bold cyan]")

    url = f"{SUPABASE_URL}/rest/v1/message_recipients?select=id,parent_phone,status&order=id.desc"
    resp = requests.get(url, headers=get_headers())
    rows = resp.json()

    console.print(f"Total Rows Visible: {len(rows)}")
    
    bad_rows = 0
    for row in rows:
        phone = row['parent_phone']
        status = row['status']
        if not phone.startswith('+'):
            console.print(f"[red]❌ ID {row['id']} Bad Phone: {phone}[/red]")
            bad_rows += 1
        if status == 'Pending':
             console.print(f"[red]❌ ID {row['id']} Bad Status: {status}[/red]")
             bad_rows += 1
             
    if bad_rows == 0:
        console.print("[green]✅ ALL DATA IS CLEAN! (+252 and lowercase pending)[/green]")
    else:
        console.print(f"[red]⚠️ Found {bad_rows} issues remaining.[/red]")

if __name__ == "__main__":
    verify_fix()
