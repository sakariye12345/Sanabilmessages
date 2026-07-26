-- FINAL SOLID FIX (Handling All Dependencies)

-- 1. Drop Foreign Key Constraints (Temporary)
-- This allows us to update the parent table without violating constraints on children
ALTER TABLE message_recipients DROP CONSTRAINT IF EXISTS message_recipients_parent_phone_fkey;
ALTER TABLE student_parents DROP CONSTRAINT IF EXISTS student_parents_parent_phone_fkey;

-- 2. Normalize 'allowed_parents' (The Source of Truth)
-- Remove '+' to match Auth format (252...)
UPDATE allowed_parents
SET phone = REPLACE(phone, '+', '')
WHERE phone LIKE '+%';

-- 3. Normalize Children (message_recipients)
UPDATE message_recipients
SET parent_phone = REPLACE(parent_phone, '+', '')
WHERE parent_phone LIKE '+%';

-- 4. Normalize Children (student_parents)
-- This was the table causing the error previously
UPDATE student_parents
SET parent_phone = REPLACE(parent_phone, '+', '')
WHERE parent_phone LIKE '+%';

-- 5. Restore Constraints with CASCADE
-- Adding CASCADE ensures future updates to allowed_parents verify to children automatically
ALTER TABLE message_recipients
ADD CONSTRAINT message_recipients_parent_phone_fkey
FOREIGN KEY (parent_phone) REFERENCES allowed_parents (phone)
ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE student_parents
ADD CONSTRAINT student_parents_parent_phone_fkey
FOREIGN KEY (parent_phone) REFERENCES allowed_parents (phone)
ON UPDATE CASCADE ON DELETE CASCADE;

-- 6. Strict Security Policy (Re-apply)
ALTER TABLE message_recipients ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Users can only see their own messages" ON message_recipients;

CREATE POLICY "Users can only see their own messages"
ON message_recipients
FOR SELECT
USING (
  auth.role() = 'authenticated' AND
  parent_phone = (SELECT phone FROM auth.users WHERE id = auth.uid())
);
-- LEGACY REFERENCE: not part of the deployable migration chain.
