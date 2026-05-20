-- The check constraint was missing 'seen'!
-- We need to drop the old constraint and add a corrected one.

alter table message_recipients 
drop constraint if exists message_recipients_status_check;

alter table message_recipients 
add constraint message_recipients_status_check 
check (status in ('pending', 'sent', 'failed', 'seen'));

-- Refresh schema just to be safe (though likely not needed for constraints)
notify pgrst, 'reload config';
