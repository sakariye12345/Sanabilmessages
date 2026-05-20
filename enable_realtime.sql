-- ENABLE REALTIME REPLICATION
-- By default, Supabase disables Realtime for new tables to save resources.
-- We must explicitly add 'message_recipients' to the publication.

begin;
  -- Add table to publication
  alter publication supabase_realtime add table message_recipients;
commit;

-- Build notification (optional)
select 'Realtime Enabled for message_recipients' as status;
