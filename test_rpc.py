from supabase import create_client, Client
import os
from dotenv import load_dotenv

load_dotenv()
url = os.environ.get("EXPO_PUBLIC_SUPABASE_URL")
key = os.environ.get("EXPO_PUBLIC_SUPABASE_ANON_KEY")

supabase: Client = create_client(url, key)

res = supabase.rpc('get_inbox_summary', {}).execute()
print(res)
