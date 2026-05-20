-- 1. NORMALIZE DATA (Align DB with Auth)
-- Remove '+' from all phone numbers to match Auth format (which is '252...')
UPDATE message_recipients
SET parent_phone = REPLACE(parent_phone, '+', '')
WHERE parent_phone LIKE '+%';

-- 2. STRICT SECURITY POLICY (Solid Fix)
ALTER TABLE message_recipients ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Users can only see their own messages" ON message_recipients;

CREATE POLICY "Users can only see their own messages"
ON message_recipients
FOR SELECT
USING (
  auth.role() = 'authenticated' AND
  parent_phone = (SELECT phone FROM auth.users WHERE id = auth.uid())
);
