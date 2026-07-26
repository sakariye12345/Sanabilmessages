-- UNIVERSAL SOLID FIX (The 'Nuclear' Option for Consistency)

-- 1. DROP ALL DEPENDENT CONSTRAINTS
-- We must drop constraints on ALL children to modify the parent safely.
ALTER TABLE message_recipients DROP CONSTRAINT IF EXISTS message_recipients_parent_phone_fkey;
ALTER TABLE student_parents DROP CONSTRAINT IF EXISTS student_parents_parent_phone_fkey;
ALTER TABLE user_devices DROP CONSTRAINT IF EXISTS user_devices_user_phone_fkey;

-- 2. NORMALIZE SOURCE OF TRUTH (allowed_parents)
-- Remove '+' prefix to match Auth (252...)
UPDATE allowed_parents
SET phone = REPLACE(phone, '+', '')
WHERE phone LIKE '+%';

-- 3. NORMALIZE ALL CHILDREN (Data Consistency)
-- message_recipients
UPDATE message_recipients
SET parent_phone = REPLACE(parent_phone, '+', '')
WHERE parent_phone LIKE '+%';

-- student_parents
UPDATE student_parents
SET parent_phone = REPLACE(parent_phone, '+', '')
WHERE parent_phone LIKE '+%';

-- user_devices (This was the one that failed last time)
UPDATE user_devices
SET user_phone = REPLACE(user_phone, '+', '')
WHERE user_phone LIKE '+%';

-- 4. RESTORE CONSTRAINTS WITH CASCADE (Future Proofing)
-- Adding ON UPDATE CASCADE ensures that if we ever change parent phone again, children follow automatically.

-- content
ALTER TABLE message_recipients
ADD CONSTRAINT message_recipients_parent_phone_fkey
FOREIGN KEY (parent_phone) REFERENCES allowed_parents (phone)
ON UPDATE CASCADE ON DELETE CASCADE;

-- students
ALTER TABLE student_parents
ADD CONSTRAINT student_parents_parent_phone_fkey
FOREIGN KEY (parent_phone) REFERENCES allowed_parents (phone)
ON UPDATE CASCADE ON DELETE CASCADE;

-- devices
ALTER TABLE user_devices
ADD CONSTRAINT user_devices_user_phone_fkey
FOREIGN KEY (user_phone) REFERENCES allowed_parents (phone)
ON UPDATE CASCADE ON DELETE CASCADE;

-- 5. STRICT SECURITY POLICY (Re-apply)
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
