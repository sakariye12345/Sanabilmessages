CREATE OR REPLACE FUNCTION public.register_my_device(
  p_device_id TEXT,
  p_device_name TEXT DEFAULT NULL,
  p_platform TEXT DEFAULT NULL,
  p_fcm_token TEXT DEFAULT NULL,
  p_app_variant TEXT DEFAULT NULL,
  p_mark_login BOOLEAN DEFAULT FALSE
)
RETURNS TABLE (
  id BIGINT,
  phone_number TEXT,
  device_id TEXT,
  is_active BOOLEAN,
  trusted_at TIMESTAMPTZ,
  revoked_at TIMESTAMPTZ,
  last_seen_at TIMESTAMPTZ,
  last_login_at TIMESTAMPTZ
)
SECURITY DEFINER
SET search_path = public
LANGUAGE plpgsql
AS $$
DECLARE
  jwt_phone TEXT;
  clean_phone TEXT;
  now_ts TIMESTAMPTZ := NOW();
BEGIN
  jwt_phone := auth.jwt() ->> 'phone';
  IF jwt_phone IS NULL THEN
    RAISE EXCEPTION 'Not Authenticated';
  END IF;

  IF COALESCE(trim(p_device_id), '') = '' THEN
    RAISE EXCEPTION 'Device ID is required';
  END IF;

  clean_phone := public.normalize_somali_phone_sql(jwt_phone);

  INSERT INTO public.user_devices (
    phone_number,
    device_id,
    device_name,
    platform,
    fcm_token,
    app_variant,
    is_active,
    trusted_at,
    revoked_at,
    last_seen_at,
    last_login_at,
    updated_at
  )
  VALUES (
    clean_phone,
    trim(p_device_id),
    NULLIF(trim(COALESCE(p_device_name, '')), ''),
    NULLIF(trim(COALESCE(p_platform, '')), ''),
    NULLIF(trim(COALESCE(p_fcm_token, '')), ''),
    NULLIF(trim(COALESCE(p_app_variant, '')), ''),
    TRUE,
    now_ts,
    NULL,
    now_ts,
    CASE WHEN p_mark_login THEN now_ts ELSE NULL END,
    now_ts
  )
  ON CONFLICT ("phone_number", "device_id")
  DO UPDATE SET
    device_name = COALESCE(EXCLUDED.device_name, public.user_devices.device_name),
    platform = COALESCE(EXCLUDED.platform, public.user_devices.platform),
    fcm_token = COALESCE(EXCLUDED.fcm_token, public.user_devices.fcm_token),
    app_variant = COALESCE(EXCLUDED.app_variant, public.user_devices.app_variant),
    is_active = TRUE,
    revoked_at = NULL,
    trusted_at = COALESCE(public.user_devices.trusted_at, now_ts),
    last_seen_at = now_ts,
    last_login_at = CASE
      WHEN p_mark_login THEN now_ts
      ELSE public.user_devices.last_login_at
    END,
    updated_at = now_ts
  RETURNING
    public.user_devices.id,
    public.user_devices.phone_number,
    public.user_devices.device_id,
    public.user_devices.is_active,
    public.user_devices.trusted_at,
    public.user_devices.revoked_at,
    public.user_devices.last_seen_at,
    public.user_devices.last_login_at
  INTO
    id,
    phone_number,
    device_id,
    is_active,
    trusted_at,
    revoked_at,
    last_seen_at,
    last_login_at;

  RETURN NEXT;
END;
$$;

GRANT EXECUTE ON FUNCTION public.register_my_device(TEXT, TEXT, TEXT, TEXT, TEXT, BOOLEAN) TO authenticated, service_role;
