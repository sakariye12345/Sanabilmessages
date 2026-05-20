-- FIX MESSAGES RLS VISIBILITY
-- The app joins 'message_recipients' with 'messages'.
-- If 'messages' table has RLS enabled but no policy for the user, the join returns 0 rows.
-- This policy allows Authenticated users to read messages.
-- Only 'message_recipients' restricts WHICH messages they see (via parent_phone).

ALTER TABLE messages ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Authenticated can read messages" ON messages;

CREATE POLICY "Authenticated can read messages"
ON messages
FOR SELECT
TO authenticated
USING (true);

-- Also ensure message_recipients is accessible (Redundant but safe)
ALTER TABLE message_recipients ENABLE ROW LEVEL SECURITY;
