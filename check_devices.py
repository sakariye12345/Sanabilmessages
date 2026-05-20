import os
from dotenv import load_dotenv
from supabase import create_client

load_dotenv()
url = os.environ.get("EXPO_PUBLIC_SUPABASE_URL")
key = os.environ.get("EXPO_PUBLIC_SUPABASE_ANON_KEY") # Or service role key
supabase = create_client(url, key)

try:
    res = supabase.from_("user_devices").select("*").limit(5).execute()
    print("User Devices:", res.data)
except Exception as e:
    print("Error:", e)
