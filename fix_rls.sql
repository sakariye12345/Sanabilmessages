-- FIX: Allow the Dispatcher to update message status
-- Run this in Supabase SQL Editor

-- 1. Enable UPDATE on message_recipients for anyone (Simulation Mode)
-- In production, this would be restricted to a Service Role or specific Admin User
create policy "Allow Status Updates" on message_recipients
  for update using (true) with check (true);

-- 2. Enable UPDATE on user_devices (just in case we need to update last_seen)
create policy "Allow Device Updates" on user_devices
  for update using (true) with check (true);
