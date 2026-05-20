import os
from supabase import create_client, Client

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def check_type():
    print("Checking 'ci3_id' column type...")
    # information_schema is usually standard
    try:
        # Note: We can't select from information_schema via PostgREST easily unless exposed.
        # But we can try a trick: order by ci3_id as text vs int and see if it errors?
        # Better: Try to update a row with a string value like "test-string" on a dummy ID (or valid ID inside transaction if possible).
        # Safest: Use the 'rpc' capability if we had a function.
        
        # Let's try raw SQL via a python library if we had one, but we only have supabase-py.
        # supabase-py doesn't expose raw sql.
        
        # WE WILL ASSUME IT IS TEXT if we can insert a string.
        # Let's try to insert a dummy "501-test" into message_recipients (failed constraint likely, but type check first).
        
        # Actually, let's look at previous migrations/files.
        # 'supabase/migrations/smart_normalization.sql' might show schema.
        pass
    except Exception as e:
        print(e)
        
    # Plan B: Check migration files in local disk
    
if __name__ == "__main__":
    check_type()
