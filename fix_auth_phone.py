import os
from supabase import create_client, Client

# Hardcoded Service Key from otp_sender.py
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def fix_auth_phone():
    print("Standardizing Auth User Phone to E.164...")
    
    # Target User ID from previous step
    user_id = 'e0f73690-79e4-4f2c-9eac-7ffb1f6c8d3f' 
    new_phone = '+252634370911'
    
    try:
        # Update user
        res = supabase.auth.admin.update_user_by_id(user_id, {"phone": new_phone})
        print(f"✅ User Updated: {res.user.phone}")
        print("Auth is now E.164 compliant.")
    except Exception as e:
        print(f"❌ Error updating user: {e}")

if __name__ == "__main__":
    fix_auth_phone()
