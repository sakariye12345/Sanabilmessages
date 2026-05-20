-- 🚨 EMERGENCY DEBUG: DISABLE RLS (TEMPORARY)
-- This confirms if RLS is the blocker. If messages appear, the policy is wrong.

BEGIN;

-- 1. Disable RLS on Recipients
ALTER TABLE message_recipients DISABLE ROW LEVEL SECURITY;

-- 2. Disable RLS on Messages (Just in case)
ALTER TABLE messages DISABLE ROW LEVEL SECURITY;

-- 3. Explicitly Grant access
GRANT SELECT, INSERT, UPDATE, DELETE ON message_recipients TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON messages TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON message_recipients TO anon;
GRANT SELECT, INSERT, UPDATE, DELETE ON messages TO anon;

COMMIT;

SELECT 'RLS Disabled on Recipient/Messages Tables' as status;
