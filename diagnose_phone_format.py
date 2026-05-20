import os
from supabase import create_client, Client

# Hardcoded Service Key from otp_sender.py
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def check_formats():
    print("Checking Phone Formats...")
    
    # Fetch a few rows from message_recipients
    data = supabase.table('message_recipients').select('parent_phone').limit(5).execute().data
    print("Message Recipients Phone Formats:")
    for row in data:
        print(f" - '{row['parent_phone']}'")

    # We can't easily check auth.users without admin API, but we can infer from the user's report (252634370911)
    # The previous screenshot showed '252634370911' in the WhatsApp admin panel.
    # My code was forcing '+'.
    
if __name__ == "__main__":
    check_formats()
