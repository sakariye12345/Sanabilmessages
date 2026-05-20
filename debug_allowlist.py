import os
from supabase import create_client, Client

# Hardcoded keys for debugging
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SERVICE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"
ANON_KEY = "YOUR_SUPABASE_PUBLISHABLE_KEY" # Assuming this is the anon key from .env (double check prefix usually eyJ... but let's try)
# user said .env has: EXPO_PUBLIC_SUPABASE_ANON_KEY=YOUR_SUPABASE_PUBLISHABLE_KEY
# Wait, "sb_publishable_..." might be a different format or a specific Supabase setting. Standard JWTs start with eyJ...
# But I will use what was in .env.

def test_access(key, role_name):
    print(f"\n--- Testing Access as {role_name} ---")
    try:
        client = create_client(SUPABASE_URL, key)
        response = client.table("allowed_parents").select("*").execute()
        
        if not response.data:
            print(f"[{role_name}] returned 0 records.")
        else:
            print(f"[{role_name}] returned {len(response.data)} records.")
            for row in response.data:
                phone = row.get('phone')
                print(f"   Phone: '{phone}' | Hex: {phone.encode('utf-8').hex()}")
    except Exception as e:
        print(f"[{role_name}] Error: {e}")

if __name__ == "__main__":
    test_access(SERVICE_KEY, "SERVICE_ROLE (Admin)")
    test_access(ANON_KEY, "ANON_ROLE (Public)")
