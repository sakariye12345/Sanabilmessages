-- RLS FIX: Secure Phone Access
-- The previous policy failed because 'auth.users' is not readable by normal users.
-- We solve this by creating a Security Definer function (runs as Admin) to fetch the user's phone.

-- 1. Create Helper Function
CREATE OR REPLACE FUNCTION public.get_auth_phone()
RETURNS TEXT AS $$
    SELECT phone FROM auth.users WHERE id = auth.uid();
$$ LANGUAGE sql SECURITY DEFINER;
-- SECURITY DEFINER = Runs with permissions of the creator (postgres/admin), bypassing RLS.

-- 2. Update Policy for 'message_recipients'
DROP POLICY IF EXISTS "Users can only see their own messages" ON message_recipients;

CREATE POLICY "Users can only see their own messages"
ON message_recipients
FOR SELECT
USING (
  auth.role() = 'authenticated' AND
  parent_phone = public.get_auth_phone()
);

-- Note: We trust get_auth_phone() returns the normalized phone (252...) because:
-- a) We updated auth.users to be 252...
-- b) We updated message_recipients to be 252...
-- c) They match.

-- 3. Verify Function works?
-- You can run `SELECT public.get_auth_phone();` in SQL Editor as a user to test.
-- LEGACY REFERENCE: not part of the deployable migration chain.
