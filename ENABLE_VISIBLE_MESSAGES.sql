-- 🚨 EMERGENCY FIX: VISIBILITY
-- The App cannot see 'messages' table due to missing RLS policy.
-- This unblocks the JOIN.

BEGIN;

-- 1. Ensure 'messages' table is accessible
ALTER TABLE messages ENABLE ROW LEVEL SECURITY;

-- 2. Drop any conflicting policies
DROP POLICY IF EXISTS "Authenticated can read messages" ON messages;
DROP POLICY IF EXISTS "Public read messages" ON messages;

-- 3. Create a PERMISSIVE policy for Authenticated Users
-- We rely on 'message_recipients' to filter WHO sees WHAT.
-- If a user can see a 'recipient' row, they should be able to see the linked 'message'.
CREATE POLICY "Authenticated can read messages"
ON messages
FOR SELECT
TO authenticated
USING (true);

-- 4. Grant explicit permissions (sometimes needed)
GRANT SELECT ON messages TO authenticated;
GRANT SELECT ON message_recipients TO authenticated;

COMMIT;

SELECT 'Visibility Fix Applied Successfully' as status;
