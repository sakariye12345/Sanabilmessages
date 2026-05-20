import os
from supabase import create_client, Client

# Hardcoded Service Key from otp_sender.py
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def check_auth_user():
    print("Checking Auth User Phone Format...")
    
    # We can list users via admin api if this key has service role (it does).
    try:
        # Supabase-py client admin interface might differ slightly by version, 
        # but let's try listed auth.admin.list_users() or direct query if possible?
        # Standard client usually exposes auth.admin
        
        # Searching strictly for the user's phone number variation
        # Common variations: "252634370911", "+252634370911"
        
        # The client version might return a list directly or a UserList object.
        res = supabase.auth.admin.list_users()
        users = res.users if hasattr(res, 'users') else res
        
        target_phones = ["252634370911", "+252634370911"]
        
        found = False
        for u in users:
            if u.phone and any(t in u.phone for t in target_phones):
                print(f"✅ Found User in Auth:")
                print(f"   ID: {u.id}")
                print(f"   Phone: '{u.phone}'")
                print(f"   Metadata: {u.user_metadata}")
                found = True
        
        if not found:
            print("❌ User 252634370911 not found in recent user list.")
            
    except Exception as e:
        print(f"Error listing users: {e}")

if __name__ == "__main__":
    check_auth_user()
