-- ==========================================
-- FIXING SCHEMA FOR SYNC-PARENTS (PRODUCTION)
-- ==========================================

-- 1. Ku dar tiirarka (columns) loo baahanyahay si Sync-gu u shaqeeyo
ALTER TABLE public.allowed_parents 
ADD COLUMN IF NOT EXISTS parent_id BIGINT,
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS last_sync_at TIMESTAMPTZ DEFAULT NOW();

-- 2. Hubi in (phone) iyo (name) ay jiraan, haddi kalena sax
-- Haddii aad rabto inaad magacyada la mid dhigto Edge Function-ka:
DO $$ 
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='allowed_parents' AND column_name='phone') THEN
    ALTER TABLE public.allowed_parents RENAME COLUMN phone TO phone_number;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='allowed_parents' AND column_name='name') THEN
    ALTER TABLE public.allowed_parents RENAME COLUMN name TO parent_name;
  END IF;
END $$;

-- 3. Ka dhig (school_id, parent_id) inay noqdaan kuwo gaar ah (Unique) si Upsert-ku u shaqeeyo
ALTER TABLE public.allowed_parents DROP CONSTRAINT IF EXISTS allowed_parents_school_parent_unique;
ALTER TABLE public.allowed_parents ADD CONSTRAINT allowed_parents_school_parent_unique UNIQUE (school_id, parent_id);

-- 4. Hubi in foreign keys ay wali saxanyihiin hadii magacyadii isbedeleen
-- (Haddii meelo kale ay u isticmaali jireen 'phone', waan soo celin karnaa/u deyn karnaa)
-- Tusaale: 
-- ALTER TABLE public.message_recipients RENAME COLUMN parent_phone TO phone_number;
