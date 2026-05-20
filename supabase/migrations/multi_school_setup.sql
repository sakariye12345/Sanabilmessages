-- 1. Create Schools Table
create table if not exists public.schools (
  id bigint generated always as identity primary key,
  name text not null,
  ci3_url text not null,
  ci3_token text not null, -- Store securely, RLS will block read
  is_active boolean default true,
  created_at timestamptz default now()
);

-- 2. Add school_id to Allowed Parents
alter table public.allowed_parents 
add column if not exists school_id bigint references public.schools(id);

-- 3. Add school_id to Messages
-- Note: 'messages' table already had 'school_id' (int) from early simulation.
-- We must ensure it references the new table.

-- First, insert a default school to link existing data
insert into public.schools (name, ci3_url, ci3_token)
values ('Default School', 'https://schoolsfls443dr4rsm53m.shihaab.tech', '3e8ea952f2a06672')
on conflict do nothing;

-- Now update existing records to point to school_id = 1
update public.messages set school_id = 1 where school_id is null;
update public.allowed_parents set school_id = 1 where school_id is null;

-- Add Foreign Key constraint if not exists
do $$ 
begin
  if not exists (select 1 from information_schema.table_constraints where constraint_name = 'messages_school_id_fkey') then
    alter table public.messages
    add constraint messages_school_id_fkey
    foreign key (school_id) references public.schools(id);
  end if;
end $$;

-- 4. Secure the Schools Table (RLS)
alter table public.schools enable row level security;

-- Only Service Role (Edge Functions) can read/write schools
create policy "Service Role can manage schools"
on public.schools
for all
to service_role
using (true)
with check (true);

-- Authenticated Users (Parents) can only read basic info of their school? 
-- For now, parents don't need to read the schools table directly.

-- 5. Create Index
create index if not exists idx_messages_school_id on public.messages(school_id);
create index if not exists idx_allowed_parents_school_id on public.allowed_parents(school_id);
