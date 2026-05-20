import os
from supabase import create_client, Client

# Service Key
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def diagnose():
    print("--- DIAGNOSTIC START ---")
    
    # 1. Check Auth User Phone
    # We know the ID from previous logs: e0f73690-79e4-4f2c-9eac-7ffb1f6c8d3f
    user_id = 'e0f73690-79e4-4f2c-9eac-7ffb1f6c8d3f'
    try:
        u = supabase.auth.admin.get_user_by_id(user_id)
        auth_phone = u.user.phone
        print(f"1. Auth Phone: '{auth_phone}'")
    except Exception as e:
        print(f"1. Auth Check Failed: {e}")
        return

    # 2. Check Message Recipients for this phone
    # We query as Service Role, so RLS is bypassed. This tells us if DATA exists.
    # We fetch ALL, sorted by created_at desc
    try:
        res = supabase.table('message_recipients')\
            .select('id, parent_phone, status, created_at, messages(id, title, school_id)')\
            .eq('parent_phone', auth_phone)\
            .order('created_at', desc=True)\
            .limit(5)\
            .execute()
        
        print(f"\n2. DB Rows for '{auth_phone}': Found {len(res.data)} rows")
        for row in res.data:
            print(f"   - ID: {row['id']}")
            print(f"     Status: {row['status']}")
            print(f"     Created: {row['created_at']}")
            print(f"     Message: {row['messages']}")
            
    except Exception as e:
        print(f"2. DB Query Failed: {e}")

    # 3. Simulate RLS Policy
    # "parent_phone = (SELECT phone FROM auth.users WHERE id = auth.uid())"
    # We confirmed auth_phone matches the query phone.
    # So if rows exist in step 2, RLS *should* pass IF the policy is active and correct.

    print("\n--- DIAGNOSTIC END ---")

if __name__ == "__main__":
    diagnose()
