import requests
import json
import os

CI3_URL = "https://schoolsfls443dr4rsm53m.shihaab.tech"
TOKEN = os.environ.get("CI3_API_TOKEN", "").strip()
if not TOKEN:
    raise RuntimeError("CI3_API_TOKEN environment variable is required")

def check_api():
    print(f"--- Fecthing from {CI3_URL} ---")
    headers = {
        "Authorization": TOKEN,
        "Content-Type": "application/json"
    }
    
    try:
        resp = requests.get(f"{CI3_URL}/messages/contacts", headers=headers, timeout=15)
        if resp.status_code == 200:
            data = resp.json()
            print(f"Status: 200 OK | Count: {len(data)}")
            
            # Check for Fee messages
            fee_msgs = [m for m in data if "feega" in m.get('message', '').lower() or "fee" in m.get('message', '').lower()]
            print(f"Found {len(fee_msgs)} potential Fee messages.")
            
            # Print first 5 items to see structure
            for i, m in enumerate(data[:5]):
                print(f"[{i}] ID: {m.get('id')} | Status: {m.get('sent_status')} | Phone: {m.get('phone')}")
                print(f"    Body: {m.get('message', '')[:60]}...")
                
            # Print Fee Messages details if any
            if fee_msgs:
                print("\n--- DETAILED FEE MESSAGES ---")
                for m in fee_msgs:
                    print(json.dumps(m, indent=2))
        else:
            print(f"Error: {resp.status_code}")
            print(resp.text)
            
    except Exception as e:
        print(f"Exception: {e}")

if __name__ == "__main__":
    check_api()
