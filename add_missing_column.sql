-- The Root Cause: The column 'seen_at' was missing from the database!
-- This script adds it.

alter table message_recipients 
add column if not exists seen_at timestamptz;

-- Refresh the schema cache (just in case)
notify pgrst, 'reload config';
