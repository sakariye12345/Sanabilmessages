# Sanabil Messages: Warbixinta Dhammeystirka Production MVP

**Taariikh:** 25 July 2026
**Supabase project:** `fmmatzjhhyhtkpabyhih`
**Ujeeddo:** In si cad loo diiwaangeliyo waxa la dhammeeyey, qaabka
multi-school-ku u shaqaynayo, waxa hadda live traffic-ka hortaagan, iyo
tallaabooyinka production pilot-ka.

## 1. Go'aanka kooban

Core-ka Sanabil Messages hadda wuxuu leeyahay architecture sax ah oo
multi-school ah:

- School kasta waxaa lagu kala saaraa `school_id`.
- App variant kasta wuxuu ku xiran yahay hal school.
- Parent-ku wuxuu geli karaa app-ka school-ka uu allowlist-kiisa ku jiro oo
  keliya.
- OTP, session, inbox, profile, device trust, message routing, Realtime, iyo
  push registration dhammaantood school ayay ku scoped yihiin.
- Hal phone ayaa si ammaan ah uga mid noqon kara in ka badan hal school, isaga
  oo app kasta ku arkaya fariimaha school-kaas oo keliya.
- Edge Functions iyo canonical migrations-ka production-ka waa deployed.
- Sanabil internal Android APK waa built oo diyaar u ah device testing.

Laakiin go'aanka live-ka maanta waa:

> **Controlled code/build test: GO. Real parent traffic: NO-GO ilaa Supabase
> `402 exceed_db_size_quota` restriction-ka laga qaado.**

Tani ma aha app code bug. Waa organization-level Supabase Fair Use
restriction. `bridge-sync` iyo `sync-parents` si ula kac ah ayaa loo hakiyey
si aanay requests fashilmayaa u dhalin log storm kale.

## 2. Shaqada la dhammeeyey

### 2.1 Database incident recovery

Waxa la helay:

- `sync_logs`: 8,449,088 rows.
- `net._http_response`: qiyaastii 584 MB.
- `cron.job_run_details`: qiyaastii 323 MB.
- Laba bridge cron ayaa isla shaqadii daqiiqad kasta laba jeer kicinayey.

Waxa la qabtay:

1. 5,000 failed/partial incident samples ayaa lagu keydiyey
   `sync_log_incident_samples`.
2. Cleanup audit records ayaa la keydiyey si taariikhda incident-ku u
   lumin.
3. Operational history-ga aadka u weyn waa la nadiifiyey.
4. Duplicate cron jobs waa la saaray.
5. Retention cron ayaa la sameeyey:
   - `net._http_response`: 2 saacadood
   - `cron.job_run_details`: 7 maalmood
   - `sync_logs`: 30 maalmood
6. `bridge-sync` logging-ka waxaa laga saaray per-message duplicate logs;
   hadda wuxuu keydiyaa run summary iyo failures muhiim ah.
7. Invalid recipient phone hadda message orphan ah ma abuuro.

Natiijada live database-ka:

| Metric | Xaaladda hadda |
|---|---:|
| Database size | 58 MB |
| Table data | 34 MB |
| Indexes | 11 MB |
| WAL | 144 MB |
| `messages` total | 43 MB |
| `sync_logs` total | 16 KB |

Core business data sida `messages`, `message_recipients`,
`allowed_parents`, `schools`, iyo OTP data lama tirtirin.

### 2.2 Tenant isolation iyo database security

Canonical migration-ka production-ka wuxuu:

- `school_id` ku daray ama ku adkeeyey `message_recipients`,
  `user_devices`, iyo `student_parents`.
- Global phone uniqueness ka beddelay
  `allowed_parents(school_id, phone_number)`.
- Sameeyey unique message identity `(school_id, ci3_id)`.
- RLS ka shiday `allowed_parents`, `messages`, `message_recipients`, iyo
  `user_devices`.
- Ka xiray anon/authenticated direct table access-ka aan loo baahnayn.
- U daayey `message_recipients` Realtime select oo keliya, iyadoo RLS-ku
  xaqiijinayo authenticated parent-ka iyo school-ka.
- Old phone-only RPCs ka dhigay `service_role` only.
- Sameeyey secure school-scoped RPCs:
  - `get_my_inbox`
  - `get_inbox_summary`
  - `get_thread_messages`
  - `get_message_detail`
  - `get_my_profile`
  - `mark_my_recipients`
  - trusted-device RPCs
  - `consume_school_otp`
  - `replace_school_allowed_parents`

Parent replacement-ku wuxuu `parent_id` u isticmaalaa stable key. Haddii
number-ku CI3 source-ka ka beddelmo, parent-ka saxda ah ayaa la update-gareeyaa;
duplicate parent IDs ama phones-na waa la diidaa.

### 2.3 WhatsApp OTP security

OTP contract-ka hadda:

1. App-ku wuxuu diraa `school_id` iyo normalized phone.
2. `request-otp` wuxuu xaqiijiyaa active parent-ka school-kaas.
3. OTP waxaa lagu abuuraa cryptographically secure random.
4. OTP wuxuu leeyahay cooldown, daily cap, expiry, iyo one-time use.
5. Wrong code wuxuu yareeyaa attempts-ka; ugu badnaan 5 attempts.
6. OTP code laguma qoro Edge Function logs.
7. `verify-otp` wuxuu atomic RPC ku consume-gareeyaa OTP-ga.
8. Successful verification wuxuu soo celiyaa Supabase
   `access_token` iyo `refresh_token`.
9. App-ku wuxuu session-ka ku kaydiyaa secure storage; parent-ka mar kasta
   OTP lagama dalbanayo.
10. Trusted device-ku wuxuu ku xiran yahay school + phone + device.

### 2.4 CI3 bridge, parent sync, iyo push

- `bridge-sync` iyo `sync-parents` waxay isticmaalaan internal secret.
- HTTP calls waxay leeyihiin timeout iyo response validation.
- CI3 message duplicate check-ku school ayuu ku scoped yahay.
- Recipient inserts waxay ku xiran yihiin school-ka message-ka.
- Parent source empty ah si lama filaan ah uma tirtiri karo allowlist hore.
- Parent laga saaro CI3 source-ka waa la deactivate-gareeyaa.
- Push token query-gu wuxuu filter-gareeyaa `school_id` iyo phone.
- Push payload-ku wuxuu leeyahay `message_id`, `school_id`, iyo message type.
- Invalid Expo push tokens looma dirayo.
- Notification tap-ku wuxuu xaqiijiyaa school-ka ka hor navigation.

Edge Functions-ka live:

| Function | Version | Status |
|---|---:|---|
| `bridge-sync` | 18 | ACTIVE |
| `notify-parents` | 5 | ACTIVE |
| `request-otp` | 14 | ACTIVE |
| `sync-parents` | 7 | ACTIVE |
| `verify-otp` | 4 | ACTIVE |

### 2.5 Mobile app iyo Android release

- OTP request/verify, inbox, thread, detail, profile, Realtime, iyo devices
  dhammaantood waxay isticmaalaan build-ka `schoolId`.
- Direct recipient table update waxaa lagu beddelay secure RPC.
- Simulated Expo push token waa laga saaray production path.
- Expo Go waxaa loogu daayey UI/dev testing; remote push waxaa lagu
  tijaabinayaa APK dhab ah.
- Legacy `Start Chat`, mock screens, old CI3 client token, iyo duplicate Expo
  config waa laga saaray.
- Unknown `APP_VARIANT` build-ku hadda wuu fail-gareeyaa halkii uu si qarsoon
  Sanabil ugu fallback-gareyn lahaa.
- EAS profiles gaar ah ayaa jira: `sanabil`, `alsunna`, `alxikma`,
  `schoolb`, `schoolc`, iyo `schoold`.
- EAS project ownership iyo Supabase environment variables waa la saxay.

Sanabil final pilot APK:

- App version: `1.0.0`
- Android versionCode: `3`
- Current-source fingerprint:
  `ff42b7d29f8c5a320435c7626fd71649747f4062`
- EAS build:
  https://expo.dev/accounts/alsunna123/projects/sanabil-messages-platform/builds/e5c17b83-699f-4702-9b2b-3307985cdb55
- Direct artifact:
  https://expo.dev/artifacts/eas/VB1DOzeA8rgjDlODikPNCmRtAiwkk9QGphv8kpyxutU.apk
- EAS status: `FINISHED`

Local fallback APK:

- File:
  `C:\Users\hp\Downloads\Sanabil-Messages-1.0.0-build2.apk`
- Android versionCode: `2`
- File size: 67.07 MB
- APK archive/header validation: PASS

Production pilot-ka isticmaal build 3. Build 2 waxaa loo hayaa rollback/fallback.
EAS internal artifact-ku wuxuu dhacayaa 8 August 2026, sidaas darteed final
build 3 waa in local archive loo soo dejiyo ka hor taariikhdaas.

### 2.6 Release checks

| Check | Natiijo |
|---|---|
| TypeScript | PASS, 0 errors |
| Expo Doctor | PASS, 18/18 |
| Android production export | PASS, 1,389 modules |
| School manifest | PASS with missing-brand warnings |
| School matrix | PASS, 4 rows |
| Device matrix | PASS, 4 devices |
| Edge Function Deno checks | PASS, 5/5 |
| Local/remote timestamped migrations | ALIGNED |
| EAS internal APK build | PASS |
| Live Supabase REST | FAIL, HTTP 402 |

`npm audit` wuxuu leeyahay 19 high iyo 11 moderate transitive findings, critical
ma jiro. Fix-ka npm soo jeedinayo wuxuu qasbayaa Expo 57/React Native 0.86 major
upgrade. Lama adeegsan `npm audit fix --force`, sababtoo ah taasi waxay jebin
kartaa SDK 54 release-kan. Arrintan waxaa loo qorsheynayaa upgrade gaar ah kadib
pilot-ka.

## 3. Sida 10 school hal Supabase project ugu wada shaqaynayaan

Looma baahna 10 database ama 10 Supabase project. Hal project ayaa wada haya
shared tables, laakiin row kasta wuxuu leeyahay tenant key:

```text
School A app -> school_id=1 -> School A parents/messages/devices
School B app -> school_id=2 -> School B parents/messages/devices
School C app -> school_id=3 -> School C parents/messages/devices
```

Kala ilaalintu waxay ka dhacdaa afar layer:

1. **Build config:** APK kasta wuxuu leeyahay school ID, app name, package,
   colors, iyo assets u gaar ah.
2. **OTP gate:** Parent-ku waa inuu active ka yahay
   `allowed_parents(school_id, phone_number)`.
3. **Database authorization:** RPC + RLS waxay ku qasbaan JWT phone iyo requested
   school inay is waafaqaan.
4. **Delivery routing:** CI3 bridge, recipient rows, Realtime, device token, iyo
   push dhammaantood waxay raacaan `school_id`.

Tusaale:

- Phone `25263xxxxxxx` wuxuu ka mid noqon karaa School A iyo School B.
- School A APK wuxuu dirayaa `school_id=1`; parent-ku wuxuu arkaa A oo keliya.
- School B APK wuxuu dirayaa `school_id=2`; parent-ku wuxuu arkaa B oo keliya.
- School C APK login-ku wuu diidayaa haddii phone-kaasi C allowlist-ka uusan ku
  jirin.

Hal Pro organization/project architecture ahaan 10 school wuu qaadi karaa.
Capacity-gu wuxuu ku xirnaanayaa actual messages, Realtime, Edge Function
invocations, egress, iyo retention, ee kuma xirna tirada school rows oo keliya.
Pro project-ku wuxuu leeyahay 8 GB included disk, halka database-kan la
nadiifiyey uu hadda yahay 58 MB. Monitoring iyo retention-ka cusub ayaa
muhiim u ah in quota incident-ku uusan soo noqon.

## 4. Blocker-ka hadda iyo xal sax ah

Live REST probe wuxuu soo celinayaa:

```text
HTTP 402
exceed_db_size_quota
```

Supabase Fair Use restriction-ku wuxuu ku salaysan yahay **organization-ka
average daily database size inta billing period-ku socdo**, ma aha live size-ka
58 MB oo keliya. Sidaas darteed cleanup-ku restriction-ka isla markiiba kama
qaadayo.

Xalka degdegga ah waa mid ka mid ah:

1. Organization-ka u upgrade-garee Pro.
2. Haddii uu hore Pro u yahay, disable garee Spend Cap.
3. Haddii aan lacag hadda la bixin, sug billing cycle-ka cusub kadib cleanup-ka.

Faahfaahin rasmi ah:

- https://supabase.com/docs/guides/platform/database-size
- https://supabase.com/docs/guides/platform/manage-your-usage/disk-size

Billing action lama samayn, sababtoo ah waa financial decision uu account
owner-ku ansixinayo.

## 5. Sida service-ka dib loogu shido

Marka Supabase dashboard-ku muujiyo restriction-ka inuu baxay:

1. Hubi in app login ama REST request aanu `402` soo celin.
2. SQL Editor ku shid secure jobs:

```sql
DO $$
DECLARE
  target_job RECORD;
BEGIN
  FOR target_job IN
    SELECT jobid
    FROM cron.job
    WHERE jobname IN (
      'bridge-sync-every-minute',
      'sync-parents-every-15-minutes'
    )
  LOOP
    PERFORM cron.alter_job(target_job.jobid, active => TRUE);
  END LOOP;
END;
$$;
```

3. Sug laba daqiiqo.
4. Hubi `bridge-sync` inuu keeno hal run daqiiqaddii, duplicate run ma aha.
5. Hubi parent sync inuu shaqeeyo 15-kii daqiiqo.
6. Hubi retention job
   `sanabil-operational-retention-hourly` inuu active yahay.
7. La soco `sync_logs`, `cron.job_run_details`, iyo
   `net._http_response` saacadda ugu horreysa.

Haddii errors-ku soo noqdaan, mar kale hakinta:

```sql
UPDATE cron.job
SET active = FALSE
WHERE jobname IN (
  'bridge-sync-every-minute',
  'sync-parents-every-15-minutes'
);
```

## 6. Production pilot-ka la fulinayo

### Stage A: Restore iyo Sanabil smoke test

1. Ka qaad Supabase `402`.
2. Shid labada secure sync jobs.
3. Ku rakib Sanabil APK test Android device.
4. Samee clean login:
   - Allowed Sanabil parent
   - WhatsApp OTP
   - Correct verification
   - Inbox open
5. CI3 ka dir hal attendance iyo hal fee message.
6. Xaqiiji foreground, background, killed-app push, iyo notification tap.
7. Xaqiiji app close/open iyo phone restart kadib inaan OTP cusub la waydiin.

### Stage B: Onboard saddex pilot school

School kasta geli:

- Real `school_id`
- School name iyo app branding
- Real parents API URL
- Real messages API URL
- Secrets-ka Vault/Edge Function secrets
- Test parents
- WhatsApp OTP session
- CI3 source school mapping

Ha isticmaalin School B/C/D `.example` URLs ama placeholder phone numbers-ka
hadda ku jira template-ka.

### Stage C: Build iyo device assignment

```powershell
npx eas-cli build --platform android --profile schoolb
npx eas-cli build --platform android --profile schoolc
npx eas-cli build --platform android --profile schoold
```

APK kasta ku rakib device-kiisa. Hubi app name, package, icon, iyo expected
school ID ka hor login.

### Stage D: Cross-school isolation

Isticmaal:

- Parent A: School A oo keliya
- Parent B: School B oo keliya
- Parent AB: School A iyo B labadaba
- Inactive parent
- Number aan allowlist ku jirin

Pass criteria:

- Wrong-school login waa la diidaa.
- Parent AB wuxuu app kasta ku arkaa school-ka app-kaas oo keliya.
- School A message kuma dhaco School B device.
- Isku CI3 ID laba school ma collision-gareeyo.
- Message dib loo sync-gareeyo duplicate ma abuuro.
- Invalid phone orphan message ma abuuro.
- Cross-school leakage waa `0`.

### Stage E: Soak iyo rollout

1. Ku bilow 4 devices iyo parents tijaabo ah.
2. Samee 24 ilaa 48 saacadood soak test.
3. La soco:
   - OTP success/failure
   - Queue wait time
   - WhatsApp disconnect
   - Bridge sync latency
   - Push delivery
   - App crashes
   - Database iyo log growth
4. Kadib u fur 10 ilaa 20 real parents school kasta.
5. Rollout-ka u kordhi school-by-school; 2,000 users hal mar ha loo furin.

Targets:

- Cross-school leakage: `0`
- Duplicate recipients/messages: `0`
- Orphan messages cusub: `0`
- OTP success: ugu yaraan `95%`
- CI3-to-app p95: ugu badnaan `90 seconds`
- App crash inta soak test-ku socdo: `0`

## 7. Waxyaabaha wali u baahan owner/onboarding action

Kuwani core code bug ma aha, laakiin real launch-ka waa looga baahan yahay:

1. Supabase billing restriction-ka ka qaad.
2. School B/C/D real API endpoints, IDs, parent data, iyo secrets geli.
3. School kasta WhatsApp session gaar ah connect-garee.
4. Missing school icons, adaptive icons, iyo splash images geli.
5. CI3 token-ka current source-ka waa laga saaray, laakiin token-kii hore Git
   history ayuu ku jiraa; CI3 dhinaciisa rotate-garee.
6. WhatsApp dashboard code-ku hadda wuxuu leeyahay operator Basic Auth, rate
   limiting, security headers, iyo localhost-default binding. VPS deployment-ka
   ku dar real dashboard credentials iyo HTTPS reverse proxy.
7. Samee backup restore drill iyo operator contact procedure.
8. Legacy un-timestamped SQL files waxaa loo raray
   `supabase/legacy-migrations`; deployable timestamped chain-ku local/remote
   aligned ayuu yahay.

Unofficial WhatsApp browser automation wuxuu leeyahay account ban iyo rate
limit risk. Queue pacing, cooldown, daily cap, separate session per school, iyo
gradual rollout waa operational controls; ma jirto code dammaanad qaadi karta
in WhatsApp aanu account-ka xannibin.

## 8. Deliverables

- Detailed pre-fix audit:
  `PRODUCTION_MVP_AUDIT_AND_TEST_GUIDE_2026-07-25.md`
- Final completion report:
  `PRODUCTION_COMPLETION_REPORT_2026-07-25.md`
- Generated live checklist:
  `generated/live_rollout_checklist.generated.md`
- Generated device runbook:
  `generated/pilot_execution_runbook.generated.md`
- School manifest:
  `config/schools.manifest.json`
- Pilot school matrix:
  `school_matrix_template.csv`
- Pilot device matrix:
  `pilot_device_test_matrix.csv`
- Canonical production security migration:
  `supabase/migrations/20260725190000_production_tenant_security.sql`
- Secure cron migration:
  `supabase/migrations/20260725193000_secure_cron_schedule.sql`
- Safety pause migration:
  `supabase/migrations/20260725200000_pause_external_sync_until_service_restored.sql`

## 9. Next goal

Goal-ka xiga waa:

> **Supabase restriction restore, secure cron activation, kadib 4-device
> multi-school production pilot oo 24-48 saacadood ah.**

Code architecture-ka muhiimka ah waa diyaar. Hadda guusha waxaa go'aaminaya
billing restore, real school onboarding data, WhatsApp sessions, iyo pilot
evidence.
