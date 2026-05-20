import os
from supabase import create_client, Client

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def check_allowed_parents():
    print("Checking Allowed Parents Formats...")
    data = supabase.table('allowed_parents').select('phone').limit(5).execute().data
    for row in data:
        print(f" - '{row['phone']}'")

if __name__ == "__main__":
    check_allowed_parents()
