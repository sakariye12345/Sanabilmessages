BEGIN;

ALTER TABLE public.schools
  ADD COLUMN IF NOT EXISTS parents_api_url TEXT,
  ADD COLUMN IF NOT EXISTS parents_api_token TEXT,
  ADD COLUMN IF NOT EXISTS messages_api_url TEXT,
  ADD COLUMN IF NOT EXISTS messages_api_token TEXT,
  ADD COLUMN IF NOT EXISTS parents_api_secret_name TEXT,
  ADD COLUMN IF NOT EXISTS messages_api_secret_name TEXT;

ALTER TABLE public.schools
  ALTER COLUMN ci3_token DROP NOT NULL;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'schools_parents_api_secret_name_check'
      AND conrelid = 'public.schools'::regclass
  ) THEN
    ALTER TABLE public.schools
      ADD CONSTRAINT schools_parents_api_secret_name_check
      CHECK (
        parents_api_secret_name IS NULL
        OR parents_api_secret_name ~ '^[A-Za-z0-9_-]{3,120}$'
      );
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'schools_messages_api_secret_name_check'
      AND conrelid = 'public.schools'::regclass
  ) THEN
    ALTER TABLE public.schools
      ADD CONSTRAINT schools_messages_api_secret_name_check
      CHECK (
        messages_api_secret_name IS NULL
        OR messages_api_secret_name ~ '^[A-Za-z0-9_-]{3,120}$'
      );
  END IF;
END;
$$;

UPDATE public.schools
SET
  parents_api_secret_name = COALESCE(
    parents_api_secret_name,
    'school_' || id::TEXT || '_parents_api_token'
  ),
  messages_api_secret_name = COALESCE(
    messages_api_secret_name,
    'school_' || id::TEXT || '_messages_api_token'
  );

UPDATE public.schools
SET
  ci3_token = NULLIF(ci3_token, 'YOUR_CI3_API_TOKEN'),
  parents_api_token = NULLIF(parents_api_token, 'YOUR_CI3_API_TOKEN'),
  messages_api_token = NULLIF(messages_api_token, 'YOUR_CI3_API_TOKEN');

CREATE OR REPLACE FUNCTION public.get_active_school_integrations()
RETURNS TABLE (
  id BIGINT,
  name TEXT,
  ci3_url TEXT,
  ci3_token TEXT,
  parents_api_url TEXT,
  parents_api_token TEXT,
  messages_api_url TEXT,
  messages_api_token TEXT
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, vault
AS $$
BEGIN
  IF auth.role() <> 'service_role' THEN
    RAISE EXCEPTION 'Service role required' USING ERRCODE = '42501';
  END IF;

  RETURN QUERY
  SELECT
    s.id,
    s.name,
    s.ci3_url,
    NULLIF(s.ci3_token, ''),
    s.parents_api_url,
    COALESCE(
      NULLIF(parent_secret.decrypted_secret, ''),
      NULLIF(s.parents_api_token, ''),
      NULLIF(s.ci3_token, '')
    ),
    s.messages_api_url,
    COALESCE(
      NULLIF(message_secret.decrypted_secret, ''),
      NULLIF(s.messages_api_token, ''),
      NULLIF(s.ci3_token, '')
    )
  FROM public.schools s
  LEFT JOIN LATERAL (
    SELECT vs.decrypted_secret
    FROM vault.decrypted_secrets vs
    WHERE vs.name = s.parents_api_secret_name
    LIMIT 1
  ) parent_secret ON TRUE
  LEFT JOIN LATERAL (
    SELECT vs.decrypted_secret
    FROM vault.decrypted_secrets vs
    WHERE vs.name = s.messages_api_secret_name
    LIMIT 1
  ) message_secret ON TRUE
  WHERE COALESCE(s.is_active, FALSE)
  ORDER BY s.id;
END;
$$;

REVOKE ALL ON FUNCTION public.get_active_school_integrations()
  FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.get_active_school_integrations()
  TO service_role;

COMMIT;
