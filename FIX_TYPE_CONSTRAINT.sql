-- 🛠️ FIX MESSAGE TYPE CONSTRAINT
-- The 'messages' table has a check constraint that restricts the 'type' column.
-- We need to add 'finance', 'exam', 'receipt' to allow these messages to sync.

BEGIN;

-- 1. Drop the old constraint
ALTER TABLE messages DROP CONSTRAINT IF EXISTS messages_type_check;

-- 2. Add the new relaxed constraint
ALTER TABLE messages
ADD CONSTRAINT messages_type_check
CHECK (type IN ('general', 'notice', 'absence', 'exam', 'finance', 'receipt', 'received'));

COMMIT;

SELECT 'Constraint Updated: Added finance, exam, receipt' as status;
