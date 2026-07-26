-- COMPREHENSIVE FIX for Phone Format Mismatch (Solid Architecture)

-- 1. Temporarily Drop Foreign Key Constraint to allow updates
-- We need to check if it exists first, or just try dropping it.
ALTER TABLE message_recipients DROP CONSTRAINT IF EXISTS message_recipients_parent_phone_fkey;

-- 2. Normalize 'allowed_parents' (The Source of Truth)
-- Remove '+' from phone numbers to match Auth format (252...)
UPDATE allowed_parents
SET phone = REPLACE(phone, '+', '')
WHERE phone LIKE '+%';

-- 3. Normalize 'message_recipients' (The Child Table)
UPDATE message_recipients
SET parent_phone = REPLACE(parent_phone, '+', '')
WHERE parent_phone LIKE '+%';

-- 4. Restore Foreign Key Constraint
ALTER TABLE message_recipients
ADD CONSTRAINT message_recipients_parent_phone_fkey
FOREIGN KEY (parent_phone)
REFERENCES allowed_parents (phone)
ON UPDATE CASCADE
ON DELETE CASCADE;

-- 5. Helper: Ensure local user is also updated if using a separate 'users' table?
-- Inspecting 'users' table might be useful, but allowed_parents is likely the key one.

-- 6. STRICT SECURITY POLICY (Re-apply)
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
