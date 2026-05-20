-- 📊 INBOX SUMMARY RPC
-- Returns ONE row per Message Type.
-- Guarantees that "Absence" is shown even if there are 1000 "Fee" messages.

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
  -- 1. Auth & Normalize
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
      'Sanabil' as school_name -- Static for now, or join if needed
  FROM type_stats ts
  -- Join back to get the BODY of the latest message
  JOIN message_recipients mr ON mr.phone_number = clean_phone AND mr.created_at = ts.max_at
  JOIN messages m ON mr.message_id = m.id
  WHERE m.type = ts.type; -- Ensure correct type match

END;
$$ LANGUAGE plpgsql;
