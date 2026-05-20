-- 1. Enable RLS (Best Practice standardizes access control)
alter table message_recipients enable row level security;

-- 2. Clean up old policies to ensure a fresh start
drop policy if exists "Allow All Access" on message_recipients;
drop policy if exists "Enable read access for all users" on message_recipients;
drop policy if exists "Enable insert for all users" on message_recipients;
drop policy if exists "Enable update for all users" on message_recipients;

-- 3. Create a single, permissive policy for the App (Anon key)
-- This allows SELECT, INSERT, UPDATE, DELETE for everyone.
create policy "Allow All Access"
on message_recipients
for all
to public
using (true)
with check (true);

-- 4. Explicitly GRANT permissions to the anon role (just in case)
grant select, insert, update, delete on table message_recipients to anon;
grant select, insert, update, delete on table message_recipients to authenticated;
