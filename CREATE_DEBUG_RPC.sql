-- 🛠️ DEBUG RPC: BYPASS RLS
-- This function runs as the Owner (merged permissions), ignoring RLS policies.
-- Usage: supabase.rpc('get_my_inbox', { phone_arg: '25263...' })

CREATE OR REPLACE FUNCTION get_my_inbox(phone_arg TEXT)
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
SECURITY DEFINER -- ⚠️ RUUNS AS ADMIN
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
  WHERE mr.parent_phone = phone_arg
  ORDER BY mr.created_at DESC
  LIMIT 20;
END;
$$ LANGUAGE plpgsql;
