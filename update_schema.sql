-- Add columns to support two-way sync with CI3

-- 1. Store the CI3 'id' (contact_id) so we can send status updates back
alter table message_recipients 
add column if not exists ci3_id text;

-- 2. Track if we have told CI3 about the latest status (Sent/Seen)
alter table message_recipients 
add column if not exists is_synced_to_ci3 boolean default false;

-- 3. Add index for performance check
create index if not exists idx_messages_sync_status 
on message_recipients(status, is_synced_to_ci3);
