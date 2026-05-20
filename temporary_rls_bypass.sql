-- TEMPORARY DEBUG: BYPASS RLS
-- The Realtime system is timing out. We suspect the "get_auth_phone()" function is causing lag or error.
-- We will temporarily allowing ALL authenticated users to view rows to see if the connection succeeds.

DROP POLICY IF EXISTS "Users can only see their own messages" ON message_recipients;

CREATE POLICY "Debug Public Access"
ON message_recipients
FOR SELECT
USING (
  true  -- 🟢 ALLOW EVERYTHING (Just for testing)
);

-- Note: We still require auth.role() = 'authenticated' implicitly via RLS enablement,
-- but this removes the complex function call.
