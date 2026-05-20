-- ======================================================
-- 🏛️ SYSTEM-WIDE INTEGRITY: FINAL RPC STANDARDIZATION
-- ======================================================

-- 1. Standardize get_inbox_summary()
CREATE OR REPLACE FUNCTION get_inbox_summary()
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
  IF jwt_phone IS NULL THEN RAISE EXCEPTION 'Not Authenticated'; END IF;
  clean_phone := REPLACE(jwt_phone, '+', '');

  RETURN QUERY
  WITH type_stats AS (
      SELECT
          m.type,
          COUNT(*) FILTER (WHERE mr.status NOT IN ('read', 'seen')) as u_count,
          MAX(mr.created_at) as max_at
      FROM message_recipients mr
      JOIN messages m ON mr.message_id = m.id
      WHERE mr.phone_number = clean_phone
      GROUP BY m.type
  )
  SELECT
      ts.type as group_type,
      ts.u_count as unread_count,
      m.body as last_message,
      m.title as last_title,
      ts.max_at as last_at,
      'Sanabil' as school_name
  FROM type_stats ts
  JOIN message_recipients mr ON mr.phone_number = clean_phone AND mr.created_at = ts.max_at
  JOIN messages m ON mr.message_id = m.id
  WHERE m.type = ts.type;
END;
$$ LANGUAGE plpgsql;

-- 2. Standardize get_my_inbox() (Secure version)
CREATE OR REPLACE FUNCTION get_my_inbox()
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
  IF jwt_phone IS NULL THEN RAISE EXCEPTION 'Not Authenticated'; END IF;
  clean_phone := REPLACE(jwt_phone, '+', '');

  RETURN QUERY
  SELECT 
    mr.id,
    mr.status,
    mr.created_at,
    m.id as message_id,
    m.title,
    m.body,
    m.type,
    m.school_id
  FROM message_recipients mr
  JOIN messages m ON mr.message_id = m.id
  WHERE mr.phone_number = clean_phone
  ORDER BY mr.created_at DESC
  LIMIT 300;
END;
$$ LANGUAGE plpgsql;

-- 3. Standardize get_my_inbox(phone_arg) (Debug version)
CREATE OR REPLACE FUNCTION get_my_inbox(phone_arg TEXT)
SET search_path = public
AS $$
BEGIN
  RETURN QUERY
  SELECT 
    mr.id,
    mr.status,
    mr.created_at,
    m.id as message_id,
    m.title,
    m.body,
    m.type,
    m.school_id
  FROM message_recipients mr
  JOIN messages m ON mr.message_id = m.id
  WHERE mr.phone_number = phone_arg
  ORDER BY mr.created_at DESC
  LIMIT 20;
END;
$$ LANGUAGE plpgsql;
