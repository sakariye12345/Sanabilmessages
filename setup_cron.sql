-- ====================================================
-- MULTI-TENANT SCHEMA UPGRADE
-- Run this in Supabase SQL Editor
-- ====================================================

-- 1. Add OTP Gateway config to schools table
-- This allows each school to have its own dedicated SMS/WhatsApp OTP sender
ALTER TABLE public.schools
  ADD COLUMN IF NOT EXISTS otp_gateway_url  TEXT,      -- e.g. https://api.infobip.com/sms/...
  ADD COLUMN IF NOT EXISTS otp_gateway_key  TEXT,      -- API Key / Token for the gateway
  ADD COLUMN IF NOT EXISTS otp_sender_id    TEXT;      -- e.g. "SANABIL" or "+252..." 

-- 2. Add school_id to otp_queue for per-school tracking
ALTER TABLE public.otp_queue
  ADD COLUMN IF NOT EXISTS school_id BIGINT REFERENCES public.schools(id);

-- 3. Update existing otp_queue rows to school 1 (default)
UPDATE public.otp_queue SET school_id = 1 WHERE school_id IS NULL;

-- 4. Enable pg_cron and pg_net extensions (if not yet enabled)
--    NOTE: You must also enable these in Supabase Dashboard > Extensions
CREATE EXTENSION IF NOT EXISTS pg_cron;
CREATE EXTENSION IF NOT EXISTS pg_net;

-- 5. Schedule sync-parents every 7 minutes
--    Replace 'YOUR_SERVICE_ROLE_KEY' with your actual key from Supabase Dashboard > Settings > API
SELECT cron.schedule(
  'sync-parents-every-7-min',
  '*/7 * * * *',
  $$
    SELECT net.http_post(
      url     := 'https://fmmatzjhhyhtkpabyhih.supabase.co/functions/v1/sync-parents',
      headers := jsonb_build_object(
        'Content-Type',  'application/json',
        'Authorization', 'Bearer YOUR_SUPABASE_SERVICE_ROLE_KEY'
      ),
      body    := '{}'::jsonb
    ) AS request_id;
  $$
);

-- 6. Verify the cron job was created
SELECT jobname, schedule, command FROM cron.job WHERE jobname = 'sync-parents-every-7-min';
