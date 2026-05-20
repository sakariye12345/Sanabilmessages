import requests
import os
import json

# Manual .env loading
try:
    with open(".env", "r") as f:
        for line in f:
            if "=" in line and not line.startswith("#"):
                key, val = line.strip().split("=", 1)
                os.environ[key] = val
except FileNotFoundError:
    print("Warning: .env file not found")

SUPABASE_URL = os.environ.get("SUPABASE_URL") or os.environ.get("EXPO_PUBLIC_SUPABASE_URL")
SUPABASE_KEY = os.environ.get("SUPABASE_KEY") or os.environ.get("EXPO_PUBLIC_SUPABASE_ANON_KEY")

if not SUPABASE_URL or not SUPABASE_KEY:
    print("Error: Missing SUPABASE_URL or SUPABASE_KEY")
    exit(1)

headers = {
    "apikey": SUPABASE_KEY,
    "Authorization": f"Bearer {SUPABASE_KEY}",
    "Content-Type": "application/json",
    "Prefer": "return=representation" # important for returning data
}

def test_update():
    print("1. Creating a TEST message...")
    # Insert Message
    msg_url = f"{SUPABASE_URL}/rest/v1/messages"
    msg_payload = {
        "school_id": 1,
        "type": "notice",
        "title": "Verification Test",
        "body": "This is a test message to verify triggers.",
        "created_at": "2025-01-01T12:00:00Z"
    }
    r = requests.post(msg_url, headers=headers, json=msg_payload)
    if r.status_code != 201:
        print(f"Failed to create message: {r.text}")
        return
    msg_id = r.json()[0]['id']

    # fetch valid phone
    phone_query = requests.get(f"{SUPABASE_URL}/rest/v1/message_recipients?select=parent_phone&limit=1", headers=headers)
    if phone_query.status_code == 200 and phone_query.json():
        valid_phone = phone_query.json()[0]['parent_phone']
    else:
        # Fallback if DB is empty, but likely to fail FK if so
        valid_phone = "+252615000000"

    # Insert Recipient (mimicking Bridge insertion)
    print(f"   Using Parent Phone: {valid_phone}")
    rcpt_url = f"{SUPABASE_URL}/rest/v1/message_recipients"
    rcpt_payload = {
        "message_id": msg_id,
        "parent_phone": valid_phone,
        "status": "pending",
        "ci3_id": "999999_TEST", 
        "is_synced_to_ci3": True # Pretend we already told CI3 it's 'sent'
    }
    r2 = requests.post(rcpt_url, headers=headers, json=rcpt_payload)
    if r2.status_code != 201:
        print(f"Failed to create recipient: {r2.text}")
        return
    recip_item = r2.json()[0]
    rid = recip_item['id']
    print(f"   Created Recipient ID: {rid} | Status: {recip_item['status']} | Synced: {recip_item['is_synced_to_ci3']}")

    # ---------------------------------------------------------
    
    print("\n2. Updating status to 'seen' (Simulating App)...")
    update_url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{rid}"
    payload = {"status": "seen", "seen_at": "2025-01-01T12:05:00Z"}
    
    resp_upd = requests.patch(update_url, headers=headers, json=payload)
    
    with open("last_status_code.txt", "w") as f:
        f.write(f"Status: {resp_upd.status_code}\nBody: {resp_upd.text}")

    if resp_upd.status_code not in [200, 204]:
        print(f"Error updating: {resp_upd.text}")
    else:
        print(f"   Update success ({resp_upd.status_code}).")

    # ---------------------------------------------------------

    print("\n3. Verifying Trigger (is_synced_to_ci3 should become False)...")
    check_url = f"{SUPABASE_URL}/rest/v1/message_recipients?id=eq.{rid}&select=id,status,is_synced_to_ci3"
    resp_check = requests.get(check_url, headers=headers)
    
    if resp_check.status_code == 200:
        updated_item = resp_check.json()[0]
        print(f"   New State -> ID: {updated_item['id']} | Status: {updated_item['status']} | Synced: {updated_item['is_synced_to_ci3']}")
        
        if updated_item['is_synced_to_ci3'] is False:
             print("\n[SUCCESS] TRIGGER WORKED: is_synced_to_ci3 FLIPPED to False.")
        else:
             print("\n[FAIL] TRIGGER FAILED: is_synced_to_ci3 is still True.")
    else:
        print("Error checking status.")

    # ---------------------------------------------------------
    # Cleanup
    # print("\n4. Cleanup (Deleting test data)...")
    # requests.delete(f"{SUPABASE_URL}/rest/v1/messages?id=eq.{msg_id}", headers=headers)


if __name__ == "__main__":
    test_update()
