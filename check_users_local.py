from supabase import create_client
import json

# Config
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
# The Key provided by the user in otp_sender.py
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

def check_users():
    if not SUPABASE_KEY:
        print("Error: Key not found.")
        return

    supabase = create_client(SUPABASE_URL, SUPABASE_KEY)
    
    print("Fetching users (limit 50)...")
    try:
        # Use auth.admin.list_users() correctly
        response = supabase.auth.admin.list_users() 
        # Note: In some python versions list_users() returns a list directly or an object with .users
        # Let's inspect the response type
        
        users = response.users if hasattr(response, 'users') else response
        
        # Depending on library version, might need pagination or differen access
        if not users:
             print("No users found.")
             return

        print(f"Found {len(users)} users.")
        for u in users:
            print(f"User Object Dir: {dir(u)}")
            try:
                print(f"User Dict: {u.__dict__}")
            except:
                pass
            
            # Try to access common attributes directly
            uid = getattr(u, 'id', "N/A")
            phone = getattr(u, 'phone', "N/A")
            email = getattr(u, 'email', "N/A")
            
            print(f"ID: {uid} | Phone: {phone} | Email: {email}")
            
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    check_users()
