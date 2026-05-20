import os
from supabase import create_client, Client

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def check_user_devices():
    print("Checking user_devices columns...")
    try:
        data = supabase.table('user_devices').select('*').limit(1).execute().data
        if data:
            print("Columns:", data[0].keys())
        else:
            print("Table empty, cannot infer keys.")
            # If empty, update is safe anyway.
    except Exception as e:
        print(e)

if __name__ == "__main__":
    check_user_devices()
