# Live Recheck

Date: 2026-05-18

## Clarified Endpoint Mapping

Confirmed from user guidance plus live verification:

- `https://demo.saafisystems.com`:
  - used for demo / testing of `allowed_parents`
  - live endpoint working:
    - `GET /index.php/api/v1/parents/allowed`

- `https://schoolsfls443dr4rsm53m.shihaab.tech`:
  - real CI3 backend for message queue reads
  - live endpoint working:
    - `GET /messages/contacts`
  - `messages/update_status` route exists as well

## What Was Rechecked

### Supabase Health

Live Supabase project is responding and healthy.

Verified counts:

- `schools`: 1
- `allowed_parents`: 5
- `messages`: 100771
- `message_recipients`: 0
- `user_devices`: 0
- `otp_queue`: 41
- `otp_logs`: 0
- `sync_logs`: 139135

### Current `schools` Row

The active school row is still:

- `ci3_url = https://demo.saafisystems.com`
- `ci3_token = YOUR_SCHOOL_API_TOKEN`

This is the root live misconfiguration.

Reason:

- `bridge-sync` reads messages from `${school.ci3_url}/messages/contacts`
- current `school.ci3_url` points to the demo allowlist host
- that host returns `404` for all tested message queue paths

### Sync Logs

Latest live logs on 2026-05-18 still show:

- `FAILED`
- `Error in school Sanabil School: Downstream Sync Failed: CI3 HTTP Error: 404`

This confirms the cron job is alive, Supabase is alive, and the current failure is endpoint configuration, not database downtime.

### Real Message Backend

Verified:

- `GET https://schoolsfls443dr4rsm53m.shihaab.tech/messages/contacts`
- returns `200`
- current body: `[]`

Meaning:

- the real message backend is reachable
- it is not currently returning queued messages
- but it is the correct host family for downstream sync

### Demo Allowlist Backend

Verified:

- `GET https://demo.saafisystems.com/index.php/api/v1/parents/allowed`
- returns `200`
- returns 5 parent records

Meaning:

- demo allowlist flow is alive
- this host should not be used as the message queue source

## Security State Recheck

Using the anon key, live reads currently succeed for:

- `allowed_parents`
- `messages`
- `message_recipients` (empty result, but public route responds)

Observed:

- anon can read `messages` directly
- anon can read `allowed_parents` directly
- this matches the screenshot warnings about RLS being disabled / public exposure

Implication:

- current system is operationally permissive
- some app flows may work only because tables are public, not because secure policy design is complete

## Bottom Line

The updated interpretation is:

1. Supabase is on and healthy.
2. The cron / bridge job is running.
3. The current live failure is not “database off”.
4. The active `schools` config is pointed at the demo allowlist host for downstream sync.
5. The real message backend host is different and reachable.
6. Even after fixing the host mapping, the real `/messages/contacts` endpoint currently returns an empty list, so there are two separate questions:
   - wrong host configured in Supabase
   - real message queue currently empty
