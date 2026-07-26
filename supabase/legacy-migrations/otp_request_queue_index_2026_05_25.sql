CREATE INDEX IF NOT EXISTS idx_otp_queue_school_phone_status_created
  ON public.otp_queue (school_id, phone, status, created_at DESC);
-- LEGACY REFERENCE: not part of the deployable migration chain.
