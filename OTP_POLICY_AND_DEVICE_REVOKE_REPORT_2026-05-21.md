# Warbixinta OTP Policy Hardening iyo Device Revoke

Taariikh: `2026-05-21`
Mashruuc: `Sanabil Messages`

## Ujeeddada Shaqadan

Shaqadan waxay ahayd laba arrimood oo aad muhiim ugu ah production-ka:

1. `WhatsApp OTP` in aan laga dhigin sender aan xeer lahayn
2. trusted-device system-ka in lagu daro `revoke control`

Macnaha guud:

- OTP volume-ka waa in la xakameeyaa
- school walba waa inuu yeeshaa pause/cooldown/cap controls
- device trusted ah waa in la damin karaa haddii loo baahdo

## Waxa La Beddelay

### 1. Device revoke contract

Waxaa la sameeyay migration cusub:

- [supabase/migrations/otp_policy_and_device_revoke.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/otp_policy_and_device_revoke.sql)

Waxa lagu daray:

- `revoke_my_device(device_id)`
- `list_my_devices()`

Shaqadoodu waa:

- current user-ku inuu damin karo device trusted ahaa
- devices-ka user-ka in si secure ah loo soo saari karo

### 2. Logout wuxuu noqday logout dhab ah

File:

- [src/store/auth.ts](/C:/Users/hp/SanabilMessages/src/store/auth.ts)

Waxa hadda dhacaya marka user-ku `Log Out` sameeyo:

1. current device trust-ka waa la revoke-gareeyaa
2. kadib Supabase session-ka waa la xiraa

Tani waxay muhiim u tahay in `logout` aanu uga tagin device-ka trusted ahaan sidii hore.

### 3. Device trust helper waa la ballaariyay

File:

- [src/services/deviceTrust.ts](/C:/Users/hp/SanabilMessages/src/services/deviceTrust.ts)

Waxa lagu kordhiyay:

- `getCurrentDeviceId()`
- `revokeCurrentDeviceTrust()`

Tani waxay app-ka siisay awood uu current device-ka si nadiif ah uga saaro trust-ka.

### 4. School-level OTP policy controls

Table:

- `schools`

Waxaa lagu daray:

- `otp_is_paused`
- `otp_pause_reason`
- `otp_pause_until`
- `otp_cooldown_seconds`
- `otp_daily_cap`
- `otp_last_sent_at`
- `otp_last_error_at`
- `otp_consecutive_failures`

Macnaha:

- school walba wuxuu yeelan karaa xeerar u gaar ah
- OTP dispatch ma aha hal behavior oo qof walba wada saaran

### 5. WhatsApp dispatcher hardening

File:

- [whatsapp-service/server.js](/C:/Users/hp/SanabilMessages/whatsapp-service/server.js)

Waxyaabaha cusub ee lagu daray:

- `stale PROCESSING recovery`
- `max attempts`
- `daily cap check`
- `cooldown check`
- `pause/resume API`
- `auto-pause after repeated failures`
- `newer OTP supersedes older OTP`
- expired pause auto-clear

## Nidaamka Cusub Sidee U Shaqaynayaa

Marka `otp_queue` uu leeyahay rows cusub:

1. service-ku marka hore wuxuu hagaajiyaa rows `PROCESSING` ku xayirmay
2. wuxuu eegaa school-ka:
   - ma paused baa?
   - cooldown ma ku jiraa?
   - daily cap ma gaaray?
3. wuxuu hubiyaa in OTP-gaasi aanu ahayn mid duug ah oo ka horeeya OTP cusub
4. haddii shuruudaha oo dhan sax yihiin, markaas ayuu diraa
5. haddii failures isku xigaan bataan, school-kaas si ku meel gaar ah ayuu u `auto-pause` gareeyaa

Tani waxay si weyn u yaraynaysaa:

- spam bursts
- repeated sends
- stale queue buildup
- operator blind spots

## Waxa Live Loo Fuliyay

Waxa live ahaan database-ka loogu orodsiiyay:

- [supabase/migrations/otp_policy_and_device_revoke.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/otp_policy_and_device_revoke.sql)

Waxaan xaqiijiyay in DB-ga live uu hayo:

- `revoke_my_device`
- `list_my_devices`

## Waxa Aan Weli Live Ka Dhigin

Qaybtan muhiimka ah:

- [whatsapp-service/server.js](/C:/Users/hp/SanabilMessages/whatsapp-service/server.js) code-ka cusub **repo-ga wuu galay**, laakiin VPS-kaaga WhatsApp service-ka wali uma guurin si toos ah

Marka si dhab ah policies-kan ugu shaqeeyaan runtime:

1. VPS-ka repo-ga latest ha la pull gareeyo
2. `whatsapp-service` process-ka ha la restart gareeyo

Haddii taas aan la samayn, DB migration-ka wuu noolaanayaa laakiin sender behavior-ku wali wuxuu ahaan karaa kii hore ee service-ka currently ku socda VPS-ka.

## Verification

Waxaan sameeyay:

- `node --check whatsapp-service/server.js`
- type scan ku wajahan files-ka shaqadan taabanaya

Natiijo:

- syntax-ku waa sax
- files-ka shaqadan ku jira ma keenin type error toos ah

Hal smoke-check oo live ah wuxuu ku dhacay:

- `cli_login_postgres` auth failure / circuit breaker

Tani waxay ahayd Supabase CLI login-role issue, mana muujinayso in migration-ku fashilmay, sababtoo ah RPC-yada cusub si gooni ah ayaan u xaqiijiyay inay DB-ga ku jiraan.

## Next Goal

`VPS service rollout + app-side device management UX`

Tani waa shaqada xigta ee ugu saxan:

1. `whatsapp-service` cusub ha galo VPS-ka
2. school policies ha noqdaan active runtime ahaan
3. profile ama admin flow ha helo device list / revoke UX
