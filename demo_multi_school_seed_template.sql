-- =========================================================
-- DEMO MULTI-SCHOOL SEED TEMPLATE
-- Fill this file from school_matrix_template.csv
-- Use normalized phones only: 25263xxxxxxxx
-- =========================================================

-- ---------------------------------------------------------
-- 1. SCHOOLS
-- ---------------------------------------------------------
-- Replace URLs before running. Create the matching secrets directly in
-- Supabase Vault; never paste token values into this tracked file.

INSERT INTO public.schools (
  id,
  name,
  ci3_url,
  parents_api_url,
  messages_api_url,
  parents_api_secret_name,
  messages_api_secret_name,
  is_active,
  server_node_id,
  wa_session_status,
  otp_cooldown_seconds,
  otp_daily_cap
)
VALUES
  (
    4,
    'School B',
    'https://<legacy-ci3-or-main-host>',
    'https://<parents-source>',
    'https://<messages-source>',
    'school_4_parents_api_token',
    'school_4_messages_api_token',
    TRUE,
    'VPS-1',
    'DISCONNECTED',
    30,
    250
  ),
  (
    5,
    'School C',
    'https://<legacy-ci3-or-main-host>',
    'https://<parents-source>',
    'https://<messages-source>',
    'school_5_parents_api_token',
    'school_5_messages_api_token',
    TRUE,
    'VPS-1',
    'DISCONNECTED',
    30,
    250
  ),
  (
    6,
    'School D',
    'https://<legacy-ci3-or-main-host>',
    'https://<parents-source>',
    'https://<messages-source>',
    'school_6_parents_api_token',
    'school_6_messages_api_token',
    TRUE,
    'VPS-1',
    'DISCONNECTED',
    30,
    250
  )
ON CONFLICT (id) DO UPDATE
SET
  name = EXCLUDED.name,
  ci3_url = EXCLUDED.ci3_url,
  parents_api_url = EXCLUDED.parents_api_url,
  messages_api_url = EXCLUDED.messages_api_url,
  parents_api_secret_name = EXCLUDED.parents_api_secret_name,
  messages_api_secret_name = EXCLUDED.messages_api_secret_name,
  is_active = EXCLUDED.is_active,
  server_node_id = EXCLUDED.server_node_id,
  otp_cooldown_seconds = EXCLUDED.otp_cooldown_seconds,
  otp_daily_cap = EXCLUDED.otp_daily_cap;

-- ---------------------------------------------------------
-- 2. ALLOWED PARENTS
-- ---------------------------------------------------------
-- Important:
-- - phone_number must be normalized
-- - school_id must match the target school
-- - is_active should be TRUE for test accounts

INSERT INTO public.allowed_parents (
  school_id,
  parent_id,
  parent_name,
  phone_number,
  is_active
)
VALUES
  (4, 20001, 'Parent B1', '25263xxxx221', TRUE),
  (4, 20002, 'Parent B2', '25263xxxx222', TRUE),
  (4, 20003, 'Parent B3', '25263xxxx223', TRUE),

  (5, 30001, 'Parent C1', '25263xxxx331', TRUE),
  (5, 30002, 'Parent C2', '25263xxxx332', TRUE),
  (5, 30003, 'Parent C3', '25263xxxx333', TRUE),

  (6, 40001, 'Parent D1', '25263xxxx441', TRUE),
  (6, 40002, 'Parent D2', '25263xxxx442', TRUE),
  (6, 40003, 'Parent D3', '25263xxxx443', TRUE);

-- ---------------------------------------------------------
-- 3. OPTIONAL: STUDENTS / RELATIONSHIP DATA
-- ---------------------------------------------------------
-- Use this only if your CI3/demo flows depend on student-parent linkage.
-- Keep school_id consistent with allowed_parents.

-- INSERT INTO public.students (...)
-- INSERT INTO public.student_parents (...)

-- ---------------------------------------------------------
-- 4. PHASE 1 VERIFICATION
-- ---------------------------------------------------------

-- Check schools
SELECT id, name, parents_api_url, messages_api_url, server_node_id, wa_session_status
FROM public.schools
WHERE id IN (1, 4, 5, 6)
ORDER BY id;

-- Check allowed parents
SELECT school_id, parent_id, parent_name, phone_number, is_active
FROM public.allowed_parents
WHERE school_id IN (1, 4, 5, 6)
ORDER BY school_id, parent_id;

-- Check for duplicate parent numbers across schools
SELECT phone_number, COUNT(*) AS total
FROM public.allowed_parents
WHERE school_id IN (1, 4, 5, 6)
GROUP BY phone_number
HAVING COUNT(*) > 1
ORDER BY total DESC, phone_number;

-- ---------------------------------------------------------
-- 5. NOTES
-- ---------------------------------------------------------
-- Before Phase 2 starts, confirm:
-- - school rows exist
-- - all phone numbers are normalized
-- - source URLs are correct
-- - no duplicate test parent numbers were inserted by mistake
