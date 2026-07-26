# Sanabil Messages: Production MVP Audit iyo Test Guide

**Taariikhda audit-ka:** 25 July 2026
**Ujeeddo:** In la ogaado waxa dhab ahaan ka dhiman MVP-ga, waxa hadda production-ka joojinaya, iyo sida loo sameeyo pilot test ay ku jiraan school-yo, app instances, WhatsApp OTP sessions, iyo mobile devices kala duwan.

## 1. Go'aanka kooban

Sanabil Messages wuxuu caddeeyay core flow-ga hal school:

1. Parent-ka ku jira allowlist-ka ayaa phone number gelinaya.
2. OTP waxaa dira WhatsApp automation-ka.
3. OTP verification iyo Supabase session way shaqeeyaan.
4. Fariin CI3 laga soo diray waxay gaadhaysaa parent-ka oo app-ka ayay kasoo muuqataa.
5. Trusted device iyo realtime refresh waxay shaqeeyeen tijaabadii hore.

Laakiin app-ka **wali looma fasixi karo real parents ama multi-school production pilot**. Audit-kan wuxuu helay blockers muhiim ah oo ku jira live Supabase, tenant isolation, push notifications, OTP security, EAS builds, iyo release management.

Xaaladda hadda waa:

- **Core proof-of-concept:** DONE
- **Hal-school controlled demo:** Wuu shaqeeyay
- **Multi-school production architecture:** Qayb ahaan diyaar, laakiin isolation-ku wali ma dhamma
- **Real production pilot:** NO-GO ilaa P0 blockers-ka hoose la xalliyo

## 2. Caddeynta live system-ka

### 2.1 Supabase service-ku hadda waa restricted

REST API-ga live project-ka wuxuu soo celiyay:

```text
402 exceed_db_size_quota
```

Taasi waxay ka dhigan tahay app-ka, Auth flow-ga ku tiirsan database-ka, REST, iyo qayb ka mid ah Edge Functions-ku inaanay si la isku hallayn karo u shaqayn karin hadda.

Database inspection-ku wuxuu muujiyay:

| Table | Qiyaasta rows | Total size |
|---|---:|---:|
| `sync_logs` | 8,432,874 | 1,272 MB |
| `messages` | 117,752 | 39 MB |
| `message_recipients` | 255 | 176 KB |
| `otp_queue` | 48 | 136 KB |
| `allowed_parents` | 5 | 96 KB |

Waxaa jira **117,497 orphan messages**: message rows aan lahayn wax recipient ah.

Sababta ugu weyn:

- Laba cron job ayaa `bridge-sync` kicinaya daqiiqad kasta.
- `bridge-sync` wuxuu sameeyaa log badan message kasta iyo duplicate check kasta.
- Message-ka waxaa la insert-gareeyaa ka hor inta phone number-ka recipient-ka la xaqiijin; phone khaldan wuxuu ka tagi karaa orphan message.

### 2.2 Live database security waa furan tahay

Live DB inspection-ku wuxuu xaqiijiyay:

- `messages`: RLS disabled
- `message_recipients`: RLS disabled
- `allowed_parents`: waxaa saaran public read policies
- `message_recipients`: waxaa saaran `Allow All Access` public policy
- `messages`: waxaa saaran public insert/select policies

Supabase wuxuu ku talinayaa in table kasta oo ku jira exposed `public` schema lagu ilaaliyo RLS. Marka quota restriction-ka laga qaado, xaaladdan hadda jirta waxay keeni kartaa in anon key lagu akhriyo ama lagu beddelo parent messages iyo recipient data.

### 2.3 Multi-school isolation wali ma dhamma

Build kasta wuxuu leeyahay `schoolId`, laakiin school-kaas looma gudbiyo OTP functions ama message RPCs:

- `request-otp` wuxuu qaataa `phone` oo keliya.
- `verify-otp` wuxuu qaataa `phone` iyo `code` oo keliya.
- `get_my_inbox`, `get_inbox_summary`, `get_thread_messages`, `get_message_detail`, iyo `get_my_profile` ma qaataan `school_id`.
- RPC-yadu fariimaha waxay ku scope-gareeyaan phone oo keliya.
- `allowed_parents_phone_key` wuxuu phone number-ka ka dhigay global unique, sidaas darteed isku parent laguma dari karo laba school.
- Push dispatcher-ku devices wuxuu ku raadiyaa phone oo keliya, school ma raaciyo.

Natiijadu waxay noqon kartaa:

- Parent School A ku jira inuu OTP ka codsan karo app-ka School B.
- Parent laba school ku jira inuu labada school fariimahooda ku arko hal app.
- Push-ka School A inuu gaadho app/device loogu talagalay School B.

### 2.4 Live multi-school data wali lama gelin

Live database-ka maanta wuxuu leeyahay:

- Hal school oo keliya: `Sanabil School`
- Shan allowed parents
- WhatsApp status-ka school-ka: `WAITING_QR`

`school_matrix_template.csv` wuxuu leeyahay School B/C/D URLs ku dhammaanaya `.example`, test phones, support phones, iyo branding placeholders. Validator-ku `.example` uma aqoonsana placeholder, sidaas darteed wuxuu si khaldan u sheegay in matrix-ku valid yahay.

### 2.5 Build iyo release readiness wali ma gudbin

Checks la sameeyay:

- Android JS production export: PASS
- School manifest structural validation: PASS, laakiin school assets badan way maqan yihiin
- TypeScript: FAIL
- Expo Doctor: 15/18 PASS
- EAS access: FAIL
- Dependency audit: 30 findings, oo ay ku jiraan high iyo critical findings

Expo Doctor wuxuu helay:

- `expo-linking` peer dependency waa maqan yahay.
- Expo SDK patch versions isma waafaqaan.
- `app.json` iyo `app.config.js` waa laba config oo is khilaafaya.

EAS CLI-ga hadda wuxuu ku login yahay account aan permission u lahayn project ID-ga ku jira `app.config.js`, sidaas darteed cloud build lama bilaabi karo.

## 3. P0: Waxyaabaha khasabka ah ka hor real production pilot

### P0.1 Soo celi Supabase service-ka, kadib jooji log storm-ka

Habka saxda ah:

1. Temporarily disable labada `bridge-sync` cron job.
2. Upgrade organization-ka/project-ka Supabase Pro si `402` restriction-ka degdeg looga baxo.
3. Samee backup ka hor cleanup.
4. Archive ama nadiifi `sync_logs`.
5. Nadiifi 117,497 orphan messages kadib marka query-ga cleanup-ka si taxaddar leh loo review-gareeyo.
6. Ku dar retention job, tusaale ahaan in `sync_logs` lagu hayo 7 ilaa 14 maalmood.
7. Ka saar per-message duplicate logs; keydi hal summary row per school per run, iyo failure details oo keliya.
8. Hal cron job oo keliya u daa `bridge-sync`.
9. Ku dar monitoring database growth iyo failed sync count.

Pro plan-ka hadda wuxuu leeyahay 8 GB disk per project; hal project ayaa 10 school pilot qaadi kara haddii log storm-ka, orphan rows, indexes, iyo retention-ka la saxo.

### P0.2 Samee hal canonical security migration

Migration cusub oo timestamp sax ah leh waa inuu:

1. Enable RLS ku sameeyo `messages` iyo `message_recipients`.
2. Drop ku sameeyo simulation, public read, iyo `Allow All Access` policies.
3. Ka xiro `allowed_parents`, `schools`, `otp_queue`, `otp_logs`, `sync_logs`, iyo `user_devices` direct client access.
4. RPC-yada u oggolaado `authenticated` oo keliya; helper-ka `get_user_id_by_phone` u oggolaado `service_role` oo keliya.
5. Abuuro secure RPC loogu talagalay `mark delivered/seen`; app-ku yuusan direct update ku samayn recipient table.
6. `school_id`, `message_id`, iyo phone fields-ka muhiimka ah ka dhigo `NOT NULL` marka bad data la nadiifiyo.
7. Ku dar indexes:
   - `message_recipients(phone_number, created_at desc)`
   - `messages(school_id, created_at desc)`
   - `allowed_parents(school_id, phone_number)`
   - `user_devices(school_id, phone_number, is_active)`
8. Ka saar global unique constraint-ka `allowed_parents(phone_number)`.
9. Ku beddel unique constraint `allowed_parents(school_id, phone_number)`.

Migration history-ga hadda ma aha reproducible: 20 migration files Supabase CLI wuu skip-gareeyaa sababtoo ah filenames-koodu ma laha timestamp sax ah. Remote migration history-ga waxaa ka muuqda laba migrations oo keliya. Waa in la sameeyo baseline/canonical migration chain ka hor release.

### P0.3 Dhammee school-scoped contract-ka

App instance kasta waa inuu dirayaa build-time `schoolId`:

```json
{
  "school_id": 4,
  "phone": "25263xxxxxxx"
}
```

Isbeddellada loo baahan yahay:

1. `request-otp(phone, school_id)` wuxuu xaqiijiyaa row-ga `allowed_parents` ee school-kaas.
2. `verify-otp(phone, code, school_id)` wuxuu xaqiijiyaa OTP-ga school-kaas.
3. Inbox/profile/thread/detail RPC kasta wuxuu qaataa `p_school_id`.
4. RPC kasta wuxuu xaqiijiyaa in JWT phone-ku active parent ka yahay school-ka la codsaday.
5. `user_devices` waxaa lagu daraa `school_id`; push token-ka waxaa lagu xiraa school + phone + device/app.
6. Push query-gu wuxuu filter-gareeyaa `phone_number` iyo `school_id`.
7. Realtime callback-ku wuxuu refetch-gareeyaa school-scoped RPC oo keliya.
8. School A app waa inuu diidaa parent School B oo keliya ku jira.
9. Parent labada school ku jira waa inuu School A app ku arkaa A oo keliya, School B app-na B oo keliya.

### P0.4 Push notification-ka ka dhig release-ready

Tijaabadii Expo Go ma xaqiijin push notifications. Expo Go Android kama taageero remote push notifications sida production build, sidaas darteed APK release/internal build ayaa khasab ah.

Waxa la saxayo:

1. Ku dar `expo-notifications` config plugin.
2. Install `expo-linking` iyo direct dependencies-ka loo baahan yahay.
3. Ka saar simulated push token-ka production path-ka.
4. Haddii permission la diido, login-ku waa inuu sii shaqeeyaa.
5. `user_devices.fcm_token` waa inuu nullable noqdaa ama device registration-ku si sax ah u maareeyaa token la'aan.
6. Dooro hal push dispatcher: `bridge-sync` ama `notify-parents`; labadaba isku mar ha shaqayn.
7. Push payload kasta ha yeesho `message_id`, `school_id`, iyo `type`.
8. Hubi push ticket response, kana disable-garee `DeviceNotRegistered` tokens.
9. Test-garee foreground, background, killed app, notification tap, iyo notification permission denied.

### P0.5 OTP-ga ka ilaali brute force iyo school mix-up

Waxa la saxayo:

1. Ku dar maximum verify attempts, tusaale 5 attempts per OTP.
2. Wrong attempts kadib lock ama fail-garee OTP-ga.
3. Samee per-phone iyo per-IP rate limiting.
4. OTP code ku samee cryptographically secure random generation.
5. Ka saar OTP code logging-ka Edge Function logs.
6. Code-ka clear/hash-garee marka la verify-gareeyo ama uu expire-gareeyo.
7. Kala saar:
   - Parent request cooldown
   - School WhatsApp send pacing
8. `session_password` hadda ma laha expiry dhab ah inkasta oo response-ku sheegayo minutes. Habka adag waa Edge Function-ku inuu soo saaro session tokens, kadib random password-ka isla markiiba rotate-gareeyo.
9. `sync-parents` waa inuu inactive ka dhigaa parent laga saaray source allowlist-ka; upsert keliya kuma filna access revocation.

### P0.6 Xidho WhatsApp dashboard-ka iyo internal Edge Functions

`whatsapp-service/server.js` routes-ka start, stop, pause, resume, status, summary, iyo QR ma laha authentication.

Ka hor public production:

1. Port `4000` ha noqon mid internet-ka oo dhan u furan.
2. Ku hor mari HTTPS reverse proxy.
3. Ku dar operator authentication ama private VPN/access gateway.
4. `/api/wa/start`, `/stop`, `/pause`, iyo `/resume` ha yeeshaan authorization.
5. QR data ha u muuqan anonymous user.
6. `bridge-sync`, `sync-parents`, iyo `notify-parents` ha xaqiijiyaan cron/webhook secret; anon key keliya yuusan ku filnaan.
7. PM2 process-ka ku socodsii non-root user, resource limits, startup persistence, iyo log rotation.

Audit time-ka `72.62.28.186:4000` iyo `:9000` labaduba timeout ayay ahaayeen, halka live DB school status-ku ahaa `WAITING_QR`. VPS process, firewall, reverse proxy, iyo WhatsApp session waa in dib loo xaqiijiyaa.

### P0.7 Rotate garee credentials-ka ku baxay public GitHub

GitHub repository-gu waa public. CI3 static API token wuxuu ku jiraa tracked source iyo SQL history.

Waxa la qabanayo:

1. Rotate garee CI3/API token-ka hadda jira.
2. Ka saar token-ka source files-ka iyo comments-ka.
3. Tokens-ka ku kaydi Supabase secrets/Vault ama server-only environment.
4. Samee secret scan dhammaan Git history-ga.
5. PAT ama password kasta oo hore chat, terminal, ama public repo loogu isticmaalay dib u rotate-garee.

### P0.8 Dhis EAS profiles la hubo

Generic `school-apk` profile-ku ma dejinayo `APP_VARIANT` gudaha EAS builder. Shell env local ah keliya laguma tiirsanaan karo.

Habka saxda ah:

1. Samee explicit profile school kasta:

```json
"schoolb-pilot": {
  "distribution": "internal",
  "env": {
    "APP_VARIANT": "schoolb"
  },
  "android": {
    "buildType": "apk"
  }
}
```

2. Hubi account-ka EAS ee leh project permission.
3. Go'aami in variants-ku wada isticmaali karaan hal EAS project/slug ama school kasta loo sameeyo EAS project u gaar ah.
4. Ku dar remote app versioning iyo `autoIncrement`.
5. School kasta sii icon, splash, package name, support info, iyo display name dhab ah.
6. PowerShell command-ka saxda ah:

```powershell
npx eas-cli build --platform android --profile schoolb-pilot
```

Ha isticmaalin `APP_VARIANT=schoolb ...` gudaha PowerShell.

## 4. P1: Waxyaabaha muhiimka ah ka hor public launch

Kuwani controlled pilot-ka ma wada joojinayaan, laakiin waa in la dhammeeyaa ka hor rollout ballaadhan:

1. Ka saar `Start Chat`, `new-chat`, iyo legacy `chat/[phone]`; waa mock/old CI3 path oo parent receive-only MVP-ga ka baxsan.
2. Ka saar hardcoded CI3 URL/token client code-ka.
3. TypeScript ka dhig clean, oo u samee app iyo Deno functions configs kala duwan.
4. Sax `OfflineBanner` import-ka iyo Animated private field usage.
5. Ku dar crash/error monitoring.
6. Ku dar local persistence si fariimihii hore offline loo akhriyi karo.
7. Ku dar privacy policy, data retention policy, support channel, iyo account/device recovery procedure.
8. Samee backups iyo restore drill.
9. Ku dar CI checks: typecheck, Expo Doctor, manifest validation, dependency review, iyo production export.
10. Samee release tag iyo rollback APK; hadda core changes badan wali Git commit ma aha.

## 5. Sida shaqada loo dhammeeyo, sida ay u kala horreyso

### Stage 1: Emergency stabilization

1. Pause duplicate bridge cron jobs.
2. Upgrade Supabase Pro ama restore service-ka.
3. Backup database.
4. Deploy bridge logging/orphan fix.
5. Cleanup logs iyo orphan rows.
6. Hal bridge cron dib u shid.
7. Xaqiiji REST API-ga inuu kasoo noqday `200`, database growth-kuna joogsaday.

### Stage 2: Security iyo multi-school contract

1. Samee canonical timestamped migration.
2. Xidho RLS/policies/grants.
3. Ku dar school-scoped OTP/RPC/device/push contract.
4. Fix allowed parent uniqueness.
5. Fix parent deactivation sync.
6. Deploy migrations iyo Edge Functions.
7. Samee anon-security tests iyo cross-school tests.

### Stage 3: Mobile release hardening

1. Update Expo SDK 54 patch dependencies.
2. Install missing peer dependencies.
3. Remove legacy/mock screens.
4. Fix push registration iyo notification navigation.
5. Fix TypeScript.
6. Configure school-specific EAS profiles iyo versioning.
7. Build hal Sanabil internal APK.
8. Ku tijaabi real Android device, app foreground/background/killed.

### Stage 4: Multi-school pilot setup

1. Ku beddel School B/C/D placeholders real demo URLs, API tokens, names, packages, phones, iyo assets.
2. Seed-garee school rows iyo allowed parents.
3. Samee WhatsApp session school kasta.
4. Build afar APK.
5. Ku rakib afar device.
6. Fuliso test matrix-ka qaybta xigta.

### Stage 5: Soak iyo go-live decision

1. Samee 24 ilaa 48 saacadood controlled pilot.
2. La soco sync failures, OTP queue, WhatsApp disconnects, database growth, push tickets, app crashes, iyo cross-school leakage.
3. Haddii dhammaan gates-ku PASS yihiin, ku dar parents tiro yar oo dhab ah.
4. Rollout-ka u kordhi school-by-school, halkii hal mar 2,000 users loo furi lahaa.

## 6. Tools-ka loo adeegsanayo

| Shaqo | Tool |
|---|---|
| Mobile dependency/config audit | Expo Doctor |
| APK cloud build iyo sharing | EAS Build internal distribution |
| Device install/logs | Android Platform Tools: `adb` iyo `adb logcat` |
| Database migrations/functions | Supabase CLI |
| Database security/performance | Supabase Security Advisor, SQL Editor, `inspect db table-stats` |
| VPS process control | PM2 |
| Reverse proxy/TLS | Nginx ama Caddy |
| Operator-only dashboard access | Firewall/VPN/access gateway + authentication |
| API contract testing | Bruno/Postman ama PowerShell `Invoke-RestMethod` |
| Mobile UI repeatable smoke tests | Maestro, optional kadib manual flow stabilizes |
| Issue/test tracking | `rollout_status_tracker.csv` ama issue board |

## 7. Preflight commands

### 7.1 Local code

```powershell
npm ci
npx expo-doctor
npx tsc --noEmit
npm run schools:validate
npm run schools:matrix:validate
npm run schools:devices:validate
npx expo export --platform android --clear
```

Pass criteria:

- Expo Doctor: 18/18
- TypeScript: 0 errors
- School validators: 0 errors, 0 placeholders
- Production export: PASS

`npm audit fix --force` si indho la'aan ah ha loo ordin; wuxuu keeni karaa Expo major-version breaking upgrade.

### 7.2 Supabase

```powershell
npx supabase projects list
npx supabase migration list --linked
npx supabase functions list
npx supabase inspect db table-stats --linked
```

Pass criteria:

- Local iyo remote migrations waa isku mid.
- Dhammaan Edge Functions versions-ka la filayo waa ACTIVE.
- REST API ma soo celinayo `402`.
- Security Advisor ma hayo RLS critical findings.
- `sync_logs` si xad-dhaaf ah uma korayo.

### 7.3 EAS

```powershell
npx eas-cli whoami
npx eas-cli config --platform android --profile sanabil-pilot
npx eas-cli build --platform android --profile sanabil-pilot
```

Pass criteria:

- Account-ku wuxuu leeyahay project permission.
- Resolved app name, package, school ID, iyo variant waa sax.
- Internal APK build-ku wuu dhammaadaa.

### 7.4 Android devices

```powershell
adb devices
adb install -r .\builds\sanabil.apk
adb logcat
```

Marka clean-install test la samaynayo:

```powershell
adb shell pm clear com.sanabil.messages
```

`pm clear` wuxuu tirtirayaa local session-ka app-kaas; isticmaal test device oo keliya.

## 8. Full production pilot test matrix

### 8.1 Test data

Samee ugu yaraan:

- Parent A: School A oo keliya
- Parent B: School B oo keliya
- Parent AB: School A iyo School B labadaba
- Parent inactive: hore active u ahaa, kadib CI3 laga deactivate-gareeyay
- Phone invalid
- Phone aan allowlist ku jirin

### 8.2 OTP tests

| Test | Natiijada la rabo |
|---|---|
| Allowed parent + correct school | OTP queued oo WhatsApp sax ah laga diro |
| School A parent oo School B app gala | Request waa la diidaa |
| Wrong OTP | Error; attempt counter kordha |
| Wrong OTP 5 jeer | OTP locked/failed |
| Expired OTP | Lama aqbalo |
| OTP mar labaad la isticmaalo | Lama aqbalo |
| Resend gudaha cooldown | OTP cusub lama abuuro |
| WhatsApp disconnected | Queue ma lumin; UI wuxuu bixiyaa xaalad cad |
| Daily cap reached | School OTP pause + operator alert |
| Notification permission denied | Login wuu dhammaadaa; push keliya ayaa maqnaada |

### 8.3 Message routing tests

| Test | Natiijada la rabo |
|---|---|
| School A message -> Parent A | School A app/device oo keliya |
| School B message -> Parent B | School B app/device oo keliya |
| School A message -> Parent AB | School A app oo keliya |
| School B message -> Parent AB | School B app oo keliya |
| Isku CI3 ID laba school | Labaduba si gaar ah ayay u shaqeeyaan |
| Isku CI3 message dib loo sync-gareeyo | Duplicate recipient/message lama abuuro |
| Invalid recipient phone | Orphan message lama abuuro |
| Long Somali message | UI ma jabo; text waa la akhriyi karaa |
| App foreground | Realtime/refetch wuxuu keenaa fariinta |
| App background | Push notification ayaa timaadda |
| App killed | Push ayaa timaadda; tap-ku message sax ah ayuu furaa |
| Offline kadib reconnect | Fariimaha cusub ayaa soo baxa |

### 8.4 Device/session tests

| Test | Natiijada la rabo |
|---|---|
| App close/open | OTP mar kale lama waydiiyo |
| Phone restart | Session waa soo noqdaa |
| Logout | Current device trust waa revoked |
| Remote device revoke | Device-ka la saaray access ma sii haysto |
| Same parent laba device | Labada device policy-ga la oggol yahay ayay raacaan |
| Uninstall/reinstall | OTP cusub ayaa loo baahan yahay |
| App upgrade | Session iyo messages ma lumin |

### 8.5 Failure recovery tests

1. CI3 messages endpoint dami muddo kooban; school-yada kale waa inay sii shaqeeyaan.
2. Parents endpoint error geli; existing active parents waa inaan si khalad ah loo tirtirin.
3. WhatsApp session disconnect; queue waa inuu sugaa oo aanu duplicate dirin.
4. PM2 process restart; connected sessions iyo pending queue waa inay recover-gareeyaan.
5. Supabase unavailable; app-ku waa inuu bixiyo error/offline state, aanu empty inbox uga dhigin “xog ma jirto”.
6. Duplicate cron invocation; database idempotency waa inuu joojiyaa duplicate rows.

## 9. Performance iyo load test

Ha ku bilaabin 2,000 real WhatsApp OTP isku mar. Unofficial WhatsApp browser automation wuxuu leeyahay ban iyo throttling risk.

Habka saxda ah:

1. Samee dry-run/staging sender oo queue-ga process-gareeya laakiin WhatsApp dhab ah aan dirin.
2. Load test request/verify contract-ka iyo database indexes-ka staging data.
3. Real WhatsApp test ku bilow tiro yar per school.
4. Kordhi batch-ka si tartiib ah adigoo eegaya:
   - Queue wait time
   - Send failures
   - WhatsApp disconnect/ban signals
   - Edge Function latency
   - Database CPU/disk growth

Pilot targets:

- Cross-school leakage: `0`
- Duplicate recipients: `0`
- Orphan messages cusub: `0`
- OTP success rate: ugu yaraan `95%`
- CI3-to-app message p95: ugu badnaan `90 seconds`
- Stuck OTP `PROCESSING` ka badan 5 minutes: `0`
- App crashes inta soak test-ku socdo: `0`
- Database/log growth: xad la fahmi karo oo joogto ah

## 10. GO / NO-GO checklist

Production pilot waa `GO` oo keliya marka:

- [ ] Supabase `402` restriction-ka laga baxay
- [ ] Duplicate bridge cron la saaray
- [ ] Log storm iyo orphan creation la saxay
- [ ] Backup la xaqiijiyay
- [ ] `messages` iyo `message_recipients` RLS waa enabled
- [ ] Public/simulation policies waa la saaray
- [ ] School-scoped OTP iyo RPC contract waa deployed
- [ ] Same phone laba school waa la taageerayaa
- [ ] Push tokens school ayay ku scoped yihiin
- [ ] Wrong-school login test waa PASS
- [ ] Parent AB isolation test waa PASS
- [ ] OTP brute-force protection waa deployed
- [ ] WhatsApp dashboard waa protected
- [ ] Leaked CI3 token waa rotated
- [ ] Expo Doctor 18/18
- [ ] TypeScript 0 errors
- [ ] EAS account permission waa sax
- [ ] Afar explicit app profiles way build-gareeyeen
- [ ] Foreground/background/killed push tests waa PASS
- [ ] 24-48 hour soak test waa PASS
- [ ] Rollback APK iyo database recovery plan way jiraan

## 11. Next goal

Shaqada xigta ee ugu saxan ma aha in afar APK isla markiiba la build-gareeyo. Goal-ka xiga waa:

> **Production Stabilization and Tenant Isolation:** Supabase quota restore, log/orphan cleanup, RLS lockdown, school-scoped OTP/RPC/device/push contract, kadib hal Sanabil release APK oo push iyo auth si buuxda loogu xaqiijiyo.

Marka goal-kaas PASS noqdo, School B/C/D onboarding iyo 4-device pilot wuxuu noqonayaa hawl operational ah oo ammaan iyo natiijo cad leh.

## 12. Official references

- Supabase RLS: https://supabase.com/docs/guides/database/postgres/row-level-security
- Supabase production checklist: https://supabase.com/docs/guides/deployment/going-into-prod
- Supabase migrations: https://supabase.com/docs/guides/deployment/database-migrations
- Supabase disk usage: https://supabase.com/docs/guides/platform/manage-your-usage/disk-size
- Expo SDK 54 notifications: https://docs.expo.dev/versions/v54.0.0/sdk/notifications/
- Expo internal distribution: https://docs.expo.dev/build/internal-distribution/
- Expo app variants: https://docs.expo.dev/build-reference/variants/
- Expo app version management: https://docs.expo.dev/build-reference/app-versions/
