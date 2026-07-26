BEGIN;

CREATE OR REPLACE FUNCTION public.replace_school_allowed_parents(
  p_school_id BIGINT,
  p_parents JSONB
)
RETURNS TABLE (
  upserted_count BIGINT,
  deactivated_count BIGINT
)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  now_ts TIMESTAMPTZ := NOW();
  parent_row RECORD;
  previously_active BIGINT := 0;
  active_after_sync BIGINT := 0;
  processed_count BIGINT := 0;
BEGIN
  IF auth.role() <> 'service_role' THEN
    RAISE EXCEPTION 'Service role required' USING ERRCODE = '42501';
  END IF;

  IF jsonb_typeof(COALESCE(p_parents, '[]'::JSONB)) <> 'array' THEN
    RAISE EXCEPTION 'p_parents must be a JSON array';
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM public.schools s
    WHERE s.id = p_school_id
      AND COALESCE(s.is_active, FALSE)
  ) THEN
    RAISE EXCEPTION 'Active school not found';
  END IF;

  IF EXISTS (
    SELECT 1
    FROM (
      SELECT public.normalize_somali_phone_sql(item ->> 'phone_number') AS phone_number
      FROM jsonb_array_elements(COALESCE(p_parents, '[]'::JSONB)) item
    ) phones
    WHERE phones.phone_number <> ''
    GROUP BY phones.phone_number
    HAVING COUNT(*) > 1
  ) THEN
    RAISE EXCEPTION 'Parent payload contains duplicate phone numbers';
  END IF;

  IF EXISTS (
    SELECT 1
    FROM (
      SELECT NULLIF(item ->> 'parent_id', '')::BIGINT AS parent_id
      FROM jsonb_array_elements(COALESCE(p_parents, '[]'::JSONB)) item
    ) parents
    WHERE parents.parent_id IS NOT NULL
    GROUP BY parents.parent_id
    HAVING COUNT(*) > 1
  ) THEN
    RAISE EXCEPTION 'Parent payload contains duplicate parent IDs';
  END IF;

  UPDATE public.allowed_parents ap
  SET is_active = FALSE,
      last_sync_at = now_ts
  WHERE ap.school_id = p_school_id
    AND COALESCE(ap.is_active, FALSE);

  GET DIAGNOSTICS previously_active = ROW_COUNT;

  FOR parent_row IN
    SELECT
      NULLIF(item ->> 'parent_id', '')::BIGINT AS parent_id,
      NULLIF(trim(item ->> 'parent_name'), '') AS parent_name,
      public.normalize_somali_phone_sql(item ->> 'phone_number') AS phone_number,
      COALESCE((item ->> 'is_active')::BOOLEAN, TRUE) AS is_active
    FROM jsonb_array_elements(COALESCE(p_parents, '[]'::JSONB)) item
  LOOP
    IF parent_row.phone_number = '' THEN
      CONTINUE;
    END IF;

    IF parent_row.parent_id IS NOT NULL THEN
      INSERT INTO public.allowed_parents AS existing (
        school_id,
        parent_id,
        parent_name,
        phone_number,
        is_active,
        last_sync_at
      )
      VALUES (
        p_school_id,
        parent_row.parent_id,
        parent_row.parent_name,
        parent_row.phone_number,
        parent_row.is_active,
        now_ts
      )
      ON CONFLICT (school_id, parent_id)
      DO UPDATE SET
        parent_name = EXCLUDED.parent_name,
        phone_number = EXCLUDED.phone_number,
        is_active = EXCLUDED.is_active,
        last_sync_at = EXCLUDED.last_sync_at;
    ELSE
      INSERT INTO public.allowed_parents AS existing (
        school_id,
        parent_id,
        parent_name,
        phone_number,
        is_active,
        last_sync_at
      )
      VALUES (
        p_school_id,
        NULL,
        parent_row.parent_name,
        parent_row.phone_number,
        parent_row.is_active,
        now_ts
      )
      ON CONFLICT (school_id, phone_number)
      DO UPDATE SET
        parent_name = EXCLUDED.parent_name,
        is_active = EXCLUDED.is_active,
        last_sync_at = EXCLUDED.last_sync_at;
    END IF;

    processed_count := processed_count + 1;
  END LOOP;

  SELECT COUNT(*)
  INTO active_after_sync
  FROM public.allowed_parents ap
  WHERE ap.school_id = p_school_id
    AND COALESCE(ap.is_active, FALSE)
    AND ap.last_sync_at = now_ts;

  RETURN QUERY
  SELECT
    processed_count,
    GREATEST(previously_active - active_after_sync, 0);
END;
$$;

REVOKE ALL ON FUNCTION public.replace_school_allowed_parents(BIGINT, JSONB)
  FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.replace_school_allowed_parents(BIGINT, JSONB)
  TO service_role;

COMMIT;
