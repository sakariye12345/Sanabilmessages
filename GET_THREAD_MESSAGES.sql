-- ==========================================
-- 🔒 SECURE RPC: GET_THREAD_MESSAGES
-- ==========================================
-- Fetch thread-specific messages using JWT Auth, preventing spoofing and bypassing complex RLS issues.

CREATE OR REPLACE FUNCTION get_thread_messages(p_type TEXT)
RETURNS TABLE (
  id BIGINT,
  created_at TIMESTAMPTZ,
  status TEXT,
  phone_number TEXT,     -- Ensures React Native gets the same alias it expects
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
  -- 1. Extract phone from verified JWT
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL THEN 
    RAISE EXCEPTION 'Not Authenticated'; 
  END IF;
  
  clean_phone := REPLACE(jwt_phone, '+', '');

  -- 2. Return Thread Data 
  RETURN QUERY
  SELECT
      mr.id,
      mr.created_at,
      mr.status,
      mr.phone_number,
      m.id as message_id,
      m.title,
      m.body,
      m.type,
      m.school_id
  FROM message_recipients mr
  JOIN messages m ON mr.message_id = m.id
  WHERE mr.phone_number = clean_phone AND m.type = p_type
  ORDER BY mr.created_at DESC;

END;
$$ LANGUAGE plpgsql;
