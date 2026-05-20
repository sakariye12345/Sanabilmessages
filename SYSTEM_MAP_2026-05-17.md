# Sanabil Messages System Map

Date: 2026-05-17

## 1. Executive Reading

This repo contains one product idea, but at least three architectural eras:

- Era 1: mobile UI prototype
- Era 2: Supabase-backed push + bridge architecture
- Era 3: multi-school, cron-driven, OTP + WhatsApp operational platform

The codebase is therefore not “one clean stack”. It is a layered archive of how the system evolved while solving real production problems.

## 2. Timeline Reconstruction

### January 2026: UI and First Supabase Messaging Layer

Key artifacts:

- `SanabilMessages_Phase1_Report.md`
- `architecture_analysis.md`
- `supabase_setup.sql`
- `supabase_schema.sql`
- `gateway_service.py`
- early app screens

Interpretation:

- the project started as a React Native clone of Android Messages
- first backend intent was “message queue + push notification + allowlist login”
- OTP and production sync were not yet mature

### January 15 to January 23: First Live Delivery Experiments

Key artifacts:

- `fix_rls.sql`
- `diagnose.py`
- `check_status.py`
- `check_token.py`
- `push_dispatcher.py`
- `create_trigger.sql`
- `test_status_update.py`
- `bridge_service.py`

Interpretation:

- the team moved from design into real message dispatch
- Python services were used as the operational bridge
- status sync back to CI3 became important
- RLS and schema mismatch issues started appearing immediately

### February 2026: Schema Repair, RLS Hardening, Edge Functions

Key artifacts:

- `supabase/migrations/*`
- `rpc_get_user_id.sql`
- `otp_sender.py`
- `diagnose_*`
- `debug_*`
- `SECURE_RPC.sql`
- `ENABLE_SYNC_CRON.sql`
- `HANDOFF_TO_NEXT_SESSION.md`

Interpretation:

- the system clearly moved into production-style troubleshooting
- Supabase Edge Functions became the preferred architecture
- the team fought:
  - phone normalization
  - RLS visibility
  - CI3 duplicate behavior
  - realtime issues
  - auth format mismatches

### March 2026: Multi-school Direction and RPC Consolidation

Key artifacts:

- `app.config.js`
- `eas.json`
- `setup_cron.sql`
- `GET_INBOX_SUMMARY.sql`
- `GET_THREAD_MESSAGES.sql`
- `add_duplicate_constraint.sql`

Interpretation:

- the product stopped being “Sanabil-only” in design
- RPC-based inbox/thread access became the intended stable path
- multi-tenant rollout was planned, but only partly completed

### Late March 2026: OTP Microservice Direction

Key artifacts:

- `whatsapp-service/server.js`
- `setup_otp_testing.sql`
- `simulate_edge_function.py`

Interpretation:

- the OTP pathway was evolving away from Python Selenium into a service model
- but local install/runtime for that service is incomplete in this repo snapshot

### April 2026: Transcript/PDF Side Work

Key artifacts:

- `transcript_template.html`
- `transcript_view.php`
- `read_pdf.py`

Interpretation:

- these are sidecar artifacts, not part of the core Sanabil Messages delivery pipeline

## 3. Core Subsystems

### A. Parent Mobile App

Primary files:

- `app/_layout.tsx`
- `app/index.tsx`
- `app/(auth)/phone.tsx`
- `app/(auth)/verify.tsx`
- `app/(tabs)/inbox.tsx`
- `app/thread/[type].tsx`
- `app/message/[id].tsx`
- `src/store/auth.ts`
- `src/lib/supabase.ts`
- `src/services/notifications.ts`

Responsibilities:

- login by phone
- OTP verification
- register push token
- show inbox grouped by category
- show thread by message type
- show individual message view

Reality:

- inbox and thread are built around Supabase RPCs
- direct “chat” flow still points to an older CI3 API layer
- message detail view bypasses the secure RPC pattern

### B. Allowlist and Parent Identity

Main sources:

- `allowed_parents` in Supabase
- `ci3-demo/demo/application/controllers/Api.php`
- `ci3-demo/demo/application/models/Api_model.php`
- `supabase/functions/sync-parents/index.ts`

Responsibilities:

- CI3 exposes a cleaned allowlist feed
- Supabase stores synced parent records
- app checks allowlist before OTP

Reality:

- this is one of the few flows whose live endpoint currently works
- the live allowlist response matches the current Supabase table

### C. OTP Authentication

Main files:

- `supabase/functions/request-otp/index.ts`
- `app/(auth)/phone.tsx`
- `app/(auth)/verify.tsx`
- `otp_sender.py`
- `whatsapp-service/server.js`

Responsibilities:

- generate OTP
- create/update Supabase Auth user
- queue OTP for delivery
- deliver via gateway or WhatsApp service

Reality:

- queue creation works conceptually
- delivery path depends on either:
  - school-specific gateway config, or
  - a running WhatsApp delivery service
- the current live school row has no gateway configured and WhatsApp is disconnected

### D. Message Ingestion and Sync

Main files:

- `supabase/functions/bridge-sync/index.ts`
- `bridge_service.py`
- `gateway_service.py`
- `push_dispatcher.py`
- `update_schema.sql`
- `create_trigger.sql`

Responsibilities:

- pull messages from CI3
- normalize and classify them
- insert into Supabase
- mark status back upstream to CI3
- notify app devices

Reality:

- this is the subsystem currently most broken live

### E. Inbox and Thread Access

Main files:

- `GET_INBOX_SUMMARY.sql`
- `GET_THREAD_MESSAGES.sql`
- `SECURE_RPC.sql`
- `final_rpc_standardization.sql`
- `app/(tabs)/inbox.tsx`
- `app/thread/[type].tsx`

Responsibilities:

- secure user-scoped reads via JWT-derived phone
- group messages by category
- avoid RLS join failures

Reality:

- this is the intended current read architecture
- but one legacy overloaded RPC still exists and is incompatible with current schema

## 4. Canonical Live Data Contract

Verified against the live Supabase project on 2026-05-17.

### `allowed_parents`

Observed columns:

- `id`
- `phone_number`
- `parent_name`
- `created_at`
- `school_id`
- `parent_id`
- `is_active`
- `last_sync_at`

Current reality:

- table contains 5 rows
- data appears to come from the CI3 allowlist API

### `messages`

Observed columns:

- `id`
- `school_id`
- `student_id`
- `type`
- `title`
- `body`
- `created_at`

Current reality:

- table contains 100771 rows
- latest live message timestamps observed: 2026-02-23

### `message_recipients`

Verified columns through select probing:

- `id`
- `message_id`
- `phone_number`
- `status`
- `error`
- `sent_at`
- `created_at`
- `ci3_id`
- `is_synced_to_ci3`
- `seen_at`

Confirmed absent:

- `parent_phone`

Current reality:

- table currently contains 0 rows

### `user_devices`

Verified columns through select probing:

- `id`
- `phone_number`
- `fcm_token`
- `platform`
- `is_active`
- `last_seen_at`
- `created_at`

Confirmed absent:

- `user_phone`

Current reality:

- table currently contains 0 rows

### `schools`

Observed columns:

- `id`
- `name`
- `ci3_url`
- `ci3_token`
- `is_active`
- `created_at`
- `otp_gateway_url`
- `otp_gateway_key`
- `otp_sender_id`
- `wa_session_status`
- `server_node_id`

Current live row:

- `name`: `Sanabil School`
- `ci3_url`: `https://demo.saafisystems.com`
- `ci3_token`: demo-style token
- `wa_session_status`: `DISCONNECTED`
- OTP gateway fields: `NULL`

### `otp_queue`

Observed columns:

- `id`
- `phone`
- `code`
- `status`
- `created_at`
- `updated_at`
- `school_id`

Current reality:

- 41 rows total
- status distribution:
  - `SENT`: 38
  - `FAILED`: 2
  - `PENDING`: 1

### `sync_logs`

Current reality:

- 137703 rows total
- latest logs are minute-by-minute `FAILED`

## 5. Live Environment Facts

### Fact 1: The old CI3 message environment still exists

Verified:

- `https://schoolsfls443dr4rsm53m.shihaab.tech/messages/contacts` returns `200`
- body is currently `[]`

Interpretation:

- the older CI3 messaging backend still exists
- it is reachable
- it is not the current configured `schools.ci3_url`

### Fact 2: The current configured CI3 URL is only good for allowlist sync

Verified:

- `https://demo.saafisystems.com/index.php/api/v1/parents/allowed` returns `200`
- same host returns `404` for:
  - `/messages/contacts`
  - `/index.php/messages/contacts`
  - `/api/v1/messages/contacts`
  - `/index.php/api/v1/messages/contacts`
  - `/messages/update_status`

Interpretation:

- current `schools.ci3_url` supports parent allowlist API
- current `schools.ci3_url` does not expose the message queue endpoints that `bridge-sync` expects

### Fact 3: Bridge failure is explained by config drift, not mystery behavior

Observed in live logs:

- `bridge-sync` fails every minute with `CI3 HTTP Error: 404`

Direct cause:

- `bridge-sync` calls `${school.ci3_url}/messages/contacts`
- current `school.ci3_url` points to a host where that route does not exist

## 6. Auth Layer Mismatch

Observed live auth users:

- `252634370573`
- `252637464772`
- `252634370911`

Observed allowlist phones:

- `252634608072`
- `0634458114`
- `252636666666` inactive
- `252634370911`
- `252634878112`

Interpretation:

- only one auth user directly matches one currently active allowlist row: `252634370911`
- at least two auth users are not represented in the current allowlist snapshot

This suggests one or more of:

- historical test users remain in auth
- allowlist changed later
- parent sync changed source numbers

## 7. Normalization Problem Still Alive

Live allowlist API response includes:

- `0634458114`

App login normalizes phones toward `252...`.

`request-otp` also normalizes input before lookup.

Therefore:

- that active parent record is likely not log-in compatible with the current app

This is not a theoretical issue. It is a live data-contract defect.

## 8. OTP Delivery Failure Chain

Current live facts:

- app requests OTP through `request-otp`
- `request-otp` stores a row in `otp_queue`
- current `schools` row has no OTP gateway configured
- `request-otp` therefore falls back to WhatsApp-based delivery
- current `schools.wa_session_status` is `DISCONNECTED`
- latest queue still has one `PENDING` OTP from 2026-03-26

Interpretation:

- the OTP queue exists
- the OTP creation path likely works
- the delivery channel is currently inactive

This means the parent login experience is probably blocked at “code never arrives”.

## 9. What Looks Active vs What Looks Legacy

### Active or Intended-Current

- `app/(auth)/phone.tsx`
- `app/(auth)/verify.tsx`
- `app/(tabs)/inbox.tsx`
- `app/thread/[type].tsx`
- `src/lib/supabase.ts`
- `src/services/notifications.ts`
- `supabase/functions/request-otp/index.ts`
- `supabase/functions/sync-parents/index.ts`
- `supabase/functions/bridge-sync/index.ts`
- `GET_INBOX_SUMMARY.sql`
- `GET_THREAD_MESSAGES.sql`

### Legacy but Historically Important

- `src/api/client.ts`
- `app/chat/[phone].tsx`
- `bridge_service.py`
- `gateway_service.py`
- `push_dispatcher.py`
- `otp_sender.py`
- `CREATE_DEBUG_RPC.sql`
- `SECURE_RPC.sql`

### Debugging Residue

Large part of root scripts:

- `check_*`
- `debug_*`
- `diagnose_*`
- `verify_*`
- `test_*`

These are useful for forensic understanding, but they are not the canonical runtime.

## 10. Schema Drift Map

### Legacy Naming

- `allowed_parents.phone`
- `message_recipients.parent_phone`
- `user_devices.user_phone`

### Current Live Naming

- `allowed_parents.phone_number`
- `message_recipients.phone_number`
- `user_devices.phone_number`

Meaning:

- any script still referencing `parent_phone` or `user_phone` should be considered suspect until proven updated

## 11. Security Observations

### Message Detail Access Weakness

`app/message/[id].tsx` queries `messages` directly by `id` and `school_id`.

At the same time, SQL repair files explicitly created permissive message read policies for authenticated users.

Implication:

- if a user can guess a message ID within the same school, the detail view may expose it even if the user was not an intended recipient

Inbox/thread use secure per-user RPCs. Message detail does not.

### Allowlist Enumeration Risk

`app/(auth)/phone.tsx` queries `allowed_parents` before authentication.

Implication:

- the app assumes public or semi-public read access to allowlist existence
- that is convenient operationally, but it exposes phone existence checks

### Credential Exposure

The repo contains:

- CI3 tokens
- service-role tokens
- cron bearer tokens
- hardcoded Supabase URLs

This repo should be treated as secret-exposed.

## 12. Build / Local Runtime Reality

### Root App

`npx tsc --noEmit` fails.

Primary reasons:

- missing `expo-status-bar`
- missing direct `@expo/vector-icons`
- type drift in `app/chat/[phone].tsx`
- bad import in `src/components/OfflineBanner.tsx`
- Deno edge functions mixed into app typecheck scope

### Supabase Local Setup

`supabase/config.toml` expects:

- `supabase/seed.sql`

But that file does not exist.

Also:

- no `supabase/.env` is present in repo

### WhatsApp Service

`whatsapp-service/package.json` exists, but its dependencies are not installed locally in this repo snapshot.

Meaning:

- the service is designed, but not locally runnable without setup

## 13. Best Explanation of the Current Breakdown

The system likely broke in stages:

1. Messaging originally worked against the older `schoolsfls...shihaab.tech` CI3 environment.
2. Multi-school work introduced `schools.ci3_url`.
3. `schools.ci3_url` was later pointed at `demo.saafisystems.com`.
4. `sync-parents` continued to work because that host exposes the allowlist API.
5. `bridge-sync` began failing because that host does not expose message queue endpoints.
6. OTP delivery also degraded because the active school has no gateway configured and WhatsApp session is disconnected.

This is the cleanest explanation that matches:

- live `sync_logs`
- live `schools` row
- working allowlist API
- failing message API
- pending OTP queue

## 14. Best Mental Model Going Forward

Treat the project as:

- product direction: valid
- UI foundation: usable
- data hub choice: good
- live contract: drifted
- operational layers: partially dead

The next engineering work should start from contracts, not UI.

Start with:

1. canonical CI3 endpoint contract
2. canonical Supabase schema naming
3. OTP delivery channel decision
4. secure read path completion
5. local build cleanup

Only then continue feature work.
