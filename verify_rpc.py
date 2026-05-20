import os
from supabase import create_client, Client

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def verify_rpc():
    phone = "252634370911"
    print(f"--- Verifying RPC 'get_my_inbox' for {phone} ---")
    
    try:
        res = supabase.rpc('get_my_inbox', {'phone_arg': phone}).execute()
        print(f"RPC Result Count: {len(res.data)}")
        if len(res.data) > 0:
            print("First Row:", res.data[0])
        else:
            print("RPC returned empty list.")
            
    except Exception as e:
        print(f"RPC FAILED: {e}")

if __name__ == "__main__":
    verify_rpc()
