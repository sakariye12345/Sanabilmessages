ALTER TABLE public.schools
  ADD COLUMN IF NOT EXISTS wa_session_status TEXT DEFAULT 'DISCONNECTED',
  ADD COLUMN IF NOT EXISTS server_node_id TEXT DEFAULT 'VPS-1';

ALTER TABLE public.otp_queue
  ADD COLUMN IF NOT EXISTS school_id BIGINT REFERENCES public.schools(id),
  ADD COLUMN IF NOT EXISTS attempt_count INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS error_message TEXT,
  ADD COLUMN IF NOT EXISTS processing_started_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS sent_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS provider TEXT DEFAULT 'whatsapp',
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ DEFAULT NOW();

DO $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM information_schema.table_constraints
    WHERE table_schema = 'public'
      AND table_name = 'otp_queue'
      AND constraint_name = 'otp_queue_status_check'
  ) THEN
    ALTER TABLE public.otp_queue DROP CONSTRAINT otp_queue_status_check;
  END IF;
END $$;

ALTER TABLE public.otp_queue
  ADD CONSTRAINT otp_queue_status_check
  CHECK (status IN ('PENDING', 'PROCESSING', 'SENT', 'FAILED'));

UPDATE public.otp_queue
SET provider = COALESCE(provider, 'whatsapp'),
    updated_at = COALESCE(updated_at, created_at),
    attempt_count = COALESCE(attempt_count, 0)
WHERE TRUE;

ALTER TABLE public.otp_logs
  ADD COLUMN IF NOT EXISTS school_id BIGINT REFERENCES public.schools(id),
  ADD COLUMN IF NOT EXISTS provider TEXT DEFAULT 'whatsapp',
  ADD COLUMN IF NOT EXISTS error_message TEXT,
  ADD COLUMN IF NOT EXISTS sent_at TIMESTAMPTZ;

CREATE INDEX IF NOT EXISTS idx_otp_queue_status_school ON public.otp_queue(status, school_id);
CREATE INDEX IF NOT EXISTS idx_otp_queue_created_at ON public.otp_queue(created_at);
CREATE INDEX IF NOT EXISTS idx_otp_logs_school_id ON public.otp_logs(school_id);
