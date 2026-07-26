ALTER TABLE public.schools
  ADD COLUMN IF NOT EXISTS otp_is_paused BOOLEAN DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS otp_pause_reason TEXT,
  ADD COLUMN IF NOT EXISTS otp_pause_until TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS otp_cooldown_seconds INTEGER DEFAULT 30,
  ADD COLUMN IF NOT EXISTS otp_daily_cap INTEGER DEFAULT 250,
  ADD COLUMN IF NOT EXISTS otp_last_sent_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS otp_last_error_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS otp_consecutive_failures INTEGER DEFAULT 0;

UPDATE public.schools
SET otp_cooldown_seconds = COALESCE(otp_cooldown_seconds, 30),
    otp_daily_cap = COALESCE(otp_daily_cap, 250),
    otp_consecutive_failures = COALESCE(otp_consecutive_failures, 0),
    otp_is_paused = COALESCE(otp_is_paused, FALSE)
WHERE TRUE;

CREATE OR REPLACE FUNCTION public.revoke_my_device(
  p_device_id TEXT DEFAULT NULL
)
RETURNS TABLE (
  id BIGINT,
  phone_number TEXT,
  device_id TEXT,
  is_active BOOLEAN,
  revoked_at TIMESTAMPTZ
)
SECURITY DEFINER
SET search_path = public
LANGUAGE plpgsql
AS $$
DECLARE
  jwt_phone TEXT;
  clean_phone TEXT;
  target_device_id TEXT;
BEGIN
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL THEN
    RAISE EXCEPTION 'Not Authenticated';
  END IF;

  target_device_id := NULLIF(trim(COALESCE(p_device_id, '')), '');
  IF target_device_id IS NULL THEN
    RAISE EXCEPTION 'Device ID is required';
  END IF;

  clean_phone := public.normalize_somali_phone_sql(jwt_phone);

  RETURN QUERY
  UPDATE public.user_devices ud
  SET is_active = FALSE,
      revoked_at = NOW(),
      updated_at = NOW()
  WHERE ud.phone_number = clean_phone
    AND ud.device_id = target_device_id
    AND ud.revoked_at IS NULL
  RETURNING
    ud.id,
    ud.phone_number,
    ud.device_id,
    ud.is_active,
    ud.revoked_at;
END;
$$;

CREATE OR REPLACE FUNCTION public.list_my_devices()
RETURNS TABLE (
  id BIGINT,
  phone_number TEXT,
  device_id TEXT,
  device_name TEXT,
  platform TEXT,
  app_variant TEXT,
  is_active BOOLEAN,
  trusted_at TIMESTAMPTZ,
  revoked_at TIMESTAMPTZ,
  last_seen_at TIMESTAMPTZ,
  last_login_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ
)
SECURITY DEFINER
SET search_path = public
LANGUAGE plpgsql
AS $$
DECLARE
  jwt_phone TEXT;
  clean_phone TEXT;
BEGIN
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL THEN
    RAISE EXCEPTION 'Not Authenticated';
  END IF;

  clean_phone := public.normalize_somali_phone_sql(jwt_phone);

  RETURN QUERY
  SELECT
    ud.id,
    ud.phone_number,
    ud.device_id,
    ud.device_name,
    ud.platform,
    ud.app_variant,
    ud.is_active,
    ud.trusted_at,
    ud.revoked_at,
    ud.last_seen_at,
    ud.last_login_at,
    ud.created_at
  FROM public.user_devices ud
  WHERE ud.phone_number = clean_phone
  ORDER BY COALESCE(ud.last_seen_at, ud.created_at) DESC, ud.id DESC;
END;
$$;

GRANT EXECUTE ON FUNCTION public.revoke_my_device(TEXT) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.list_my_devices() TO authenticated, service_role;
-- LEGACY REFERENCE: not part of the deployable migration chain.
