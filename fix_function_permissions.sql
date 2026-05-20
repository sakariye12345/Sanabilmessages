-- FIX PERMISSIONS
-- ensuring the authenticated role can actually RUN the function used in the RLS policy.
-- If this was missing, the policy would crash, causing Realtime to fail with CHANNEL_ERROR.

GRANT EXECUTE ON FUNCTION public.get_auth_phone() TO authenticated;
GRANT EXECUTE ON FUNCTION public.get_auth_phone() TO service_role;

-- Also verify the function returns cleanly for test
-- (We can't simulate auth easily here in simple SQL without extensions, 
-- but the GRANT is the critical fix).

SELECT 'Permissions Granted' as status;
