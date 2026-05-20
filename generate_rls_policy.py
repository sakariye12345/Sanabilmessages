import os
import json
from supabase import create_client, Client

# Hardcoded Service Key from otp_sender.py
SUPABASE_URL = "https://fmmatzjhhyhtkpabyhih.supabase.co"
SUPABASE_KEY = "YOUR_SUPABASE_SERVICE_ROLE_KEY"

supabase: Client = create_client(SUPABASE_URL, SUPABASE_KEY)

def check_policies():
    try:
        # We can't query pg_policies easily via postgrest unless we have a function or rpc.
        # But we can try to just select * from message_recipients as an 'anon' user if we had the key, 
        # but here we have service role.
        
        # Actually, let's just inspect the table structure via a helper if possible, 
        # or we assume we need to APPLY the policy to be safe.
        
        # Since I cannot easily read pg_policies without a privileged SQL channel (RPC 'exec_sql' doesn't exist),
        # I will CREATE the policy ensuring it exists.
        # Idempotent creation (DROP then CREATE).
        
        sql = """
        -- Enable RLS
        ALTER TABLE message_recipients ENABLE ROW LEVEL SECURITY;

        -- Drop existing policy to avoid conflict
        DROP POLICY IF EXISTS "Users can only see their own messages" ON message_recipients;

        -- Create restrictive policy
        -- Assuming auth.uid() or specific phone logic. 
        -- Since users authenticate via Phone but Supabase Auth might not explicitly map phone to a claim easily accessible in simple RLS without a join or custom claim.
        -- HOWEVER, standard pattern:
        -- If user is authenticated, we might need to match `auth.jwt() -> phone`? 
        -- Supabase Auth usually puts phone in `auth.users`.
        
        -- A robust policy for this app (where parent_phone is the key):
        -- We need a way to link the auth user to the parent_phone column.
        -- Usually: `parent_phone = (select phone from auth.users where id = auth.uid())`
        
        CREATE POLICY "Users can only see their own messages"
        ON message_recipients
        FOR SELECT
        USING (
            auth.role() = 'authenticated' AND
            parent_phone = (
                SELECT phone 
                FROM auth.users 
                WHERE id = auth.uid()
            )
        );
        """
        
        print("SQL to Apply (User needs to run this in Dashboard):")
        print(sql)
        
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    check_policies()
