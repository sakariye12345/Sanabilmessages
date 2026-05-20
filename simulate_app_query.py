import os
from supabase import create_client, Client

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def simulate_app_query():
    phone = "252634370911"
    school_id = 1
    
    print(f"--- Simulating App Query for {phone} (School {school_id}) ---")
    
    # EXACT Query from inbox.tsx
    try:
        res = supabase.from_('message_recipients')\
            .select('id, status, created_at, messages!inner(id, title, body, type, school_id)')\
            .eq('parent_phone', phone)\
            .eq('messages.school_id', school_id)\
            .order('created_at', desc=True)\
            .limit(20)\
            .execute()
            
        print(f"Result Count: {len(res.data)}")
        for r in res.data:
            print(f"ID: {r['id']} | Type: {r['messages']['type']} | School: {r['messages']['school_id']}")
            
    except Exception as e:
        print(f"QUERY FAILED: {e}")

    # Debug: Check Messages Table RLS directly
    print("\n--- Checking Messages Table RLS ---")
    try:
        res_msg = supabase.table('messages').select('id, school_id').limit(5).execute()
        print(f"Direct Messages Query: Found {len(res_msg.data)} rows")
    except Exception as e:
        print(f"Direct Messages Query FAILED: {e}")

if __name__ == "__main__":
    simulate_app_query()
