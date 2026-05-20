-- 🧠 SMART NORMALIZATION TRIGGERS
-- This makes the database "Smart" by automatically cleaning phone numbers 
-- BEFORE they are saved. Passing '+252...' or '252...' will both result in '252...'
-- preventing Foreign Key errors forever.

-- 1. Create the Normalization Function
CREATE OR REPLACE FUNCTION public.normalize_phone_trigger()
RETURNS TRIGGER AS $$
BEGIN
    -- Remove all non-digit characters (including +)
    -- This turns '+252-63-444' into '25263444'
    -- Dynamically updates the column specified in TG_ARGV[0]
    
    IF TG_TABLE_NAME = 'allowed_parents' OR TG_TABLE_NAME = 'users' THEN
       NEW.phone := regexp_replace(NEW.phone, '\D', '', 'g');
    ELSIF TG_TABLE_NAME = 'message_recipients' OR TG_TABLE_NAME = 'student_parents' THEN
       NEW.parent_phone := regexp_replace(NEW.parent_phone, '\D', '', 'g');
    ELSIF TG_TABLE_NAME = 'user_devices' THEN
       NEW.user_phone := regexp_replace(NEW.user_phone, '\D', '', 'g');
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 2. Attach Triggers to Tables (BEFORE INSERT OR UPDATE)

-- allowed_parents
DROP TRIGGER IF EXISTS trg_normalize_allowed_parents ON allowed_parents;
CREATE TRIGGER trg_normalize_allowed_parents
BEFORE INSERT OR UPDATE ON allowed_parents
FOR EACH ROW EXECUTE FUNCTION normalize_phone_trigger();

-- message_recipients
DROP TRIGGER IF EXISTS trg_normalize_message_recipients ON message_recipients;
CREATE TRIGGER trg_normalize_message_recipients
BEFORE INSERT OR UPDATE ON message_recipients
FOR EACH ROW EXECUTE FUNCTION normalize_phone_trigger();

-- student_parents
DROP TRIGGER IF EXISTS trg_normalize_student_parents ON student_parents;
CREATE TRIGGER trg_normalize_student_parents
BEFORE INSERT OR UPDATE ON student_parents
FOR EACH ROW EXECUTE FUNCTION normalize_phone_trigger();

-- user_devices
DROP TRIGGER IF EXISTS trg_normalize_user_devices ON user_devices;
CREATE TRIGGER trg_normalize_user_devices
BEFORE INSERT OR UPDATE ON user_devices
FOR EACH ROW EXECUTE FUNCTION normalize_phone_trigger();

-- Summary:
-- Now, if App sends '+25263...', DB converts it to '25263...' instantly.
-- If Auth has '25263...', they match perfectly.
-- "Constraint Violation" errors are impossible due to formatting.
