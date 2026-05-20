CREATE OR REPLACE FUNCTION public.normalize_phone_trigger()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  IF TG_TABLE_NAME IN ('allowed_parents', 'message_recipients', 'student_parents', 'user_devices') THEN
    NEW.phone_number := public.normalize_somali_phone_sql(NEW.phone_number);
  END IF;

  RETURN NEW;
END;
$$;
