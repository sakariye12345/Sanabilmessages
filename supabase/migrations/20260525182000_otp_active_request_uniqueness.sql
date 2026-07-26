WITH ranked_active AS (
  SELECT
    id,
    ROW_NUMBER() OVER (
      PARTITION BY school_id, phone
      ORDER BY created_at DESC, id DESC
    ) AS row_num
  FROM public.otp_queue
  WHERE status IN ('PENDING', 'PROCESSING')
)
UPDATE public.otp_queue q
SET
  status = 'FAILED',
  error_message = COALESCE(q.error_message, 'Superseded during active OTP uniqueness cleanup.'),
  updated_at = NOW()
FROM ranked_active r
WHERE q.id = r.id
  AND r.row_num > 1;

CREATE UNIQUE INDEX IF NOT EXISTS idx_otp_queue_one_active_request_per_parent
  ON public.otp_queue (school_id, phone)
  WHERE status IN ('PENDING', 'PROCESSING');
