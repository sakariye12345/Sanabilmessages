-- ==========================================
-- AUDIT: CHECKING RLS POLICIES FOR BROKEN COLS
-- ==========================================

-- 1. List all policies on 'allowed_parents'
SELECT 
    schemaname, 
    tablename, 
    policyname, 
    permissive, 
    roles, 
    cmd, 
    qual, 
    with_check
FROM pg_policies 
WHERE tablename = 'allowed_parents';

-- 2. Check for 'phone' in other tables' policies that might link to 'allowed_parents'
SELECT 
    schemaname, 
    tablename, 
    policyname, 
    qual
FROM pg_policies 
WHERE qual ILIKE '%phone%' OR with_check ILIKE '%phone%';
