-- ALLOW INSERTS for Simulation/Testing
-- Run this in Supabase SQL Editor

create policy "Allow Inserts for Simulation" on messages
  for insert with check (true);

create policy "Allow Inserts for Simulation" on message_recipients
  for insert with check (true);
