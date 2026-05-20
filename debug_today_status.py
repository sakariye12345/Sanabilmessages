import os
from supabase import create_client, Client
import json
from datetime import datetime

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def check_today():
    print(f"--- Checking Messages for Today ({datetime.now().date()}) ---")
    
    # 1. Check message_recipients for the phone in screenshot
    phone = '252634370911' 
    res = supabase.table('message_recipients')\
        .select('id, ci3_id, parent_phone, created_at, messages!inner(id, type, body, title)')\
        .eq('parent_phone', phone)\
        .order('created_at', desc=True)\
        .limit(20)\
        .execute()
        
    print(f"Found {len(res.data)} rows in Supabase for {phone}:")
    for r in res.data:
        msg = r.get('messages') or {}
        print(f"RCPT_ID: {r['id']} | CI3_ID: {r.get('ci3_id')} | Type: {msg.get('type')}")
        print(f"Created: {r['created_at']}")
        print(f"Body: {msg.get('body')[:50]}...")
        print("-" * 20)

    # 2. Check Sync Logs for errors
    print("\n--- Recent Sync Logs ---")
    logs = supabase.table('sync_logs').select('*').order('created_at', desc=True).limit(5).execute()
    for l in logs.data:
        print(f"Log: {l['status']} | Msg: {l['message']}")

if __name__ == "__main__":
    check_today()
