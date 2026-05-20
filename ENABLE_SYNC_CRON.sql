-- 🕰️ ENABLE REAL-TIME SYNC SCHEDULER
-- This script sets up a 1-minute Cron Job to trigger 'bridge-sync'.

BEGIN;

-- 1. Enable Required Extensions
CREATE EXTENSION IF NOT EXISTS pg_cron;
CREATE EXTENSION IF NOT EXISTS pg_net;

-- 2. Clean up old jobs (Safely)
DO $$
BEGIN
    PERFORM cron.unschedule('invoke-bridge-sync');
EXCEPTION WHEN OTHERS THEN
    RAISE NOTICE 'Job did not exist, skipping unschedule.';
END $$;

-- 3. Schedule the Job (Every Minute)
-- We use pg_net to call the Edge Function URL.
-- NOTE: Replace ANON_KEY and PROJECT_REF with actual values if needed, 
-- but usually internal calls can use Service Role or just be triggered if function allows public (verification inside).
-- For Supabase Hosted, we use `net.http_post`.

SELECT cron.schedule(
    'invoke-bridge-sync', -- Job Name
    '* * * * *',          -- Schedule (Every Minute)
    $$
    select
        net.http_post(
            url:='https://fmmatzjhhyhtkpabyhih.supabase.co/functions/v1/bridge-sync',
            headers:='{"Content-Type": "application/json", "Authorization": "Bearer YOUR_SUPABASE_SERVICE_ROLE_KEY"}'::jsonb,
            body:='{}'::jsonb
        ) as request_id;
    $$
);

COMMIT;

SELECT 'Sync Schedule Created: Every Minute' as status;
