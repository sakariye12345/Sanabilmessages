# Sanabil Messages: Production Hardening iyo Client Pilot

**Taariikh:** 31 July 2026
**Xaaladda:** Code-ku waa Release Candidate; live deployment-ka wali waa **NO-GO**

## 1. Gunaanad

Sanabil Messages core architecture-keedu hadda wuxuu diyaar u yahay production
pilot ballaadhan. OTP login, trusted device, multi-school isolation, CI3 bridge,
Realtime messages, WhatsApp OTP queue, push delivery iyo school variants waxaa
loo adkeeyey qaab production ah.

App-ka lama siin karo clients wali, sababtoo ah live Supabase project-ku wuxuu
soo celinayaa:

```text
HTTP 402: exceed_db_size_quota
```

Sidoo kale school variants-ka qaarkood wali waxay leeyihiin placeholder data,
EAS project IDs aan la samayn, branding assets aan jirin, test phones aan dhab
ahayn iyo WhatsApp sessions aan CONNECTED ahayn. Production preflight-ku si
ula kac ah ayuu arrimahan u xannibayaa.

## 2. Waxyaabaha La Adkeeyey

### OTP iyo trusted device

- Device cusub waxa trusted ka dhigi kara oo keliya `verify-otp` kadib OTP sax
  ah. Authenticated session keliya ma abuuri karo trusted device.
- Labadii legacy `register_my_device` RPC waa laga saaray migration-ka. Waxay
  lahaayeen security bypass iyo ambiguous-column runtime defects.
- `touch_my_device` wuxuu update-gareeyaa oo keliya device hore loo verify-gareeyey.
- OTP request waxaa lagu daray per-phone iyo per-IP rate limit. Qiyaasta hadda
  waa 3 request halkii phone/school iyo 20 request halkii IP/school muddo 60
  ilbiriqsi ah.
- OTP terminal rows-ka `VERIFIED` iyo `FAILED` code-kooda waa la scrub-gareeyaa.
  WhatsApp worker logs-na mar dambe kuma kaydiyaan OTP code ama message body.
- `schools`, `otp_queue`, `otp_logs`, `sync_logs` iyo rate-limit table waa
  server-only; `anon` iyo `authenticated` toos uma akhriyi karaan.

### Session security

- Supabase session-ka waxaa laga raray plain AsyncStorage waxaana lagu kaydiyaa
  Expo SecureStore iyadoo long session JSON loo qaybiyo safe chunks.
- Existing installs waxaa loo sameeyey one-time secure migration.
- App cold start iyo app resume labaduba waxay dib u xaqiijiyaan device trust.
- Supabase auth listener-ka waxaa laga saaray async callback race/deadlock risk.

### Push notifications

- `bridge-sync` ayaa ah push dispatcher-ka keliya. `notify-parents` waa
  authenticated no-op si webhook hore u yaallaa uusan duplicate push u dirin.
- Expo push requests waxaa loo qaybiyaa batches ugu badnaan 100.
- Expo ticket ID kasta waxaa lagu kaydiyaa `push_delivery_tickets`.
- Receipts waxaa la hubiyaa 15 daqiiqo kadib; 24 saac kadib ticket aan receipt
  helin wuxuu noqdaa `ReceiptTimeout`.
- `DeviceNotRegistered` wuxuu si automatic ah u nadiifiyaa push token-ka duugoobay.
- App kasta wuxuu leeyahay EAS project ID u gaar ah; hal EAS project ID lama wada
  adeegsan karo school variants kala duwan.

### Multi-school iyo integrations

- Hal Supabase project ayaa qaadi kara schools badan; row kasta waxaa lagu kala
  saaraa `school_id`, RLS/RPC-yaduna school boundary ayay xaqiijiyaan.
- Parent-ku app variant-kiisa `SCHOOL_ID` keliya ayuu ka geli karaa, messages
  iyo devices-kuna school + phone ayay ku xiran yihiin.
- CI3 tokens waxaa loo diyaariyey Supabase Vault secret references. CSV/SQL
  generators mar dambe token value ma qoraan.
- Parent sync waxaa lagu daray mass-deactivation safety stop. Haddii source-ku
  si lama filaan ah uga soo dhaco wax ka badan threshold-ka, current parents
  lama deactivate-gareeyo.
- Pilot seed generator-ku executable SQL ma abuuro haddii placeholders ama fake
  data ay ku jiraan matrix-ka.

## 3. Verification La Sameeyey

| Check | Natiijo |
|---|---|
| TypeScript `tsc --noEmit` | PASS |
| Expo Doctor | PASS, 18/18 |
| Android production export | PASS, 1,391 modules |
| Deno check: 5 Edge Functions | PASS |
| WhatsApp service syntax | PASS |
| WhatsApp production dependency audit | PASS, 0 vulnerabilities |
| Repository secret scan | PASS |
| School/matrix/device structural validators | PASS |
| Supabase migration dry-run | PASS, 3 migrations pending |
| Live Supabase Auth/REST | FAIL, HTTP 402 quota restriction |
| Production manifest/matrix/device validators | FAIL as expected: real rollout data missing |

Dry-run-ku wuxuu aqbalay migrations-kan:

1. `20260731100000_auth_device_and_otp_security.sql`
2. `20260731101000_push_delivery_hardening.sql`
3. `20260731102000_integration_vault_references.sql`

Migrations-ka lama push-gareyn. Edge Functions-ka cusubna lama deploy-gareyn,
sababtoo ah auth/device change-ku waa in atomic order lagu deploy-gareeyo kadib
marka Supabase restriction-ka la saaro.

## 4. Deployment Order-ka Saxda ah

### Gate A: External blockers

1. Supabase Dashboard ka saar `exceed_db_size_quota`: upgrade/restore billing
   ama nadiifi data-ga sharci ahaan la delete-gareyn karo.
2. Xaqiiji `/auth/v1/health` iyo safe REST probe inay bixiyaan 2xx.
3. Rotate CI3 token-kii hore Git history galay, GitHub PAT-yadii la wadaagay iyo
   VPS password-kii chat-ka lagu qoray.
4. Ku samee token kasta Supabase Vault secret name-kan:
   `school_<id>_parents_api_token` iyo
   `school_<id>_messages_api_token`.
5. Buuxi real school URLs, support phone, website, test parent phones iyo
   WhatsApp `CONNECTED` status.
6. School kasta u samee EAS project u gaar ah, geli UUID-ga manifest-ka, kuna
   dar icon, adaptive icon iyo splash assets.

### Gate B: Database iyo Functions

Marka Gate A la dhammeeyo:

```powershell
npm run preflight:production
npx supabase db push --linked
npx supabase functions deploy request-otp --project-ref fmmatzjhhyhtkpabyhih
npx supabase functions deploy verify-otp --project-ref fmmatzjhhyhtkpabyhih
npx supabase functions deploy bridge-sync --project-ref fmmatzjhhyhtkpabyhih
npx supabase functions deploy sync-parents --project-ref fmmatzjhhyhtkpabyhih
npx supabase functions deploy notify-parents --project-ref fmmatzjhhyhtkpabyhih
```

Edge Function secrets-ka cusub:

- `OTP_RATE_LIMIT_PEPPER`: random secret ugu yaraan 32 bytes.
- `EXPO_ACCESS_TOKEN`: haddii Expo enhanced push security la shiday.
- Existing `BRIDGE_SYNC_SECRET`, `SYNC_PARENTS_SECRET` iyo
  `NOTIFY_WEBHOOK_SECRET` waa in aan la wadaagin ama source-ka lagu qorin.
- `PARENT_SYNC_MIN_RATIO`: optional; default-ku waa `0.5`.

Migration-ka marka hore, Functions-ka kadib. App build lama qaybin karo inta
Functions-ka cusub iyo migrations-ku kala version yihiin.

### Gate C: Scheduler

1. Manual ahaan hal mar u invoke-garee `sync-parents`.
2. Manual ahaan hal mar u invoke-garee `bridge-sync`.
3. Hubi `sync_logs` inaanay lahayn `FAILED`.
4. Kadib oo keliya activate cron/scheduler.
5. First pilot-ka ku bilow hal school iyo laba real devices muddo 24 saac ah.

## 5. Deep Production Test Plan

### Auth iyo OTP

| Test | Natiijada la rabo |
|---|---|
| Phone aan allowlist ku jirin | Login waa la diidaa; OTP lama queue-gareeyo |
| Allowed phone | Hal OTP ayaa WhatsApp soo gaadha |
| 4 requests phone isku mid ah 60s gudahood | Request-ka afraad 429 ayuu helaa |
| OTP khaldan 5 jeer | OTP-ga waa locked/failed |
| OTP sax ah laba jeer | Markii labaad waa rejected; single-use |
| Device cusub | OTP cusub waa qasab |
| App restart isla device-ka | Session-ku wuu soo noqdaa; OTP lama weydiiyo |
| Current device revoke | App resume wuxuu keenayaa logout |
| Explicit logout | Device-ku revoked ayuu noqdaa; login dambe OTP ayuu rabaa |

### Tenant isolation

Samee ugu yaraan afar school/app variants iyo afar mobile:

1. Parent A, School 1, App 1
2. Parent B, School 2, App 2
3. Parent C, School 3, App 3
4. Parent D, School 4, App 4

School kasta kasoo dir laba message. Mid kasta waa inuu gaaro school + parent
sax ah. App kale, phone kale ama school kale ma arki karo. Tijaabi sidoo kale
isla phone oo laba school ku jira; app variant kasta wuxuu soo bandhigayaa oo
keliya fariimaha `SCHOOL_ID`-kiisa.

### Messages iyo Realtime

- Finance, announcement iyo general message types.
- Duplicate CI3 record laba jeer: Supabase recipient waa inuu hal row noqdaa.
- App foreground: message-ku realtime ayuu u soo muuqdaa.
- App background/killed: push ayaa yimaada; tap-ku message sax ah ayuu furaa.
- Internet off/on: app-ku waa inuu recover-gareeyaa oo message-ka cusub soo qaataa.
- CI3 status update: `pending/sent/seen` mapping-ka iyo upstream sync xaqiiji.

### Push receipts

- `push_delivery_tickets.status` wuxuu ka gudbaa `PENDING` una gudbaa
  `DELIVERED` ama `FAILED`.
- App uninstall kadib push: `DeviceNotRegistered` waa inuu fcm token-ka NULL
  ka dhigo.
- Database webhook-ka `notify-parents` waa inuu 202 disabled bixiyo; push
  duplicate ah ma iman karo.

### WhatsApp OTP service

- Dashboard-ku school-ka wuxuu tusaa `CONNECTED`.
- Queue row wuxuu maraa `PENDING -> PROCESSING -> SENT`.
- OTP code/message body kuma muuqdaan operational logs.
- Disconnected session: row waa retry/failed, mana luminayo queue control.
- Daily cap iyo school pause waa inay OTP cusub joojiyaan.
- VPS node wuxuu qaadan karaa oo keliya schools leh `server_node_id` u dhigma.

### Sync safety

- Normal full parent feed: upsert/deactivation sax ah.
- Feed madhan ama si weyn u yaraaday: sync waa inuu safety stop sameeyaa.
- Hal school source failure: schools kale waa inay sii shaqeeyaan.
- Token khaldan: error waa inuu galaa server log, token value-na lama log-gareeyo.

## 6. Go/No-Go Criteria

Client pilot waa **GO** keliya marka dhammaan qodobadan ay sax yihiin:

- `npm run preflight:production` wuxuu ku dhammaadaa PASS.
- Supabase Auth iyo REST waxay bixiyaan 2xx, ma aha 402.
- Saddexda pending migration live ayay ku jiraan.
- Shanta Edge Function version-kooda cusub waa ACTIVE.
- Real school matrix, device matrix iyo manifest dhammaantood PASS.
- WhatsApp sessions-ka pilot schools waa CONNECTED.
- 24-hour pilot-ka ma laha cross-school leak, duplicate message/push ama lost OTP.
- Backup iyo rollback commit/build IDs waa la diiwaangeliyey.

## 7. Residual Risk

Linked production upgrade path-ka migration dry-run wuu gudbay, laakiin clean
database disaster-recovery test wali lama samayn. Local `supabase db reset`
wuxuu u baahan yahay Docker Desktop, kaas oo machine-kan hadda ka maqan.
Production launch ka hor waa in la sameeyo schema baseline/backup iyo clean
restore rehearsal. Tani ma joojinayso linked pilot-ka marka quota la saaro,
laakiin waxay joojinaysaa in nidaamka loo aqoonsado full disaster-recovery ready.

## 8. Next Goal

Next goal-ku waa **Pilot Deployment and 4-School Isolation Test**:

1. Ka saar Supabase 402 restriction.
2. Buuxi real pilot data, Vault secrets, EAS IDs iyo assets.
3. Deploy migrations iyo Functions atomic order-ka kore.
4. Dhis afar Android variants.
5. Ku rakib afar devices oo real parent phones leh.
6. Orod test matrix-ka oo kaydi PASS/FAIL evidence.
7. Haddii 24 saac pilot-ku PASS yahay, sii first controlled clients.
