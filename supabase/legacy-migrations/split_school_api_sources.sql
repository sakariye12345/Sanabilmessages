-- ==========================================
-- SPLIT SCHOOL API SOURCES
-- ==========================================

ALTER TABLE public.schools
  ADD COLUMN IF NOT EXISTS parents_api_url TEXT,
  ADD COLUMN IF NOT EXISTS parents_api_token TEXT,
  ADD COLUMN IF NOT EXISTS messages_api_url TEXT,
  ADD COLUMN IF NOT EXISTS messages_api_token TEXT;

UPDATE public.schools
SET
  parents_api_url = COALESCE(parents_api_url, ci3_url),
  parents_api_token = COALESCE(parents_api_token, ci3_token),
  messages_api_url = COALESCE(messages_api_url, ci3_url),
  messages_api_token = COALESCE(messages_api_token, ci3_token);
-- LEGACY REFERENCE: not part of the deployable migration chain.
