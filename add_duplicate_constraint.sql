-- ==========================================
-- 🚫 DATABASE-LEVEL DEDUPLICATION FIX
-- ==========================================
-- Ensures the CI3 backend cannot assign the same message to the same parent multiple times.

DO $$ 
BEGIN
    ALTER TABLE public.message_recipients DROP CONSTRAINT IF EXISTS msg_recip_unique_assign;
    ALTER TABLE public.message_recipients ADD CONSTRAINT msg_recip_unique_assign UNIQUE (message_id, phone_number);
END $$;
