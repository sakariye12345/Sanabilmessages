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

def fix_data():
    console.print("[bold cyan]🛠️  FIXING DATABASE DATA...[/bold cyan]")

    # 1. Fetch RAW messages
    url = f"{SUPABASE_URL}/rest/v1/message_recipients?select=id,parent_phone,status"
    resp = requests.get(url, headers=get_headers())
    rows = resp.json()

    console.print(f"Found {len(rows)} rows. Checking for issues...")

    for row in rows:
        row_id = row['id']
        phone = row['parent_phone']
        status = row['status']
        
        updates = {}

        # FIX 1: Ensure Phone has '+'
        if not phone.startswith('+'):
            clean_phone = phone.strip()
            # If it looks like 252..., add +
            if clean_phone.startswith('252'):
                updates['parent_phone'] = f"+{clean_phone}"
                console.print(f"   🔧 Fix Phone: {phone} -> {updates['parent_phone']}")
        
        # FIX 2: Ensure Status is lowercase 'pending'
        if status == 'Pending':
            updates['status'] = 'pending'
            console.print(f"   🔧 Fix Status: Pending -> pending")

        # Apply Updates
        if updates:
            patch_url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{row_id}"
            r = requests.patch(patch_url, headers=get_headers(), json=updates)
            if r.status_code in [200, 204]:
                console.print(f"   ✅ Updated Row {row_id}")
            else:
                console.print(f"   ❌ Failed Row {row_id}: {r.text}")

    console.print("\n[bold green]✨ Data Normalization Complete![/bold green]")
    console.print("The Dispatcher should now be able to see these messages.")

if __name__ == "__main__":
    fix_data()
