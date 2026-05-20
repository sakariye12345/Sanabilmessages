import os
from supabase import create_client, Client

SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def simulate_logic(new_id, new_type):
    print(f"--- Simulating Logic for ID: {new_id}, Type: {new_type} ---")
    
    rawCi3Id = str(new_id)
    compositeCi3Id = f"{rawCi3Id}-{new_type}"
    school_id = 1
    
    print(f"1. Checking Exact Match: '{compositeCi3Id}'")
    exact = supabase.table('message_recipients')\
        .select('id')\
        .eq('ci3_id', compositeCi3Id)\
        .execute()
        
    print(f"   Result: {len(exact.data)} rows")
    if exact.data:
        # Fetch message to check school_id
        msg_id = exact.data[0].get('message_id')
        # ... logic skipped for brevity in simulation, assuming school matches if ID matches
        print("   -> BLOCKED by Exact Match")
        return

    print(f"2. Checking Legacy Match: '{rawCi3Id}'")
    # Fetch Message Recipient first
    legacy_res = supabase.table('message_recipients')\
        .select('id, message_id')\
        .eq('ci3_id', rawCi3Id)\
        .execute() 
        
    print(f"   Result: {len(legacy_res.data)} rows")
    
    if legacy_res.data:
        # Fetch linked message to check type
        msg_id = legacy_res.data[0]['message_id']
        msg_res = supabase.table('messages')\
            .select('id, type, school_id')\
            .eq('id', msg_id)\
            .single()\
            .execute()
            
        legacy_msg = msg_res.data
        if legacy_msg['school_id'] != school_id:
             print("   -> ALLOWED (Different School)")
             return

        legacy_type = legacy_msg.get('type')
        print(f"   Legacy Type Found: {legacy_type}")
        
        if legacy_type == new_type:
            print("   -> BLOCKED by Legacy Match (Same Type)")
        else:
            print("   -> ALLOWED (Different Type)")
    else:
        print("   -> ALLOWED (No Legacy Found)")

if __name__ == "__main__":
    # Test with a likely next ID (e.g. 566 or 600)
    simulate_logic(600, 'absence')
    simulate_logic(501, 'finance') # Should be ALLOWED (if 501-absence aka 501 exists)
    simulate_logic(501, 'absence') # Should be BLOCKED
