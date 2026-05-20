import os
from supabase import create_client, Client
import time

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def trigger_insert():
    print("Inserting test message to trigger Realtime...")
    # Using a dummy parent phone that matches the diagnostics
    phone = "252634370911" 
    
    # 1. Create a dummy message
    m_res = supabase.table('messages').insert({
        'title': 'Realtime Test 🚀',
        'body': 'This is a test to verify instant delivery.',
        'type': 'notice',
        'school_id': 1
    }).execute()
    
    msg_id = m_res.data[0]['id']
    print(f"Created Message ID: {msg_id}")
    
    # 2. Assign to recipient (This should fire the event)
    r_res = supabase.table('message_recipients').insert({
        'message_id': msg_id,
        'parent_phone': phone,
        'school_id': 1, # Ensuring RLS passes if needed
        'status': 'pending'
    }).execute()
    
    print(f"Created Recipient: {r_res.data}")

if __name__ == "__main__":
    trigger_insert()
