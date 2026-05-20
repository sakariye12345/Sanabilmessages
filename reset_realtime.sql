-- RESET REALTIME CONFIGURATION
-- Sometimes the publication state gets stuck. Removing and re-adding forces a refresh.

BEGIN;
  -- 1. Remove table
  ALTER PUBLICATION supabase_realtime DROP TABLE message_recipients;
  
  -- 2. Re-add table
  ALTER PUBLICATION supabase_realtime ADD TABLE message_recipients;
COMMIT;

SELECT 'Realtime Reset Complete' as status;
