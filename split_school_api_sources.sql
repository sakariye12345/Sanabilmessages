-- ==========================================
-- SPLIT SCHOOL API SOURCES
-- ==========================================
-- Purpose:
-- 1. Keep demo/testing allowlist source separate from the real CI3 message backend.
-- 2. Stop bridge-sync from reading messages from the wrong host.
-- 3. Preserve backward compatibility by leaving ci3_url/ci3_token intact for older code.

BEGIN;

ALTER TABLE public.schools
  ADD COLUMN IF NOT EXISTS parents_api_url TEXT,
  ADD COLUMN IF NOT EXISTS parents_api_token TEXT,
  ADD COLUMN IF NOT EXISTS messages_api_url TEXT,
  ADD COLUMN IF NOT EXISTS messages_api_token TEXT;

-- Backfill from existing fields so old rows stay usable immediately.
UPDATE public.schools
SET
  parents_api_url = COALESCE(parents_api_url, ci3_url),
  parents_api_token = COALESCE(parents_api_token, ci3_token),
  messages_api_url = COALESCE(messages_api_url, ci3_url),
  messages_api_token = COALESCE(messages_api_token, ci3_token);

-- Current Sanabil v2 live mapping:
-- - Parents allowlist comes from demo.saafisystems.com
-- - Messages/status sync comes from schoolsfls443dr4rsm53m.shihaab.tech
UPDATE public.schools
SET
  name = 'Sanabil School',
  parents_api_url = 'https://demo.saafisystems.com',
  parents_api_token = 'YOUR_SCHOOL_API_TOKEN',
  messages_api_url = 'https://schoolsfls443dr4rsm53m.shihaab.tech',
  messages_api_token = '3e8ea952f2a06672'
WHERE id = 1;

COMMIT;

SELECT
  id,
  name,
  ci3_url,
  parents_api_url,
  messages_api_url
FROM public.schools
ORDER BY id;
