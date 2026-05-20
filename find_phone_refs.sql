-- ==========================================
-- FIXING TRIGGER REFERENCES AFTER RENAME
-- ==========================================

-- 1. Raadi triggers-ka saaran 'allowed_parents'
SELECT 
    trigger_name, 
    event_manipulation, 
    action_statement, 
    action_orientation
FROM information_schema.triggers 
WHERE event_object_table = 'allowed_parents';

-- 2. Raadi functions-ka laga yaabo inay isticmaalaan 'phone'
SELECT 
    p.proname as function_name,
    pg_get_functiondef(p.oid) as function_definition
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname = 'public' 
  AND pg_get_functiondef(p.oid) ILIKE '%phone%';
