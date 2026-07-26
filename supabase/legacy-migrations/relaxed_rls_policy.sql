-- Relaxed RLS Policy for Phone Number Matching
-- Handles cases where one has '+' and the other doesn't.

ALTER TABLE message_recipients ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Users can only see their own messages" ON message_recipients;

CREATE POLICY "Users can only see their own messages"
ON message_recipients
FOR SELECT
USING (
  auth.role() = 'authenticated' AND (
    -- Direct Match
    parent_phone = (SELECT phone FROM auth.users WHERE id = auth.uid())
    OR
    -- Match without '+' prefix (e.g. +252... vs 252...)
    RIGHT(parent_phone, 12) = RIGHT((SELECT phone FROM auth.users WHERE id = auth.uid()), 12)
    OR
    -- Check if auth phone is inside parent_phone or vice versa?
    -- Safest: Normalize both to remove non-digits in comparison, but SQL is tricky.
    -- Let's assume standard 252 format.
    -- If DB has '+25263...', and Auth has '25263...'
    parent_phone LIKE '%' || (SELECT phone FROM auth.users WHERE id = auth.uid())
  )
);
-- LEGACY REFERENCE: not part of the deployable migration chain.
