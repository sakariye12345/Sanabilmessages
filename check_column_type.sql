-- check_column_type.sql
SELECT 
    column_name, 
    data_type 
FROM 
    information_schema.columns 
WHERE 
    table_name = 'message_recipients' 
    AND column_name = 'ci3_id';
