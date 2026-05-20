import requests
import json

URL = "https://fmmatzjhhyhtkpabyhih.supabase.co/functions/v1/notify-parents"
KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY"

headers = {
    "Authorization": f"Bearer {KEY}",
    "Content-Type": "application/json"
}

payload = {
    "record": {
        "id": 98043,
        "message_id": 110975,
        "parent_phone": "+252634370911",
        "status": "pending"
    }
}

print(f"Invoking {URL}...")
try:
    resp = requests.post(URL, headers=headers, json=payload)
    print(f"Status: {resp.status_code}")
    print(f"Response: {resp.text}")
except Exception as e:
    print(f"Error: {e}")
