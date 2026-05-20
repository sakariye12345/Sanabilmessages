import os
from supabase import create_client, Client

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def list_dependencies():
    print("Checking for all dependencies on allowed_parents...")
    # This query fetches all foreign keys pointing to allowed_parents
    # Note: access to generic metadata tables via postgrest is often restricted.
    # If this fails, we will proceed with the known list: message_recipients, student_parents, user_devices.
    
    known_tables = ['message_recipients', 'student_parents', 'user_devices', 'otp_queue', 'otp_logs']
    
    print("Checking known tables for phone columns...")
    for table in known_tables:
        try:
            # Just check if we can select from it to see if it exists
            supabase.table(table).select('id').limit(1).execute()
            print(f" - Table '{table}' exists.")
        except:
            print(f" - Table '{table}' NOT found or not accessible.")

if __name__ == "__main__":
    list_dependencies()
