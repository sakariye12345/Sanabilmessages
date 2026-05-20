-- MIGRATE ci3_id TO TEXT
-- Required for Composite IDs (e.g. "501-finance") to resolve collisions.

BEGIN;

-- Check if column exists and alter it
DO $$ 
BEGIN 
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'message_recipients' AND column_name = 'ci3_id') THEN
        ALTER TABLE message_recipients ALTER COLUMN ci3_id TYPE text;
    END IF;
END $$;

COMMIT;
