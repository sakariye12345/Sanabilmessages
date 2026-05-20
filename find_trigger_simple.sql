-- ==========================================
-- FINDING THE HIDDEN TRIGGER
-- ==========================================

-- 1. List all triggers on 'allowed_parents'
SELECT 
    trig.tgname AS trigger_name,
    proc.proname AS function_name,
    pg_get_functiondef(proc.oid) AS function_source
FROM pg_trigger trig
JOIN pg_class cls ON trig.tgrelid = cls.oid
JOIN pg_proc proc ON trig.tgfoid = proc.oid
WHERE cls.relname = 'allowed_parents';
