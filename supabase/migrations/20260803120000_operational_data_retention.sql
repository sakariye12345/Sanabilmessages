BEGIN;

CREATE INDEX IF NOT EXISTS otp_queue_retention_idx
  ON public.otp_queue (created_at, id)
  WHERE status IN ('SENT', 'VERIFIED', 'FAILED');

CREATE INDEX IF NOT EXISTS otp_logs_retention_idx
  ON public.otp_logs (created_at, id);

CREATE INDEX IF NOT EXISTS sync_logs_retention_idx
  ON public.sync_logs (created_at, id);

CREATE INDEX IF NOT EXISTS push_delivery_tickets_retention_idx
  ON public.push_delivery_tickets (created_at, id)
  WHERE status IN ('DELIVERED', 'FAILED');

CREATE OR REPLACE FUNCTION public.purge_operational_data(
  p_batch_size INTEGER DEFAULT 5000,
  p_otp_retention_days INTEGER DEFAULT 30,
  p_push_retention_days INTEGER DEFAULT 30,
  p_sync_retention_days INTEGER DEFAULT 90
)
RETURNS TABLE (
  otp_queue_deleted INTEGER,
  otp_logs_deleted INTEGER,
  otp_attempts_deleted INTEGER,
  push_tickets_deleted INTEGER,
  sync_logs_deleted INTEGER
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  now_ts TIMESTAMPTZ := NOW();
BEGIN
  IF auth.role() <> 'service_role' THEN
    RAISE EXCEPTION 'Service role required' USING ERRCODE = '42501';
  END IF;

  IF p_batch_size NOT BETWEEN 100 AND 50000
     OR p_otp_retention_days NOT BETWEEN 7 AND 365
     OR p_push_retention_days NOT BETWEEN 7 AND 365
     OR p_sync_retention_days NOT BETWEEN 30 AND 730 THEN
    RAISE EXCEPTION 'Invalid retention settings';
  END IF;

  RETURN QUERY
  WITH
  otp_queue_targets AS MATERIALIZED (
    SELECT q.id
    FROM public.otp_queue q
    WHERE q.status IN ('SENT', 'VERIFIED', 'FAILED')
      AND q.created_at < now_ts - make_interval(days => p_otp_retention_days)
    ORDER BY q.created_at, q.id
    LIMIT p_batch_size
    FOR UPDATE SKIP LOCKED
  ),
  deleted_otp_queue AS (
    DELETE FROM public.otp_queue q
    USING otp_queue_targets target
    WHERE q.id = target.id
    RETURNING q.id
  ),
  otp_log_targets AS MATERIALIZED (
    SELECT log.id
    FROM public.otp_logs log
    WHERE log.created_at < now_ts - make_interval(days => p_otp_retention_days)
    ORDER BY log.created_at, log.id
    LIMIT p_batch_size
    FOR UPDATE SKIP LOCKED
  ),
  deleted_otp_logs AS (
    DELETE FROM public.otp_logs log
    USING otp_log_targets target
    WHERE log.id = target.id
    RETURNING log.id
  ),
  otp_attempt_targets AS MATERIALIZED (
    SELECT attempt.id
    FROM public.otp_request_attempts attempt
    WHERE attempt.created_at < now_ts - INTERVAL '24 hours'
    ORDER BY attempt.created_at, attempt.id
    LIMIT p_batch_size
    FOR UPDATE SKIP LOCKED
  ),
  deleted_otp_attempts AS (
    DELETE FROM public.otp_request_attempts attempt
    USING otp_attempt_targets target
    WHERE attempt.id = target.id
    RETURNING attempt.id
  ),
  push_ticket_targets AS MATERIALIZED (
    SELECT ticket.id
    FROM public.push_delivery_tickets ticket
    WHERE ticket.status IN ('DELIVERED', 'FAILED')
      AND ticket.created_at < now_ts - make_interval(days => p_push_retention_days)
    ORDER BY ticket.created_at, ticket.id
    LIMIT p_batch_size
    FOR UPDATE SKIP LOCKED
  ),
  deleted_push_tickets AS (
    DELETE FROM public.push_delivery_tickets ticket
    USING push_ticket_targets target
    WHERE ticket.id = target.id
    RETURNING ticket.id
  ),
  sync_log_targets AS MATERIALIZED (
    SELECT log.id
    FROM public.sync_logs log
    WHERE log.created_at < now_ts - make_interval(days => p_sync_retention_days)
    ORDER BY log.created_at, log.id
    LIMIT p_batch_size
    FOR UPDATE SKIP LOCKED
  ),
  deleted_sync_logs AS (
    DELETE FROM public.sync_logs log
    USING sync_log_targets target
    WHERE log.id = target.id
    RETURNING log.id
  )
  SELECT
    (SELECT COUNT(*)::INTEGER FROM deleted_otp_queue),
    (SELECT COUNT(*)::INTEGER FROM deleted_otp_logs),
    (SELECT COUNT(*)::INTEGER FROM deleted_otp_attempts),
    (SELECT COUNT(*)::INTEGER FROM deleted_push_tickets),
    (SELECT COUNT(*)::INTEGER FROM deleted_sync_logs);
END;
$$;

REVOKE ALL ON FUNCTION public.purge_operational_data(INTEGER, INTEGER, INTEGER, INTEGER)
  FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.purge_operational_data(INTEGER, INTEGER, INTEGER, INTEGER)
  TO service_role;

COMMIT;
