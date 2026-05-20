import requests
import json
from rich.console import Console

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY"
MSG_ID = 110975 # The ID from the previous run

console = Console()

def get_headers():
    return {
        "apikey": SUPABASE_KEY,
        "Authorization": f"Bearer {SUPABASE_KEY}",
        "Content-Type": "application/json"
    }

def check_data():
    console.print(f"Checking Message {MSG_ID}...")
    
    # 1. Check Messages
    url = f"{SUPABASE_URL}/rest/v1/messages?id=eq.{MSG_ID}&select=*,schools(name)"
    resp = requests.get(url, headers=get_headers())
    console.print(f"Messages Response: {resp.status_code}")
    console.print(f"Body: {resp.text}")

    # 2. Check Schools
    url_s = f"{SUPABASE_URL}/rest/v1/schools?select=*"
    resp_s = requests.get(url_s, headers=get_headers())
    console.print(f"Schools Response: {resp_s.status_code}")
    console.print(f"Body: {resp_s.text}")

if __name__ == "__main__":
    check_data()
