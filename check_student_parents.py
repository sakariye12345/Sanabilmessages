import os
from supabase import create_client, Client

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def check_columns():
    print("Checking student_parents columns...")
    try:
        # Fetch one row
        data = supabase.table('student_parents').select('*').limit(1).execute().data
        if data:
            print("Columns:", data[0].keys())
        else:
            print("Table empty, cannot infer keys easily via select *. Assuming 'parent_phone' based on error.")
            # If empty, update won't hurt anyway, but the error said "referenced from table", implies data exists.
    except Exception as e:
        print(e)

if __name__ == "__main__":
    check_columns()
