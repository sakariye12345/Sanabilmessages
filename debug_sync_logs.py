import os
from supabase import create_client, Client
import json

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def fetch_logs():
    print("--- Fetching Recent Sync Logs ---")
    try:
        res = supabase.table('sync_logs')\
            .select('*')\
            .order('created_at', desc=True)\
            .limit(10)\
            .execute()
            
        for log in res.data:
            print(f"ID: {log['id']} | Status: {log['status']} | Time: {log['created_at']}")
            print(f"Message: {log['message']}")
            print(f"Details: {json.dumps(log.get('details'), indent=2)}")
            print("-" * 20)
    except Exception as e:
        print(f"Error fetching logs: {e}")

if __name__ == "__main__":
    fetch_logs()
