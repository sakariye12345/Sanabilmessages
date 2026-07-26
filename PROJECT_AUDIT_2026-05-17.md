# Sanabil Messages Project Audit

Date: 2026-05-17

## 1. What This Project Is

Sanabil Messages is no longer just a mobile UI prototype. The repo now contains a full communications platform made of:

- A React Native / Expo mobile app for parents.
- Supabase as the central hub for auth, data, RPCs, edge functions, and automation.
- CI3 integration code and a demo CI3 scaffold for parent allowlists.
- OTP delivery infrastructure using Supabase Auth plus WhatsApp / gateway-based dispatch.
- Legacy and replacement bridge services for syncing CI3 messages into Supabase.

The original business goal is consistent across the codebase:
x
- Stop depending on WhatsApp limits and third-party SMS gateway friction.
- Deliver school messages directly to parent phones through your own app.
- Keep CI3 as the upstream school system and Supabase as the middleware / control plane.

## 2. Repo Anatomy

Main project areas:

- `app/`: Expo Router screens.
- `src/`: auth store, message store, Supabase client, notifications, config.
- `components/`: shared UI components.
- `supabase/functions/`: edge functions for sync, notifications, OTP, parent sync.
- `supabase/migrations/`: schema and policy evolution.
- `whatsapp-service/`: newer Node-based WhatsApp OTP service.
- `ci3-demo/`: CI3 demo scaffold for allowed parents and related UI.
- root `*.sql` / `*.py`: operational scripts, hotfixes, diagnostics, manual migrations, and debugging tools.

Non-core / noisy areas:

- `node_modules/`
- `whatsapp_session/`
- CI3 framework vendor/docs under `ci3-demo/demo/system` and `ci3-demo/demo/user_guide`

Approximate file mix excluding noisy/vendor areas:

- `ts/tsx`: 33
- `sql`: 56
- `python`: 51
- `md`: 5
- `html/php`: 163

## 3. Architecture Evolution

### Phase A: UI Replica

Documented in:

- `SanabilMessages_Phase1_Report.md`
- `architecture_analysis.md`
- `Sanabil_Report_Printable.html`

This phase built:

- Inbox UI
- Chat/thread UI
- Expo Router navigation
- Zustand auth/message state
- Mock-data driven Android Messages style UX

### Phase B: Supabase-Centered Messaging

The architecture then shifted away from a pure CI3-direct app into:

- Supabase Auth for parent login
- `allowed_parents` as the allowlist
- `messages` + `message_recipients` as the message hub
- `user_devices` for push tokens
- edge functions for sync and OTP
- realtime subscriptions in the app

### Phase C: Multi-school and Operational Hardening

Later work introduced:

- `schools` table
- build-time white-label config in `app.config.js` and `eas.json`
- `sync-parents` edge function
- `bridge-sync` cron workflow
- WhatsApp OTP microservice
- many RLS and RPC experiments

## 4. What Is Implemented in Code

### Mobile App

Implemented:

- session hydration and auth redirect
- phone entry screen
- OTP verify screen
- inbox screen using Supabase RPCs
- thread screen using thread RPC
- message detail screen
- profile screen
- notification token registration
- realtime listeners for inbox and thread

Key files:

- `app/_layout.tsx`
- `app/index.tsx`
- `app/(auth)/phone.tsx`
- `app/(auth)/verify.tsx`
- `app/(tabs)/inbox.tsx`
- `app/thread/[type].tsx`
- `app/message/[id].tsx`
- `src/lib/supabase.ts`
- `src/services/notifications.ts`
- `src/store/auth.ts`

### Supabase Layer

Implemented:

- edge functions:
  - `bridge-sync`
  - `sync-parents`
  - `request-otp`
  - `notify-parents`
- schema migration set for:
  - multi-school setup
  - OTP queue/logs
  - sync logs
  - realtime
  - duplicate prevention
  - multiple RLS iterations
- RPC drafts for:
  - `get_my_inbox`
  - `get_inbox_summary`
  - `get_thread_messages`

### CI3 Side

Implemented in the demo scaffold:

- `/api/v1/parents/allowed`
- parent management dashboard
- import/export tooling for allowlist data

Not present in the CI3 demo snapshot:

- actual `/messages/contacts`
- actual `/messages/update_status`

This means the allowlist API is represented in the repo, but the message queue endpoints appear to live elsewhere or are not committed here.

### OTP / WhatsApp Infrastructure

Two generations exist:

- older Python Selenium OTP sender and bridge scripts
- newer `whatsapp-service/server.js` using `whatsapp-web.js`

This confirms a real migration path from browser automation prototypes to a more service-like architecture.

## 5. Codebase Drift That Must Be Understood

The repo contains multiple overlapping generations of the same system.

### Drift 1: Legacy CI3 API vs New Supabase RPC Flow

Examples:

- `src/api/client.ts` still points to a hardcoded CI3 base URL.
- `app/chat/[phone].tsx` still depends on the old API client.
- `app/(tabs)/inbox.tsx` and `app/thread/[type].tsx` already use Supabase RPCs.

Conclusion:

- Inbox/thread moved forward.
- direct chat/API path is legacy and incomplete.

### Drift 2: Schema Name Changes

Older code assumes:

- `message_recipients.parent_phone`
- `user_devices.user_phone`
- `allowed_parents.phone`

Newer code assumes:

- `message_recipients.phone_number`
- `user_devices.phone_number`
- `allowed_parents.phone_number`

This drift is the single most important structural issue in the repo.

### Drift 3: Legacy Python Bridge vs Edge Function Bridge

Older scripts:

- `bridge_service.py`
- `push_dispatcher.py`
- `gateway_service.py`
- `otp_sender.py`

Newer architecture:

- `supabase/functions/bridge-sync/index.ts`
- `supabase/functions/request-otp/index.ts`
- `supabase/functions/sync-parents/index.ts`
- `whatsapp-service/server.js`

Conclusion:

- the repo preserves the operational history
- but not all older scripts match the live schema anymore

## 6. Live State Verified on 2026-05-17

Read-only checks against the current Supabase project showed:

- `schools`: 1 row
- `allowed_parents`: 5 rows
- `messages`: 100771 rows
- `message_recipients`: 0 rows
- `user_devices`: 0 rows
- `otp_queue`: 41 rows
- `otp_logs`: 0 rows
- `sync_logs`: 137703 rows
- auth users: 3

Additional live facts:

- `allowed_parents` now uses `phone_number`, not `phone`
- `message_recipients` now uses `phone_number`, not `parent_phone`
- `user_devices` now uses `phone_number`, not `user_phone`
- the old debug RPC path using `parent_phone` is broken live

Verified failure:

- `verify_rpc.py` fails with: `column mr.parent_phone does not exist`

This confirms the live database has already moved to the newer naming model, while many scripts still target the old one.

## 7. Live Operational Timeline

Observed from the live database:

- latest imported messages were created on 2026-02-23
- latest successful sync log was on 2026-02-25 with `Processed 0 down, 0 up.`
- first observed continuous 404 bridge failure began on 2026-02-25 18:58 UTC
- `allowed_parents.last_sync_at` shows parent sync activity on 2026-02-26
- latest OTP queue item was created on 2026-03-26 and is still `PENDING`
- as of 2026-05-17, `bridge-sync` is still logging minute-by-minute 404 failures

Meaning:

- the system previously worked at least partially in February
- downstream CI3 message ingestion later broke
- OTP flow partially worked historically, then stalled

## 8. Current Working / Non-Working Picture

### Working or Mostly Present

- mobile UI structure
- Supabase auth integration approach
- allowlist-based login concept
- edge-function architecture direction
- multi-school config direction
- historical OTP sending path
- parent sync concept and schema

### Not Working or Currently Broken

- current message ingestion pipeline
- current inbox data delivery from live backend
- current device token registration in production use
- old RPC variants that still reference `parent_phone`
- build cleanliness of the app
- full white-label asset readiness

## 9. Build and Code Health Findings

`npx tsc --noEmit` currently fails.

Main causes:

- missing packages: `@expo/vector-icons`, `expo-status-bar`
- `App.tsx` legacy entry still compiles even though package main uses Expo Router
- `app/chat/[phone].tsx` uses a `sender` field instead of required `direction`
- `profile.tsx` uses `Colors.error`, which does not exist
- `src/components/OfflineBanner.tsx` imports `../constants/Colors` from the wrong path
- `supabase/functions/*` are being typechecked with the app and fail because Deno modules/globals are not separated

Conclusion:

- the repo is not build-clean
- the active app path and legacy files are mixed together

## 10. White-label State

Multi-school intent is real:

- `app.config.js`
- `eas.json`
- `src/config/schoolConfig.ts`

But implementation is partial:

- only the Sanabil base asset set exists in `assets/`
- variant assets for `alsunna` and `alxikma` are missing
- UI colors still mainly use static `Colors` instead of dynamic school theming
- most school-specific runtime behavior is still hardcoded around school `1`

Conclusion:

- white-labeling is planned and started
- not yet production-complete

## 11. Security and Repo Hygiene Risks

High-risk findings:

- service role keys and API tokens are committed inside source files and SQL scripts
- cron SQL includes bearer tokens inline
- `.env` is not properly ignored for the plain `.env` file
- `whatsapp_session/` is not ignored
- `ci3-demo/` ignore rule is corrupted in `.gitignore`

Operationally, this repo should be treated as credential-exposed until secrets are rotated.

## 12. Strategic Reading of Project Status

This project is not a blank start.

It is best described as:

- concept proven
- UI foundation built
- backend direction chosen
- real integration attempted
- real data touched
- schema partially migrated
- production workflow drifted and broke

So the correct next move is not "start over".

The correct next move is:

- stabilize the live data contract
- remove legacy path confusion
- restore one clean end-to-end flow

## 13. Practical Next Order of Work

Recommended order:

1. Establish the canonical schema and API contract.
   Decide one naming system only:
   - `allowed_parents.phone_number`
   - `message_recipients.phone_number`
   - `user_devices.phone_number`

2. Repair the CI3 upstream configuration.
   The current `schools.ci3_url` / endpoint assumptions are not aligned with the repo and are producing 404s.

3. Restore downstream message ingestion.
   Until `message_recipients` is populated again, the app has no inbox.

4. Repair OTP dispatch path.
   Clear why the newest queue item from 2026-03-26 is still pending.

5. Make the mobile app build-clean.
   Remove dead files, install missing deps, and separate Deno edge functions from app typechecking.

6. Collapse legacy paths.
   Either fully retire:
   - `src/api/client.ts`
   - `app/chat/[phone].tsx`
   - old Python bridge scripts
   or clearly label them as legacy.

7. Only after stabilization:
   continue product features such as push polish, category UX, transcript/report features, and full white-label rollout.

## 14. Best Summary

Sanabil Messages already has a serious foundation. The app, Supabase layer, CI3 bridge ideas, OTP flow, multi-school direction, and operations tooling all exist. The main problem is not lack of code. The main problem is drift:

- drift between old and new schema names
- drift between legacy and current transport layers
- drift between demo CI3 code and live CI3 endpoints
- drift between app code that was prototyped and app code that is meant to run today

If this drift is cleaned up, the project is recoverable and worth continuing from the current base.
