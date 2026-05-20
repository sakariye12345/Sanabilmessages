-- 🛡️ SECURE RPC: BYPASS RLS (Safe Mode)
-- Instead of accepting 'phone_arg' (which can be faked),
-- we extract the phone number directly from the Authenticated User's JWT.

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
SECURITY DEFINER -- Runs as Admin to bypass RLS
SET search_path = public -- Security Best Practice
AS $$
DECLARE
  jwt_phone TEXT;
  clean_phone TEXT;
BEGIN
  -- 1. Get Phone from Auth context (JWT)
  jwt_phone := auth.jwt() ->> 'phone';

  -- 2. Validate Auth
  IF jwt_phone IS NULL THEN
    RAISE EXCEPTION 'Not Authenticated';
  END IF;

  -- 3. Normalize (Remove '+' if present, to match DB '252...')
  clean_phone := REPLACE(jwt_phone, '+', '');

  -- 4. Return Data for THIS user only
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
  WHERE mr.parent_phone = clean_phone
  ORDER BY mr.created_at DESC
  LIMIT 300; -- Increased to prevent "disappearing" chats
END;
$$ LANGUAGE plpgsql;
