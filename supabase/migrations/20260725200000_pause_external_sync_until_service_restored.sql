BEGIN;

DO $$
DECLARE
  target_job RECORD;
BEGIN
  FOR target_job IN
    SELECT jobid
    FROM cron.job
    WHERE jobname IN (
      'bridge-sync-every-minute',
      'sync-parents-every-15-minutes'
    )
  LOOP
    PERFORM cron.alter_job(target_job.jobid, active => FALSE);
  END LOOP;
END;
$$;

COMMIT;
