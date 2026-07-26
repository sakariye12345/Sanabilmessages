-- Enable RLS on the table (if not already enabled)
ALTER TABLE message_recipients ENABLE ROW LEVEL SECURITY;

-- Drop existing policy if any to avoid conflicts
DROP POLICY IF EXISTS "Users can only see their own messages" ON message_recipients;

-- Create restrictive policy based on auth.uid() matching parent_phone
-- This assumes standard Supabase user management where phone is stored in auth.users
CREATE POLICY "Users can only see their own messages"
ON message_recipients
FOR SELECT
USING (
  auth.role() = 'authenticated' AND
  parent_phone = (
    SELECT phone 
    FROM auth.users 
    WHERE id = auth.uid()
  )
);
-- LEGACY REFERENCE: not part of the deployable migration chain.
