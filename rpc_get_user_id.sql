-- Function to look up user ID by phone (Securely, for Service Role only)
create or replace function get_user_id_by_phone(p_phone text)
returns uuid
language plpgsql
security definer
set search_path = public
as $$
declare
  v_user_id uuid;
begin
  select id into v_user_id
  from auth.users
  where phone = p_phone
  limit 1;
  
  return v_user_id;
end;
$$;

-- Grant access to service_role (used by Edge Functions)
grant execute on function get_user_id_by_phone to service_role;
