-- =========================================================
-- AUTO-GENERATED PILOT SEED
-- Source: school_matrix_template.csv
-- Generated: 2026-06-29T18:25:26.180Z
-- =========================================================

-- Skipped rows with placeholders: none

INSERT INTO public.schools (
  id,
  name,
  ci3_url,
  ci3_token,
  parents_api_url,
  parents_api_token,
  messages_api_url,
  messages_api_token,
  is_active,
  server_node_id,
  wa_session_status,
  otp_cooldown_seconds,
  otp_daily_cap
)
VALUES
  (
    1,
    'Sanabil School',
    'https://schoolsfls443dr4rsm53m.shihaab.tech',
    NULL,
    'https://demo.saafisystems.com',
    NULL,
    'https://schoolsfls443dr4rsm53m.shihaab.tech',
    NULL,
    TRUE,
    'VPS-1',
    'CONNECTED',
    30,
    250
  ),
  (
    4,
    'School B',
    'https://demo-schoolb-messages.example',
    NULL,
    'https://demo-schoolb-parents.example',
    NULL,
    'https://demo-schoolb-messages.example',
    NULL,
    TRUE,
    'VPS-1',
    'DISCONNECTED',
    30,
    250
  ),
  (
    5,
    'School C',
    'https://demo-schoolc-messages.example',
    NULL,
    'https://demo-schoolc-parents.example',
    NULL,
    'https://demo-schoolc-messages.example',
    NULL,
    TRUE,
    'VPS-1',
    'DISCONNECTED',
    30,
    250
  ),
  (
    6,
    'School D',
    'https://demo-schoold-messages.example',
    NULL,
    'https://demo-schoold-parents.example',
    NULL,
    'https://demo-schoold-messages.example',
    NULL,
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
  ci3_token = EXCLUDED.ci3_token,
  parents_api_url = EXCLUDED.parents_api_url,
  parents_api_token = EXCLUDED.parents_api_token,
  messages_api_url = EXCLUDED.messages_api_url,
  messages_api_token = EXCLUDED.messages_api_token,
  is_active = EXCLUDED.is_active,
  server_node_id = EXCLUDED.server_node_id,
  wa_session_status = EXCLUDED.wa_session_status,
  otp_cooldown_seconds = EXCLUDED.otp_cooldown_seconds,
  otp_daily_cap = EXCLUDED.otp_daily_cap;

INSERT INTO public.allowed_parents (
  school_id,
  parent_id,
  parent_name,
  phone_number,
  is_active
)
VALUES
  (
    1,
    10001,
    'SANABIL Parent 1',
    '252630000111',
    TRUE
  ),
  (
    1,
    10002,
    'SANABIL Parent 2',
    '252630000112',
    TRUE
  ),
  (
    1,
    10003,
    'SANABIL Parent 3',
    '252630000113',
    TRUE
  ),
  (
    4,
    40001,
    'SCHOOL_B Parent 1',
    '252630000221',
    TRUE
  ),
  (
    4,
    40002,
    'SCHOOL_B Parent 2',
    '252630000222',
    TRUE
  ),
  (
    4,
    40003,
    'SCHOOL_B Parent 3',
    '252630000223',
    TRUE
  ),
  (
    5,
    50001,
    'SCHOOL_C Parent 1',
    '252630000331',
    TRUE
  ),
  (
    5,
    50002,
    'SCHOOL_C Parent 2',
    '252630000332',
    TRUE
  ),
  (
    5,
    50003,
    'SCHOOL_C Parent 3',
    '252630000333',
    TRUE
  ),
  (
    6,
    60001,
    'SCHOOL_D Parent 1',
    '252630000441',
    TRUE
  ),
  (
    6,
    60002,
    'SCHOOL_D Parent 2',
    '252630000442',
    TRUE
  ),
  (
    6,
    60003,
    'SCHOOL_D Parent 3',
    '252630000443',
    TRUE
  )
ON CONFLICT DO NOTHING;

SELECT id, name, parents_api_url, messages_api_url, server_node_id, wa_session_status
FROM public.schools
WHERE id IN (1, 4, 5, 6)
ORDER BY id;

SELECT school_id, parent_id, parent_name, phone_number, is_active
FROM public.allowed_parents
WHERE school_id IN (1, 4, 5, 6)
ORDER BY school_id, parent_id;