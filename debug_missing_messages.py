import os
from supabase import create_client, Client
import json

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def debug_messages():
    print("--- Fetching Recent Messages for 252634370911 ---")
    
    # Check for ID Collision
    print("Checking for CI3 ID 501 (Fee Message) in DB...")
    res = supabase.table('message_recipients')\
        .select('*, messages(*)')\
        .eq('ci3_id', '501')\
        .execute()
        
    if res.data:
        print(f"❌ COLLISION FOUND! Found {len(res.data)} rows with ci3_id=501:")
        for r in res.data:
            print(f"  - DB ID: {r['id']} | Created: {r['created_at']} | Phone: {r['parent_phone']}")
            if 'messages' in r:
                print(f"    Linked Msg ID: {r['messages']['id']} | Body: {r['messages']['body'][:50]}...")
    else:
        print("✅ No collision. ci3_id=501 is clean.")
        
    # Inspect DB Constraints (via raw SQL or inference)
    # We can try to insert a duplicate 501 with DIFFERENT content to see if it fails (if unique constraint exists)
    # But safer to just list indexes? Supabase-py doesn't make this easy.
    # Let's assume Unique Constraint exists because the Bridge checks for "Duplicates" manually.
    # bridge-sync/index.ts:187: if (existing) return false // Duplicate
    # It does an application-level check.

    pass
        
    # Inspect Schools
    print("Fetching Active Schools...")
    res = supabase.table('schools').select('*').execute()
    for s in res.data:
        print(f"ID: {s['id']} | Name: {s['name']} | Status: {s.get('is_active')}")
        print(f"CI3 URL: {s.get('ci3_url')}")
        # Mask Token
        token = s.get('ci3_token', '')
        print(f"Token: {token[:4]}...{token[-4:] if len(token)>4 else ''}")
        print("-" * 10)

def inspect_api_keys():
    import requests
    CI3_URL = "https://schoolsfls443dr4rsm53m.shihaab.tech"
    TOKEN = os.environ.get("CI3_API_TOKEN", "").strip()
    if not TOKEN:
        raise RuntimeError("CI3_API_TOKEN environment variable is required")
    
    print("--- Inspecting API Item 501 Keys ---")
    headers = {"Authorization": TOKEN, "Content-Type": "application/json"}
    try:
        resp = requests.get(f"{CI3_URL}/messages/contacts", headers=headers, timeout=10)
        if resp.status_code == 200:
            data = resp.json()
            target = next((m for m in data if str(m.get('id')) == '501'), None)
            if target:
                print("Keys:", list(target.keys()))
                print("Full Dump:", json.dumps(target, indent=2))
            else:
                print("ID 501 not found in current API response.")
    except Exception as e:
        print(f"Error: {e}")
        # The original code had `token` here, but it's not defined in this scope.
        # Assuming it meant the TOKEN defined in this function.
        print(f"Token: {TOKEN[:4]}...{TOKEN[-4:] if len(TOKEN)>4 else ''}")
        print("-" * 10)

if __name__ == "__main__":
    debug_messages()
    inspect_api_keys()
