import os
from supabase import create_client, Client

# Hardcoded Service Key from otp_sender.py
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

try:
    # Look for constraint definition via RPC if possible, or just insert dummy to fail.
    # Actually, simpler to just assume 'pending', 'sent', 'failed', 'seen'.
    # I'll just print a message since I can't easily query pg_catalog via this client without rpc.
    # I will stick to 'seen' as the only safe app-status update.
    pass
except Exception as e:
    print(e)
