-- TRIGGER: Auto-Reset Sync Flag
-- When status changes (e.g. Sent -> Seen), we must reset 'is_synced_to_ci3' to FALSE
-- So the Bridge picks it up again.

create or replace function reset_sync_flag()
returns trigger as $$
begin
  -- Reset sync flag ONLY if status actually changed
  if OLD.status is distinct from NEW.status then
     NEW.is_synced_to_ci3 := false;
  end if;
  return NEW;
end;
$$ language plpgsql;

drop trigger if exists tr_reset_sync_flag on message_recipients;

create trigger tr_reset_sync_flag
before update on message_recipients
for each row
execute function reset_sync_flag();
