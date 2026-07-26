-- Function to check for duplicates before insert
CREATE OR REPLACE FUNCTION prevent_duplicate_messages()
RETURNS TRIGGER AS $$
DECLARE
    existing_id INT;
BEGIN
    -- Check if a message with the same content was created in the last 5 minutes
    SELECT id INTO existing_id
    FROM messages
    WHERE school_id = NEW.school_id
      AND title = NEW.title
      AND body = NEW.body
      AND type = NEW.type
      AND created_at > (NOW() - INTERVAL '5 minutes')
    LIMIT 1;

    -- If duplicate found, raise an exception (or silent skip)
    IF existing_id IS NOT NULL THEN
        -- We raise notice to log it, but we can effectively stop the insert via exception
        -- OR we can try to handle it gracefully if the client supports ON CONFLICT.
        -- Since duplication is an issue, raising an exception is the safest way to "Reject" it.
        RAISE EXCEPTION 'Duplicate message detected within 5 minutes. ID: %', existing_id
            USING ERRCODE = 'unique_violation'; -- Uses standard unique constraint error code
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger definition
DROP TRIGGER IF EXISTS check_duplicate_message_trigger ON messages;

CREATE TRIGGER check_duplicate_message_trigger
BEFORE INSERT ON messages
FOR EACH ROW
EXECUTE FUNCTION prevent_duplicate_messages();
-- LEGACY REFERENCE: not part of the deployable migration chain.
