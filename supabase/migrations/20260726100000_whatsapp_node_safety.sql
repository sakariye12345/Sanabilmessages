BEGIN;

-- Keep the WhatsApp worker's database contract in the canonical migration
-- chain. These columns previously existed only through historical/manual SQL.
ALTER TABLE public.schools
  ADD COLUMN IF NOT EXISTS wa_session_status TEXT NOT NULL DEFAULT 'DISCONNECTED',
  ADD COLUMN IF NOT EXISTS server_node_id TEXT,
  ADD COLUMN IF NOT EXISTS otp_cooldown_seconds INTEGER NOT NULL DEFAULT 30,
  ADD COLUMN IF NOT EXISTS otp_daily_cap INTEGER NOT NULL DEFAULT 250,
  ADD COLUMN IF NOT EXISTS otp_is_paused BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS otp_pause_reason TEXT,
  ADD COLUMN IF NOT EXISTS otp_pause_until TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS otp_last_sent_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS otp_last_error_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS otp_consecutive_failures INTEGER NOT NULL DEFAULT 0;

ALTER TABLE public.otp_queue
  ADD COLUMN IF NOT EXISTS attempt_count INTEGER NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS processing_started_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS error_message TEXT,
  ADD COLUMN IF NOT EXISTS provider TEXT NOT NULL DEFAULT 'whatsapp',
  ADD COLUMN IF NOT EXISTS sent_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW();

ALTER TABLE public.otp_logs
  ADD COLUMN IF NOT EXISTS provider TEXT NOT NULL DEFAULT 'whatsapp',
  ADD COLUMN IF NOT EXISTS error_message TEXT,
  ADD COLUMN IF NOT EXISTS sent_at TIMESTAMPTZ;

UPDATE public.schools
SET
  wa_session_status = COALESCE(wa_session_status, 'DISCONNECTED'),
  otp_cooldown_seconds = GREATEST(COALESCE(otp_cooldown_seconds, 30), 1),
  otp_daily_cap = GREATEST(COALESCE(otp_daily_cap, 250), 1),
  otp_is_paused = COALESCE(otp_is_paused, FALSE),
  otp_consecutive_failures = GREATEST(COALESCE(otp_consecutive_failures, 0), 0);

UPDATE public.otp_queue
SET
  attempt_count = GREATEST(COALESCE(attempt_count, 0), 0),
  provider = COALESCE(NULLIF(provider, ''), 'whatsapp'),
  updated_at = COALESCE(updated_at, created_at, NOW());

UPDATE public.otp_logs
SET provider = COALESCE(NULLIF(provider, ''), 'whatsapp');

ALTER TABLE public.schools
  ALTER COLUMN wa_session_status SET DEFAULT 'DISCONNECTED',
  ALTER COLUMN wa_session_status SET NOT NULL,
  ALTER COLUMN otp_cooldown_seconds SET DEFAULT 30,
  ALTER COLUMN otp_cooldown_seconds SET NOT NULL,
  ALTER COLUMN otp_daily_cap SET DEFAULT 250,
  ALTER COLUMN otp_daily_cap SET NOT NULL,
  ALTER COLUMN otp_is_paused SET DEFAULT FALSE,
  ALTER COLUMN otp_is_paused SET NOT NULL,
  ALTER COLUMN otp_consecutive_failures SET DEFAULT 0,
  ALTER COLUMN otp_consecutive_failures SET NOT NULL;

ALTER TABLE public.otp_queue
  ALTER COLUMN attempt_count SET DEFAULT 0,
  ALTER COLUMN attempt_count SET NOT NULL,
  ALTER COLUMN provider SET DEFAULT 'whatsapp',
  ALTER COLUMN provider SET NOT NULL,
  ALTER COLUMN updated_at SET DEFAULT NOW(),
  ALTER COLUMN updated_at SET NOT NULL;

ALTER TABLE public.otp_logs
  ALTER COLUMN provider SET DEFAULT 'whatsapp',
  ALTER COLUMN provider SET NOT NULL;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'schools_wa_session_status_check'
      AND conrelid = 'public.schools'::regclass
  ) THEN
    ALTER TABLE public.schools
      ADD CONSTRAINT schools_wa_session_status_check
      CHECK (wa_session_status IN ('DISCONNECTED', 'WAITING_QR', 'CONNECTED'));
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'schools_server_node_id_check'
      AND conrelid = 'public.schools'::regclass
  ) THEN
    ALTER TABLE public.schools
      ADD CONSTRAINT schools_server_node_id_check
      CHECK (
        server_node_id IS NULL
        OR server_node_id ~ '^[A-Za-z0-9_-]{1,64}$'
      );
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'schools_otp_limits_check'
      AND conrelid = 'public.schools'::regclass
  ) THEN
    ALTER TABLE public.schools
      ADD CONSTRAINT schools_otp_limits_check
      CHECK (
        otp_cooldown_seconds > 0
        AND otp_daily_cap > 0
        AND otp_consecutive_failures >= 0
      );
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'otp_queue_attempt_count_check'
      AND conrelid = 'public.otp_queue'::regclass
  ) THEN
    ALTER TABLE public.otp_queue
      ADD CONSTRAINT otp_queue_attempt_count_check
      CHECK (attempt_count >= 0);
  END IF;
END;
$$;

CREATE INDEX IF NOT EXISTS schools_active_node_idx
  ON public.schools (server_node_id, id)
  WHERE is_active = TRUE;

CREATE INDEX IF NOT EXISTS otp_queue_pending_created_idx
  ON public.otp_queue (created_at, id)
  WHERE status = 'PENDING';

CREATE INDEX IF NOT EXISTS otp_queue_processing_started_idx
  ON public.otp_queue (processing_started_at)
  WHERE status = 'PROCESSING';

COMMIT;
