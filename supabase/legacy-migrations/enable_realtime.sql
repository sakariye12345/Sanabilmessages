-- Enable Realtime for message_recipients table
alter publication supabase_realtime add table message_recipients;
-- LEGACY REFERENCE: not part of the deployable migration chain.
