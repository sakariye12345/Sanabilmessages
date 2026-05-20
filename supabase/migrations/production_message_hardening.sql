CREATE OR REPLACE FUNCTION public.normalize_somali_phone_sql(raw_phone TEXT)
RETURNS TEXT
LANGUAGE plpgsql
IMMUTABLE
AS $$
DECLARE
  digits TEXT;
BEGIN
  digits := regexp_replace(COALESCE(raw_phone, ''), '\D', '', 'g');

  IF digits = '' THEN
    RETURN '';
  END IF;

  IF digits LIKE '252%' AND length(digits) >= 12 THEN
    RETURN digits;
  END IF;

  IF left(digits, 1) = '0' AND length(digits) >= 9 THEN
    digits := substr(digits, 2);
  END IF;

  IF length(digits) = 9 THEN
    RETURN '252' || digits;
  END IF;

  RETURN digits;
END;
$$;

UPDATE public.allowed_parents
SET phone_number = public.normalize_somali_phone_sql(phone_number)
WHERE phone_number IS NOT NULL
  AND phone_number <> public.normalize_somali_phone_sql(phone_number);

UPDATE public.message_recipients
SET phone_number = public.normalize_somali_phone_sql(phone_number)
WHERE phone_number IS NOT NULL
  AND phone_number <> public.normalize_somali_phone_sql(phone_number);

UPDATE public.user_devices
SET phone_number = public.normalize_somali_phone_sql(phone_number)
WHERE phone_number IS NOT NULL
  AND phone_number <> public.normalize_somali_phone_sql(phone_number);

UPDATE public.otp_queue
SET phone = public.normalize_somali_phone_sql(phone)
WHERE phone IS NOT NULL
  AND phone <> public.normalize_somali_phone_sql(phone);

DROP FUNCTION IF EXISTS public.get_my_inbox(TEXT);

CREATE OR REPLACE FUNCTION public.get_my_inbox()
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
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  jwt_phone TEXT;
  clean_phone TEXT;
BEGIN
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL THEN
    RAISE EXCEPTION 'Not Authenticated';
  END IF;

  clean_phone := public.normalize_somali_phone_sql(jwt_phone);

  RETURN QUERY
  SELECT
    mr.id,
    mr.status,
    mr.created_at,
    m.id AS message_id,
    m.title,
    m.body,
    m.type,
    m.school_id
  FROM public.message_recipients mr
  JOIN public.messages m ON m.id = mr.message_id
  WHERE mr.phone_number = clean_phone
  ORDER BY mr.created_at DESC, mr.id DESC
  LIMIT 300;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION public.get_inbox_summary()
RETURNS TABLE (
  group_type TEXT,
  unread_count BIGINT,
  last_message TEXT,
  last_title TEXT,
  last_at TIMESTAMPTZ,
  school_name TEXT
)
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  jwt_phone TEXT;
  clean_phone TEXT;
BEGIN
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL THEN
    RAISE EXCEPTION 'Not Authenticated';
  END IF;

  clean_phone := public.normalize_somali_phone_sql(jwt_phone);

  RETURN QUERY
  WITH filtered AS (
    SELECT
      mr.id,
      mr.status,
      mr.created_at,
      m.id AS message_id,
      m.type,
      m.title,
      m.body,
      m.school_id
    FROM public.message_recipients mr
    JOIN public.messages m ON m.id = mr.message_id
    WHERE mr.phone_number = clean_phone
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
    g.type AS group_type,
    g.unread_count,
    latest.body AS last_message,
    latest.title AS last_title,
    g.last_at,
    'Sanabil'::TEXT AS school_name
  FROM grouped g
  JOIN LATERAL (
    SELECT f.body, f.title
    FROM filtered f
    WHERE f.type = g.type
    ORDER BY f.created_at DESC, f.id DESC
    LIMIT 1
  ) latest ON TRUE
  ORDER BY g.last_at DESC;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION public.get_thread_messages(p_type TEXT)
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
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  jwt_phone TEXT;
  clean_phone TEXT;
BEGIN
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL THEN
    RAISE EXCEPTION 'Not Authenticated';
  END IF;

  clean_phone := public.normalize_somali_phone_sql(jwt_phone);

  RETURN QUERY
  SELECT
    mr.id,
    mr.created_at,
    mr.status,
    mr.phone_number,
    m.id AS message_id,
    m.title,
    m.body,
    m.type,
    m.school_id
  FROM public.message_recipients mr
  JOIN public.messages m ON m.id = mr.message_id
  WHERE mr.phone_number = clean_phone
    AND m.type = p_type
  ORDER BY mr.created_at DESC, mr.id DESC;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION public.get_message_detail(p_message_id BIGINT)
RETURNS TABLE (
  id BIGINT,
  school_id BIGINT,
  type TEXT,
  title TEXT,
  body TEXT,
  created_at TIMESTAMPTZ
)
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  jwt_phone TEXT;
  clean_phone TEXT;
BEGIN
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL THEN
    RAISE EXCEPTION 'Not Authenticated';
  END IF;

  clean_phone := public.normalize_somali_phone_sql(jwt_phone);

  RETURN QUERY
  SELECT DISTINCT
    m.id,
    m.school_id,
    m.type,
    m.title,
    m.body,
    m.created_at
  FROM public.message_recipients mr
  JOIN public.messages m ON m.id = mr.message_id
  WHERE mr.phone_number = clean_phone
    AND m.id = p_message_id
  ORDER BY m.id DESC
  LIMIT 1;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION public.get_my_profile()
RETURNS TABLE (
  id BIGINT,
  school_id BIGINT,
  parent_id BIGINT,
  parent_name TEXT,
  phone_number TEXT,
  is_active BOOLEAN
)
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  jwt_phone TEXT;
  clean_phone TEXT;
BEGIN
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL THEN
    RAISE EXCEPTION 'Not Authenticated';
  END IF;

  clean_phone := public.normalize_somali_phone_sql(jwt_phone);

  RETURN QUERY
  SELECT
    ap.id,
    ap.school_id,
    ap.parent_id,
    ap.parent_name,
    ap.phone_number,
    ap.is_active
  FROM public.allowed_parents ap
  WHERE ap.phone_number = clean_phone
  LIMIT 1;
END;
$$ LANGUAGE plpgsql;

REVOKE ALL ON FUNCTION public.get_my_inbox() FROM PUBLIC;
REVOKE ALL ON FUNCTION public.get_inbox_summary() FROM PUBLIC;
REVOKE ALL ON FUNCTION public.get_thread_messages(TEXT) FROM PUBLIC;
REVOKE ALL ON FUNCTION public.get_message_detail(BIGINT) FROM PUBLIC;
REVOKE ALL ON FUNCTION public.get_my_profile() FROM PUBLIC;

GRANT EXECUTE ON FUNCTION public.get_my_inbox() TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_inbox_summary() TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_thread_messages(TEXT) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_message_detail(BIGINT) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.get_my_profile() TO authenticated, service_role;
