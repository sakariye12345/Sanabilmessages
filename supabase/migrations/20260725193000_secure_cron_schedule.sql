BEGIN;

DO $$
DECLARE
  existing_job RECORD;
BEGIN
  FOR existing_job IN
    SELECT jobid
    FROM cron.job
    WHERE jobid IN (1, 2, 3)
       OR jobname IN (
         'invoke-bridge-every-minute',
         'invoke-bridge-sync',
         'sync-parents-every-7-min',
         'bridge-sync-every-minute',
         'sync-parents-every-15-minutes',
         'sanabil-operational-retention-hourly'
       )
  LOOP
    PERFORM cron.unschedule(existing_job.jobid);
  END LOOP;
END;
$$;

SELECT cron.schedule(
  'bridge-sync-every-minute',
  '* * * * *',
  $job$
    SELECT net.http_post(
      url := 'https://fmmatzjhhyhtkpabyhih.supabase.co/functions/v1/bridge-sync',
      headers := jsonb_build_object(
        'Content-Type', 'application/json',
        'x-internal-secret', (
          SELECT decrypted_secret
          FROM vault.decrypted_secrets
          WHERE name = 'sanabil_internal_cron_secret'
        )
      ),
      body := '{}'::JSONB
    );
  $job$
);

SELECT cron.schedule(
  'sync-parents-every-15-minutes',
  '*/15 * * * *',
  $job$
    SELECT net.http_post(
      url := 'https://fmmatzjhhyhtkpabyhih.supabase.co/functions/v1/sync-parents',
      headers := jsonb_build_object(
        'Content-Type', 'application/json',
        'x-internal-secret', (
          SELECT decrypted_secret
          FROM vault.decrypted_secrets
          WHERE name = 'sanabil_internal_cron_secret'
        )
      ),
      body := '{}'::JSONB
    );
  $job$
);

SELECT cron.schedule(
  'sanabil-operational-retention-hourly',
  '17 * * * *',
  $job$
    DELETE FROM net._http_response
    WHERE created < NOW() - INTERVAL '2 hours';

    DELETE FROM cron.job_run_details
    WHERE end_time < NOW() - INTERVAL '7 days';

    DELETE FROM public.sync_logs
    WHERE created_at < NOW() - INTERVAL '30 days';
  $job$
);

COMMIT;
