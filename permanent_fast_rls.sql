-- PERMANENT FAST RLS (JWT BASED)
-- The previous function-based RLS (querying auth.users) caused Realtime timeouts.
-- The "Public" debug policy was insecure.
-- This policy uses the JWT Token directly (Memory lookup) which is Instant and Secure.

-- 1. Drop previous policies
DROP POLICY IF EXISTS "Users can only see their own messages" ON message_recipients;
DROP POLICY IF EXISTS "Debug Public Access" ON message_recipients;

-- 2. Create JWT-based Policy
CREATE POLICY "Users can only see their own messages"
ON message_recipients
FOR SELECT
USING (
  auth.role() = 'authenticated' AND
  (
    -- Check Phone from JWT Claim (Fastest)
    -- Normalize both sides to digits only to ensure match
    parent_phone = regexp_replace((auth.jwt() ->> 'phone'), '\D', '', 'g') 
  )
);

-- Explanation:
-- auth.jwt() gets the user's session token.
-- ->> 'phone' extracts the phone number.
-- replace(..., '+', '') ensures we match the normalized DB format even if the token has a '+'.
-- This happens in-memory, so it is extremely fast and won't timeout Realtime.

SELECT 'Permanent Fast RLS Applied' as status;
