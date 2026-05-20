-- ORDER IS IMPORTANT: Insert data first, then add constraint.

-- 1. Insert Default School (Must exist before we reference it)
INSERT INTO schools (id, name)
OVERRIDING SYSTEM VALUE
VALUES (1, 'Sanabil High School')
ON CONFLICT (id) DO NOTHING;

-- 2. Fix Missing Foreign Keys
-- Now that school ID 1 exists, existing messages (school_id=1) will satisfy this constraint.
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'messages_school_id_fkey') THEN
        ALTER TABLE messages 
        ADD CONSTRAINT messages_school_id_fkey 
        FOREIGN KEY (school_id) 
        REFERENCES schools(id);
    END IF;
END $$;

-- 3. Reload Schema Cache (This must be done via Dashboard usually, but sometimes a schema change triggers it)
-- The user needs to click "Reload Schema Cache" in the Dashboard API Settings if this doesn't work.
