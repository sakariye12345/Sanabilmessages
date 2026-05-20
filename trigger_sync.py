import requests

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
# Using the same key used for DB access which is likely the service role key
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

def trigger_sync():
    print("--- Triggering bridge-sync manually ---")
    url = f"{SUPABASE_URL}/functions/v1/bridge-sync"
    headers = {
        "Authorization": f"Bearer {SUPABASE_KEY}",
        "Content-Type": "application/json"
    }
    
    try:
        # The function seems to expect an empty body or any body
        resp = requests.post(url, headers=headers, json={}, timeout=60)
        print(f"Status: {resp.status_code}")
        print(f"Response: {resp.text}")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    trigger_sync()
