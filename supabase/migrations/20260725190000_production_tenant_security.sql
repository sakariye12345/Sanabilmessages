BEGIN;

-- Every recipient and trusted device must belong to one school. Keeping the
-- tenant key on these hot tables makes RLS, Realtime, and push routing explicit.
ALTER TABLE public.message_recipients
  ADD COLUMN IF NOT EXISTS school_id BIGINT;

ALTER TABLE public.user_devices
  ADD COLUMN IF NOT EXISTS school_id BIGINT;

ALTER TABLE public.student_parents
  ADD COLUMN IF NOT EXISTS school_id BIGINT;

ALTER TABLE public.otp_queue
  ADD COLUMN IF NOT EXISTS verify_attempt_count INTEGER NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS verified_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS expires_at TIMESTAMPTZ;

ALTER TABLE public.user_devices
  ALTER COLUMN fcm_token DROP NOT NULL;

UPDATE public.message_recipients mr
SET school_id = m.school_id
FROM public.messages m
WHERE m.id = mr.message_id
  AND mr.school_id IS DISTINCT FROM m.school_id;

WITH one_school_per_phone AS (
  SELECT
    phone_number,
    MIN(school_id) AS school_id
  FROM public.allowed_parents
  WHERE school_id IS NOT NULL
  GROUP BY phone_number
  HAVING COUNT(DISTINCT school_id) = 1
)
UPDATE public.user_devices ud
SET school_id = parent.school_id
FROM one_school_per_phone parent
WHERE ud.school_id IS NULL
  AND parent.phone_number = ud.phone_number;

UPDATE public.student_parents sp
SET school_id = student.school_id
FROM public.students student
WHERE student.id = sp.student_id
  AND sp.school_id IS DISTINCT FROM student.school_id;

UPDATE public.otp_queue
SET expires_at = COALESCE(created_at, NOW()) + INTERVAL '10 minutes'
WHERE expires_at IS NULL;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'message_recipients_school_id_fkey'
      AND conrelid = 'public.message_recipients'::regclass
  ) THEN
    ALTER TABLE public.message_recipients
      ADD CONSTRAINT message_recipients_school_id_fkey
      FOREIGN KEY (school_id) REFERENCES public.schools(id);
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'user_devices_school_id_fkey'
      AND conrelid = 'public.user_devices'::regclass
  ) THEN
    ALTER TABLE public.user_devices
      ADD CONSTRAINT user_devices_school_id_fkey
      FOREIGN KEY (school_id) REFERENCES public.schools(id);
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'student_parents_school_id_fkey'
      AND conrelid = 'public.student_parents'::regclass
  ) THEN
    ALTER TABLE public.student_parents
      ADD CONSTRAINT student_parents_school_id_fkey
      FOREIGN KEY (school_id) REFERENCES public.schools(id);
  END IF;
END;
$$;

ALTER TABLE public.student_parents
  DROP CONSTRAINT IF EXISTS student_parents_parent_phone_fkey;

ALTER TABLE public.message_recipients
  DROP CONSTRAINT IF EXISTS message_recipients_phone_fkey;

ALTER TABLE public.user_devices
  DROP CONSTRAINT IF EXISTS user_devices_phone_fkey;

ALTER TABLE public.allowed_parents
  DROP CONSTRAINT IF EXISTS allowed_parents_phone_key;

DROP INDEX IF EXISTS public.allowed_parents_phone_key;

DO $$
DECLARE
  constraint_row RECORD;
BEGIN
  FOR constraint_row IN
    SELECT c.conname
    FROM pg_constraint c
    WHERE c.conrelid = 'public.user_devices'::regclass
      AND c.contype = 'u'
      AND pg_get_constraintdef(c.oid) = 'UNIQUE (phone_number, device_id)'
  LOOP
    EXECUTE format(
      'ALTER TABLE public.user_devices DROP CONSTRAINT %I',
      constraint_row.conname
    );
  END LOOP;
END;
$$;

CREATE UNIQUE INDEX IF NOT EXISTS allowed_parents_school_phone_uidx
  ON public.allowed_parents (school_id, phone_number);

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'student_parents_school_phone_fkey'
      AND conrelid = 'public.student_parents'::regclass
  ) THEN
    ALTER TABLE public.student_parents
      ADD CONSTRAINT student_parents_school_phone_fkey
      FOREIGN KEY (school_id, phone_number)
      REFERENCES public.allowed_parents(school_id, phone_number)
      ON UPDATE CASCADE
      ON DELETE CASCADE;
  END IF;
END;
$$;

CREATE UNIQUE INDEX IF NOT EXISTS user_devices_school_phone_device_uidx
  ON public.user_devices (school_id, phone_number, device_id);

CREATE UNIQUE INDEX IF NOT EXISTS message_recipients_school_ci3_uidx
  ON public.message_recipients (school_id, ci3_id)
  WHERE ci3_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS messages_school_created_idx
  ON public.messages (school_id, created_at DESC, id DESC);

CREATE INDEX IF NOT EXISTS message_recipients_school_phone_created_idx
  ON public.message_recipients (school_id, phone_number, created_at DESC, id DESC);

CREATE INDEX IF NOT EXISTS message_recipients_message_idx
  ON public.message_recipients (message_id);

CREATE INDEX IF NOT EXISTS allowed_parents_school_active_phone_idx
  ON public.allowed_parents (school_id, is_active, phone_number);

CREATE INDEX IF NOT EXISTS student_parents_school_phone_idx
  ON public.student_parents (school_id, phone_number);

CREATE INDEX IF NOT EXISTS user_devices_school_phone_active_idx
  ON public.user_devices (school_id, phone_number, is_active)
  WHERE revoked_at IS NULL;

CREATE INDEX IF NOT EXISTS otp_queue_school_phone_created_idx
  ON public.otp_queue (school_id, phone, created_at DESC);

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM public.allowed_parents WHERE school_id IS NULL) THEN
    ALTER TABLE public.allowed_parents ALTER COLUMN school_id SET NOT NULL;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM public.messages WHERE school_id IS NULL) THEN
    ALTER TABLE public.messages ALTER COLUMN school_id SET NOT NULL;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM public.message_recipients
    WHERE school_id IS NULL OR message_id IS NULL OR phone_number IS NULL
  ) THEN
    ALTER TABLE public.message_recipients ALTER COLUMN school_id SET NOT NULL;
    ALTER TABLE public.message_recipients ALTER COLUMN message_id SET NOT NULL;
    ALTER TABLE public.message_recipients ALTER COLUMN phone_number SET NOT NULL;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM public.user_devices WHERE school_id IS NULL) THEN
    ALTER TABLE public.user_devices ALTER COLUMN school_id SET NOT NULL;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM public.otp_queue WHERE school_id IS NULL) THEN
    ALTER TABLE public.otp_queue ALTER COLUMN school_id SET NOT NULL;
  END IF;
END;
$$;

CREATE OR REPLACE FUNCTION public.enforce_recipient_school()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
  message_school_id BIGINT;
BEGIN
  SELECT m.school_id
  INTO message_school_id
  FROM public.messages m
  WHERE m.id = NEW.message_id;

  IF message_school_id IS NULL THEN
    RAISE EXCEPTION 'Recipient message_id % does not reference a school message', NEW.message_id;
  END IF;

  IF NEW.school_id IS NULL THEN
    NEW.school_id := message_school_id;
  ELSIF NEW.school_id <> message_school_id THEN
    RAISE EXCEPTION 'Recipient school_id must match its message school_id';
  END IF;

  NEW.phone_number := public.normalize_somali_phone_sql(NEW.phone_number);
  IF NEW.phone_number = '' THEN
    RAISE EXCEPTION 'Recipient phone number is invalid';
  END IF;

  RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS enforce_recipient_school_trigger
  ON public.message_recipients;

CREATE TRIGGER enforce_recipient_school_trigger
BEFORE INSERT OR UPDATE OF message_id, school_id, phone_number
ON public.message_recipients
FOR EACH ROW
EXECUTE FUNCTION public.enforce_recipient_school();

CREATE OR REPLACE FUNCTION public.current_parent_phone()
RETURNS TEXT
LANGUAGE plpgsql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  jwt_phone TEXT;
BEGIN
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL OR trim(jwt_phone) = '' THEN
    RAISE EXCEPTION 'Not authenticated' USING ERRCODE = '42501';
  END IF;

  RETURN public.normalize_somali_phone_sql(jwt_phone);
END;
$$;

CREATE OR REPLACE FUNCTION public.parent_has_school_access(p_school_id BIGINT)
RETURNS BOOLEAN
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT EXISTS (
    SELECT 1
    FROM public.allowed_parents ap
    JOIN public.schools s ON s.id = ap.school_id
    WHERE ap.school_id = p_school_id
      AND ap.phone_number = public.current_parent_phone()
      AND COALESCE(ap.is_active, FALSE)
      AND COALESCE(s.is_active, FALSE)
  );
$$;

-- Remove permissive historical policies before defining the production rules.
DO $$
DECLARE
  policy_row RECORD;
BEGIN
  FOR policy_row IN
    SELECT schemaname, tablename, policyname
    FROM pg_policies
    WHERE schemaname = 'public'
      AND tablename IN (
        'allowed_parents',
        'messages',
        'message_recipients',
        'user_devices'
      )
  LOOP
    EXECUTE format(
      'DROP POLICY IF EXISTS %I ON %I.%I',
      policy_row.policyname,
      policy_row.schemaname,
      policy_row.tablename
    );
  END LOOP;
END;
$$;

ALTER TABLE public.allowed_parents ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.messages ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.message_recipients ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.user_devices ENABLE ROW LEVEL SECURITY;

REVOKE ALL PRIVILEGES ON TABLE public.allowed_parents FROM anon, authenticated;
REVOKE ALL PRIVILEGES ON TABLE public.messages FROM anon, authenticated;
REVOKE ALL PRIVILEGES ON TABLE public.message_recipients FROM anon, authenticated;
REVOKE ALL PRIVILEGES ON TABLE public.user_devices FROM anon, authenticated;

-- Realtime needs SELECT, but RLS exposes only the authenticated parent's own
-- recipient events in schools where that parent is currently active.
GRANT SELECT ON TABLE public.message_recipients TO authenticated;

CREATE POLICY message_recipients_parent_realtime_select
ON public.message_recipients
FOR SELECT
TO authenticated
USING (
  phone_number = public.current_parent_phone()
  AND public.parent_has_school_access(school_id)
);

CREATE OR REPLACE FUNCTION public.get_my_inbox(p_school_id BIGINT)
RETURNS TABLE (
  id BIGINT,
  status TEXT,
  created_at TIMESTAMPTZ,
  message_id BIGINT,
  title TEXT,
  body TEXT,
  type TEXT,
  school_id BIGINT
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  clean_phone TEXT;
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  clean_phone := public.current_parent_phone();

  RETURN QUERY
  SELECT
    mr.id,
    mr.status,
    mr.created_at,
    m.id,
    m.title,
    m.body,
    m.type,
    m.school_id
  FROM public.message_recipients mr
  JOIN public.messages m ON m.id = mr.message_id
  WHERE mr.school_id = p_school_id
    AND m.school_id = p_school_id
    AND mr.phone_number = clean_phone
  ORDER BY mr.created_at DESC, mr.id DESC
  LIMIT 300;
END;
$$;

CREATE OR REPLACE FUNCTION public.get_inbox_summary(p_school_id BIGINT)
RETURNS TABLE (
  group_type TEXT,
  unread_count BIGINT,
  last_message TEXT,
  last_title TEXT,
  last_at TIMESTAMPTZ,
  school_name TEXT
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  clean_phone TEXT;
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  clean_phone := public.current_parent_phone();

  RETURN QUERY
  WITH filtered AS (
    SELECT
      mr.id,
      mr.status,
      mr.created_at,
      m.type,
      m.title,
      m.body
    FROM public.message_recipients mr
    JOIN public.messages m ON m.id = mr.message_id
    WHERE mr.school_id = p_school_id
      AND m.school_id = p_school_id
      AND mr.phone_number = clean_phone
  ),
  grouped AS (
    SELECT
      f.type,
      COUNT(*) FILTER (WHERE f.status NOT IN ('read', 'seen')) AS unread_count,
      MAX(f.created_at) AS last_at
    FROM filtered f
    GROUP BY f.type
  )
  SELECT
    grouped.type,
    grouped.unread_count,
    latest.body,
    latest.title,
    grouped.last_at,
    school.name
  FROM grouped
  JOIN LATERAL (
    SELECT f.body, f.title
    FROM filtered f
    WHERE f.type = grouped.type
    ORDER BY f.created_at DESC, f.id DESC
    LIMIT 1
  ) latest ON TRUE
  JOIN public.schools school ON school.id = p_school_id
  ORDER BY grouped.last_at DESC;
END;
$$;

CREATE OR REPLACE FUNCTION public.get_thread_messages(
  p_school_id BIGINT,
  p_type TEXT
)
RETURNS TABLE (
  id BIGINT,
  created_at TIMESTAMPTZ,
  status TEXT,
  phone_number TEXT,
  message_id BIGINT,
  title TEXT,
  body TEXT,
  type TEXT,
  school_id BIGINT
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  clean_phone TEXT;
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  clean_phone := public.current_parent_phone();

  RETURN QUERY
  SELECT
    mr.id,
    mr.created_at,
    mr.status,
    mr.phone_number,
    m.id,
    m.title,
    m.body,
    m.type,
    m.school_id
  FROM public.message_recipients mr
  JOIN public.messages m ON m.id = mr.message_id
  WHERE mr.school_id = p_school_id
    AND m.school_id = p_school_id
    AND mr.phone_number = clean_phone
    AND m.type = p_type
  ORDER BY mr.created_at DESC, mr.id DESC;
END;
$$;

CREATE OR REPLACE FUNCTION public.get_message_detail(
  p_school_id BIGINT,
  p_message_id BIGINT
)
RETURNS TABLE (
  id BIGINT,
  school_id BIGINT,
  type TEXT,
  title TEXT,
  body TEXT,
  created_at TIMESTAMPTZ
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  clean_phone TEXT;
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  clean_phone := public.current_parent_phone();

  RETURN QUERY
  SELECT
    m.id,
    m.school_id,
    m.type,
    m.title,
    m.body,
    m.created_at
  FROM public.messages m
  WHERE m.id = p_message_id
    AND m.school_id = p_school_id
    AND EXISTS (
      SELECT 1
      FROM public.message_recipients mr
      WHERE mr.message_id = m.id
        AND mr.school_id = p_school_id
        AND mr.phone_number = clean_phone
    )
  LIMIT 1;
END;
$$;

CREATE OR REPLACE FUNCTION public.get_my_profile(p_school_id BIGINT)
RETURNS TABLE (
  id BIGINT,
  school_id BIGINT,
  parent_id BIGINT,
  parent_name TEXT,
  phone_number TEXT,
  is_active BOOLEAN
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  RETURN QUERY
  SELECT
    ap.id,
    ap.school_id,
    ap.parent_id,
    ap.parent_name,
    ap.phone_number,
    COALESCE(ap.is_active, FALSE)
  FROM public.allowed_parents ap
  WHERE ap.school_id = p_school_id
    AND ap.phone_number = public.current_parent_phone()
    AND COALESCE(ap.is_active, FALSE)
  LIMIT 1;
END;
$$;

CREATE OR REPLACE FUNCTION public.mark_my_recipients(
  p_school_id BIGINT,
  p_recipient_ids BIGINT[],
  p_status TEXT
)
RETURNS INTEGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  clean_phone TEXT;
  changed_count INTEGER;
  next_status TEXT := lower(trim(COALESCE(p_status, '')));
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  IF next_status NOT IN ('sent', 'seen') THEN
    RAISE EXCEPTION 'Unsupported recipient status';
  END IF;

  IF COALESCE(array_length(p_recipient_ids, 1), 0) = 0 THEN
    RETURN 0;
  END IF;

  clean_phone := public.current_parent_phone();

  UPDATE public.message_recipients mr
  SET status = next_status,
      seen_at = CASE
        WHEN next_status = 'seen' THEN COALESCE(mr.seen_at, NOW())
        ELSE mr.seen_at
      END
  WHERE mr.id = ANY (p_recipient_ids)
    AND mr.school_id = p_school_id
    AND mr.phone_number = clean_phone
    AND (
      (next_status = 'sent' AND mr.status = 'pending')
      OR (next_status = 'seen' AND mr.status IS DISTINCT FROM 'seen')
    );

  GET DIAGNOSTICS changed_count = ROW_COUNT;
  RETURN changed_count;
END;
$$;

CREATE OR REPLACE FUNCTION public.register_my_device(
  p_school_id BIGINT,
  p_device_id TEXT,
  p_device_name TEXT DEFAULT NULL,
  p_platform TEXT DEFAULT NULL,
  p_fcm_token TEXT DEFAULT NULL,
  p_app_variant TEXT DEFAULT NULL,
  p_mark_login BOOLEAN DEFAULT FALSE
)
RETURNS TABLE (
  id BIGINT,
  school_id BIGINT,
  phone_number TEXT,
  device_id TEXT,
  is_active BOOLEAN,
  trusted_at TIMESTAMPTZ,
  revoked_at TIMESTAMPTZ,
  last_seen_at TIMESTAMPTZ,
  last_login_at TIMESTAMPTZ
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  clean_phone TEXT;
  now_ts TIMESTAMPTZ := NOW();
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  IF COALESCE(trim(p_device_id), '') = '' THEN
    RAISE EXCEPTION 'Device ID is required';
  END IF;

  clean_phone := public.current_parent_phone();

  RETURN QUERY
  INSERT INTO public.user_devices AS existing (
    school_id,
    phone_number,
    device_id,
    device_name,
    platform,
    fcm_token,
    app_variant,
    is_active,
    trusted_at,
    revoked_at,
    last_seen_at,
    last_login_at,
    updated_at
  )
  VALUES (
    p_school_id,
    clean_phone,
    trim(p_device_id),
    NULLIF(trim(COALESCE(p_device_name, '')), ''),
    NULLIF(trim(COALESCE(p_platform, '')), ''),
    NULLIF(trim(COALESCE(p_fcm_token, '')), ''),
    NULLIF(trim(COALESCE(p_app_variant, '')), ''),
    TRUE,
    now_ts,
    NULL,
    now_ts,
    CASE WHEN p_mark_login THEN now_ts ELSE NULL END,
    now_ts
  )
  ON CONFLICT (school_id, phone_number, device_id)
  DO UPDATE SET
    device_name = COALESCE(EXCLUDED.device_name, existing.device_name),
    platform = COALESCE(EXCLUDED.platform, existing.platform),
    fcm_token = COALESCE(EXCLUDED.fcm_token, existing.fcm_token),
    app_variant = COALESCE(EXCLUDED.app_variant, existing.app_variant),
    is_active = TRUE,
    trusted_at = COALESCE(existing.trusted_at, now_ts),
    revoked_at = NULL,
    last_seen_at = now_ts,
    last_login_at = CASE
      WHEN p_mark_login THEN now_ts
      ELSE existing.last_login_at
    END,
    updated_at = now_ts
  RETURNING
    existing.id,
    existing.school_id,
    existing.phone_number,
    existing.device_id,
    existing.is_active,
    existing.trusted_at,
    existing.revoked_at,
    existing.last_seen_at,
    existing.last_login_at;
END;
$$;

CREATE OR REPLACE FUNCTION public.get_my_device_trust(
  p_school_id BIGINT,
  p_device_id TEXT
)
RETURNS TABLE (
  id BIGINT,
  school_id BIGINT,
  phone_number TEXT,
  device_id TEXT,
  device_name TEXT,
  platform TEXT,
  app_variant TEXT,
  is_active BOOLEAN,
  trusted_at TIMESTAMPTZ,
  revoked_at TIMESTAMPTZ,
  last_seen_at TIMESTAMPTZ,
  last_login_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  RETURN QUERY
  SELECT
    ud.id,
    ud.school_id,
    ud.phone_number,
    ud.device_id,
    ud.device_name,
    ud.platform,
    ud.app_variant,
    COALESCE(ud.is_active, FALSE),
    ud.trusted_at,
    ud.revoked_at,
    ud.last_seen_at,
    ud.last_login_at,
    ud.created_at
  FROM public.user_devices ud
  WHERE ud.school_id = p_school_id
    AND ud.phone_number = public.current_parent_phone()
    AND ud.device_id = trim(p_device_id)
  LIMIT 1;
END;
$$;

CREATE OR REPLACE FUNCTION public.list_my_devices(p_school_id BIGINT)
RETURNS TABLE (
  id BIGINT,
  school_id BIGINT,
  phone_number TEXT,
  device_id TEXT,
  device_name TEXT,
  platform TEXT,
  app_variant TEXT,
  is_active BOOLEAN,
  trusted_at TIMESTAMPTZ,
  revoked_at TIMESTAMPTZ,
  last_seen_at TIMESTAMPTZ,
  last_login_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  RETURN QUERY
  SELECT
    ud.id,
    ud.school_id,
    ud.phone_number,
    ud.device_id,
    ud.device_name,
    ud.platform,
    ud.app_variant,
    COALESCE(ud.is_active, FALSE),
    ud.trusted_at,
    ud.revoked_at,
    ud.last_seen_at,
    ud.last_login_at,
    ud.created_at
  FROM public.user_devices ud
  WHERE ud.school_id = p_school_id
    AND ud.phone_number = public.current_parent_phone()
  ORDER BY COALESCE(ud.last_seen_at, ud.created_at) DESC, ud.id DESC;
END;
$$;

CREATE OR REPLACE FUNCTION public.revoke_my_device(
  p_school_id BIGINT,
  p_device_id TEXT
)
RETURNS TABLE (
  id BIGINT,
  school_id BIGINT,
  phone_number TEXT,
  device_id TEXT,
  is_active BOOLEAN,
  revoked_at TIMESTAMPTZ
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
  IF NOT public.parent_has_school_access(p_school_id) THEN
    RAISE EXCEPTION 'Parent is not active in this school' USING ERRCODE = '42501';
  END IF;

  RETURN QUERY
  UPDATE public.user_devices ud
  SET is_active = FALSE,
      revoked_at = NOW(),
      updated_at = NOW()
  WHERE ud.school_id = p_school_id
    AND ud.phone_number = public.current_parent_phone()
    AND ud.device_id = trim(p_device_id)
    AND ud.revoked_at IS NULL
  RETURNING
    ud.id,
    ud.school_id,
    ud.phone_number,
    ud.device_id,
    COALESCE(ud.is_active, FALSE),
    ud.revoked_at;
END;
$$;

CREATE OR REPLACE FUNCTION public.consume_school_otp(
  p_school_id BIGINT,
  p_phone TEXT,
  p_code TEXT,
  p_max_attempts INTEGER DEFAULT 5
)
RETURNS TABLE (
  otp_id BIGINT,
  result_status TEXT,
  attempts_remaining INTEGER,
  expires_at TIMESTAMPTZ
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  clean_phone TEXT := public.normalize_somali_phone_sql(p_phone);
  otp_row public.otp_queue%ROWTYPE;
  next_attempt_count INTEGER;
  effective_expiry TIMESTAMPTZ;
BEGIN
  IF auth.role() <> 'service_role' THEN
    RAISE EXCEPTION 'Service role required' USING ERRCODE = '42501';
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM public.allowed_parents ap
    JOIN public.schools s ON s.id = ap.school_id
    WHERE ap.school_id = p_school_id
      AND ap.phone_number = clean_phone
      AND COALESCE(ap.is_active, FALSE)
      AND COALESCE(s.is_active, FALSE)
  ) THEN
    RETURN QUERY SELECT NULL::BIGINT, 'parent_not_allowed'::TEXT, 0, NULL::TIMESTAMPTZ;
    RETURN;
  END IF;

  SELECT q.*
  INTO otp_row
  FROM public.otp_queue q
  WHERE q.school_id = p_school_id
    AND q.phone = clean_phone
    AND q.status IN ('PENDING', 'PROCESSING', 'SENT')
  ORDER BY q.created_at DESC, q.id DESC
  LIMIT 1
  FOR UPDATE;

  IF NOT FOUND THEN
    RETURN QUERY SELECT NULL::BIGINT, 'missing_otp'::TEXT, 0, NULL::TIMESTAMPTZ;
    RETURN;
  END IF;

  effective_expiry := COALESCE(
    otp_row.expires_at,
    COALESCE(otp_row.created_at, NOW()) + INTERVAL '10 minutes'
  );

  IF effective_expiry <= NOW() THEN
    UPDATE public.otp_queue
    SET status = 'FAILED',
        error_message = 'OTP expired before verification.',
        updated_at = NOW()
    WHERE id = otp_row.id;

    RETURN QUERY
    SELECT otp_row.id, 'expired'::TEXT, 0, effective_expiry;
    RETURN;
  END IF;

  IF COALESCE(otp_row.verify_attempt_count, 0) >= GREATEST(p_max_attempts, 1) THEN
    UPDATE public.otp_queue
    SET status = 'FAILED',
        error_message = 'Maximum OTP verification attempts reached.',
        updated_at = NOW()
    WHERE id = otp_row.id;

    RETURN QUERY
    SELECT otp_row.id, 'max_attempts'::TEXT, 0, effective_expiry;
    RETURN;
  END IF;

  IF otp_row.code <> trim(COALESCE(p_code, '')) THEN
    next_attempt_count := COALESCE(otp_row.verify_attempt_count, 0) + 1;

    UPDATE public.otp_queue
    SET verify_attempt_count = next_attempt_count,
        status = CASE
          WHEN next_attempt_count >= GREATEST(p_max_attempts, 1) THEN 'FAILED'
          ELSE status
        END,
        error_message = CASE
          WHEN next_attempt_count >= GREATEST(p_max_attempts, 1)
            THEN 'Maximum OTP verification attempts reached.'
          ELSE 'Invalid OTP verification attempt.'
        END,
        updated_at = NOW()
    WHERE id = otp_row.id;

    RETURN QUERY
    SELECT
      otp_row.id,
      CASE
        WHEN next_attempt_count >= GREATEST(p_max_attempts, 1)
          THEN 'max_attempts'::TEXT
        ELSE 'invalid_code'::TEXT
      END,
      GREATEST(GREATEST(p_max_attempts, 1) - next_attempt_count, 0),
      effective_expiry;
    RETURN;
  END IF;

  UPDATE public.otp_queue
  SET status = 'VERIFIED',
      verified_at = NOW(),
      error_message = NULL,
      updated_at = NOW()
  WHERE id = otp_row.id
    AND status IN ('PENDING', 'PROCESSING', 'SENT');

  IF NOT FOUND THEN
    RETURN QUERY SELECT otp_row.id, 'already_consumed'::TEXT, 0, effective_expiry;
    RETURN;
  END IF;

  RETURN QUERY
  SELECT
    otp_row.id,
    'verified'::TEXT,
    GREATEST(GREATEST(p_max_attempts, 1) - COALESCE(otp_row.verify_attempt_count, 0), 0),
    effective_expiry;
END;
$$;

CREATE OR REPLACE FUNCTION public.replace_school_allowed_parents(
  p_school_id BIGINT,
  p_parents JSONB
)
RETURNS TABLE (
  upserted_count BIGINT,
  deactivated_count BIGINT
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

  IF jsonb_typeof(COALESCE(p_parents, '[]'::JSONB)) <> 'array' THEN
    RAISE EXCEPTION 'p_parents must be a JSON array';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM public.schools s
    WHERE s.id = p_school_id
      AND COALESCE(s.is_active, FALSE)
  ) THEN
    RAISE EXCEPTION 'Active school not found';
  END IF;

  RETURN QUERY
  WITH raw_payload AS (
    SELECT
      NULLIF(item ->> 'parent_id', '')::BIGINT AS parent_id,
      NULLIF(trim(item ->> 'parent_name'), '') AS parent_name,
      public.normalize_somali_phone_sql(item ->> 'phone_number') AS phone_number,
      COALESCE((item ->> 'is_active')::BOOLEAN, TRUE) AS is_active,
      ordinality
    FROM jsonb_array_elements(COALESCE(p_parents, '[]'::JSONB))
      WITH ORDINALITY AS payload(item, ordinality)
  ),
  normalized_payload AS (
    SELECT DISTINCT ON (phone_number)
      parent_id,
      parent_name,
      phone_number,
      is_active
    FROM raw_payload
    WHERE phone_number <> ''
    ORDER BY phone_number, ordinality DESC
  ),
  deactivated AS (
    UPDATE public.allowed_parents ap
    SET is_active = FALSE,
        last_sync_at = now_ts
    WHERE ap.school_id = p_school_id
      AND COALESCE(ap.is_active, FALSE)
      AND NOT EXISTS (
        SELECT 1
        FROM normalized_payload payload
        WHERE payload.phone_number = ap.phone_number
          AND payload.is_active
      )
    RETURNING ap.id
  ),
  upserted AS (
    INSERT INTO public.allowed_parents AS existing (
      school_id,
      parent_id,
      parent_name,
      phone_number,
      is_active,
      last_sync_at
    )
    SELECT
      p_school_id,
      payload.parent_id,
      payload.parent_name,
      payload.phone_number,
      payload.is_active,
      now_ts
    FROM normalized_payload payload
    ON CONFLICT (school_id, phone_number)
    DO UPDATE SET
      parent_id = EXCLUDED.parent_id,
      parent_name = EXCLUDED.parent_name,
      is_active = EXCLUDED.is_active,
      last_sync_at = EXCLUDED.last_sync_at
    RETURNING existing.id
  )
  SELECT
    (SELECT COUNT(*) FROM upserted),
    (SELECT COUNT(*) FROM deactivated);
END;
$$;

-- Historical phone-only functions are not safe once the same phone can belong
-- to more than one tenant. Keep them available only to service-role during the
-- coordinated rollout; the mobile app uses the school-scoped overloads above.
REVOKE ALL ON FUNCTION public.get_my_inbox() FROM PUBLIC, anon, authenticated;
REVOKE ALL ON FUNCTION public.get_inbox_summary() FROM PUBLIC, anon, authenticated;
REVOKE ALL ON FUNCTION public.get_thread_messages(TEXT) FROM PUBLIC, anon, authenticated;
REVOKE ALL ON FUNCTION public.get_message_detail(BIGINT) FROM PUBLIC, anon, authenticated;
REVOKE ALL ON FUNCTION public.get_my_profile() FROM PUBLIC, anon, authenticated;
REVOKE ALL ON FUNCTION public.register_my_device(TEXT, TEXT, TEXT, TEXT, TEXT, BOOLEAN)
  FROM PUBLIC, anon, authenticated;
REVOKE ALL ON FUNCTION public.get_my_device_trust(TEXT)
  FROM PUBLIC, anon, authenticated;
REVOKE ALL ON FUNCTION public.list_my_devices()
  FROM PUBLIC, anon, authenticated;
REVOKE ALL ON FUNCTION public.revoke_my_device(TEXT)
  FROM PUBLIC, anon, authenticated;

REVOKE ALL ON FUNCTION public.current_parent_phone() FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.parent_has_school_access(BIGINT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.get_my_inbox(BIGINT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.get_inbox_summary(BIGINT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.get_thread_messages(BIGINT, TEXT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.get_message_detail(BIGINT, BIGINT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.get_my_profile(BIGINT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.mark_my_recipients(BIGINT, BIGINT[], TEXT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.register_my_device(BIGINT, TEXT, TEXT, TEXT, TEXT, TEXT, BOOLEAN)
  FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.get_my_device_trust(BIGINT, TEXT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.list_my_devices(BIGINT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.revoke_my_device(BIGINT, TEXT) FROM PUBLIC, anon;
REVOKE ALL ON FUNCTION public.consume_school_otp(BIGINT, TEXT, TEXT, INTEGER)
  FROM PUBLIC, anon, authenticated;
REVOKE ALL ON FUNCTION public.replace_school_allowed_parents(BIGINT, JSONB)
  FROM PUBLIC, anon, authenticated;

GRANT EXECUTE ON FUNCTION public.current_parent_phone() TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.parent_has_school_access(BIGINT) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_my_inbox(BIGINT) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_inbox_summary(BIGINT) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_thread_messages(BIGINT, TEXT) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_message_detail(BIGINT, BIGINT) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_my_profile(BIGINT) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.mark_my_recipients(BIGINT, BIGINT[], TEXT)
  TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.register_my_device(BIGINT, TEXT, TEXT, TEXT, TEXT, TEXT, BOOLEAN)
  TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_my_device_trust(BIGINT, TEXT)
  TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.list_my_devices(BIGINT)
  TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.revoke_my_device(BIGINT, TEXT)
  TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.consume_school_otp(BIGINT, TEXT, TEXT, INTEGER)
  TO service_role;
GRANT EXECUTE ON FUNCTION public.replace_school_allowed_parents(BIGINT, JSONB)
  TO service_role;

DO $$
BEGIN
  IF to_regprocedure('public.get_user_id_by_phone(text)') IS NOT NULL THEN
    REVOKE ALL ON FUNCTION public.get_user_id_by_phone(TEXT)
      FROM PUBLIC, anon, authenticated;
    GRANT EXECUTE ON FUNCTION public.get_user_id_by_phone(TEXT) TO service_role;
  END IF;
END;
$$;

COMMIT;
