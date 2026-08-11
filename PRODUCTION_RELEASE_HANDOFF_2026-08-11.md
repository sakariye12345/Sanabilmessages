# Sanabil Messages: Production Release Handoff

**Taariikh:** 11 Agoosto 2026

**Xaaladda code-ka:** Local production preflight waa PASS

**Xaaladda live release-ka:** NO-GO ilaa external blockers-ka hoose la xalliyo

## 1. Nuxurka Guud

Sanabil Messages core application-ka, multi-school tenant isolation-ka, WhatsApp OTP flow-ga, trusted devices, Supabase RPC security, Edge Functions, CI3 bridge, Realtime inbox, push delivery hardening, iyo release automation dhammaantood code ahaan waa la diyaariyey.

Wareeggan waxa lagu xiray blockers muhiim ah oo aan billing ahayn:

- Release-ku hadda wuxuu qaadan karaa school variants la doortay, sida `sanabil`, halkii schools aan wali onboard-gareysnayn ay wada joojin lahaayeen deployment-ka.
- Real test-parent phone numbers laguma qasbayo public GitHub repo. Production matrix files-ku local-only ayay noqonayaan.
- Command timeout wuxuu nadiifiyaa dhammaan child processes; Expo/Node processes dambe kama harayaan test fashilmay.
- App kasta wuxuu UI-ga ka qaadanayaa magaca iyo primary color-ka variant-kiisa.
- Supabase outage ama API failure looma muujinayo waalidka sidii inbox madhan; waxaa la siinayaa error cad iyo retry.
- EAS profiles-ka waxaa loo kala xiray `development`, `preview`, iyo `production` environments.
- WhatsApp service production dependency audit-ku waa `0 vulnerabilities`.

## 2. Verification La Dhammeeyey

Kuwaan dhammaantood way gudbeen:

- TypeScript: PASS
- Expo Doctor: `18/18`
- Secret scan: PASS
- School manifest structural validation: PASS
- School matrix structural validation: PASS
- Device matrix structural validation: PASS
- Expo/EAS build contract, dhammaan 6 variants: PASS
- Sanabil selected production build contract: PASS
- WhatsApp service syntax: PASS
- WhatsApp production dependency audit: `0 vulnerabilities`
- Deno checks ee 5 Edge Functions: PASS
- Android production export: PASS
- Android bundle: `1,391 modules`, qiyaastii `3.97 MB`
- Generator reproducibility: PASS
- Supabase required Edge secret names: `3/3`

Root Expo/Metro dependency tree-ku wali wuxuu leeyahay transitive high findings oo lagu sixi karo oo keliya breaking Expo SDK upgrade. `npm audit fix` non-breaking ah ayaa la fuliyey, critical findings ma jiraan. `npm audit fix --force` lama isticmaalin sababtoo ah wuxuu app-ka ka boodsiin lahaa Expo 54 ilaa Expo 57 oo u baahan upgrade project gaar ah iyo native regression testing.

## 3. Blockers-ka Hadda Jira

### Blocker A: Supabase Organization Restriction

Live Auth health wuxuu soo celinayaa:

```text
HTTP 402 exceed_db_size_quota
```

Inta restriction-kan jiro, lama samayn karo production login, Auth, REST, Realtime, migration deployment ama full smoke test lagu kalsoonaan karo.

**Owner action:** Supabase dashboard-ka ka xalli Organization billing/quota restriction-ka. Password ama account credentials cidna ha la wadaagin.

### Blocker B: Pending Live Deployment

Migrations-kan local ayay diyaar ku yihiin, laakiin lama xaqiijin inay live database-ka gaareen inta 402 jiro:

- `20260731100000_auth_device_and_otp_security.sql`
- `20260731101000_push_delivery_hardening.sql`
- `20260731102000_integration_vault_references.sql`
- `20260803120000_operational_data_retention.sql`

Edge Functions-ka local version-kooda sidoo kale waa in dib loo deploy-gareeyo marka service-ku soo noqdo.

### Blocker C: Real Pilot Configuration

Sanabil pilot-ka strict validator-ku hadda wuxuu sugayaa:

- Real public support phone oo lagu geliyo `config/schools.manifest.json`.
- Saddex real normalized test-parent phones oo local production matrix lagu geliyo.
- Device-1 real test phone iyo status `READY` ama `PASS`.

Schools kale waxay u baahan yihiin EAS project ID, branding assets, support phone, real API URLs, real test devices iyo WhatsApp session `CONNECTED` marka mid kasta onboarding-kiisu bilaabmo.

## 4. Production Data Oo Aan GitHub Gelin

Samee labada local files:

```powershell
Copy-Item school_matrix_template.csv school_matrix.production.csv
Copy-Item pilot_device_test_matrix.csv pilot_device_test_matrix.production.csv
```

Kadib ku beddel oo keliya school-ka la tijaabinayo real data. Files-kan `.gitignore` ayaa ilaalinaya:

- `school_matrix.production.csv`
- `pilot_device_test_matrix.production.csv`

Ha gelin CI3 tokens, Supabase service-role key, OTP codes ama parent phone lists GitHub. API credentials-ku Supabase Vault/Edge secrets ayay ku jiraan; matrix-ku wuxuu hayaa oo keliya secret names.

## 5. Gate-ka Hal School

Tusaale Sanabil oo keliya:

```powershell
npm run preflight:production -- --variants=sanabil --school-matrix=school_matrix.production.csv --device-matrix=pilot_device_test_matrix.production.csv
```

Tusaale afar schools:

```powershell
npm run preflight:production -- --variants=sanabil,schoolb,schoolc,schoold --school-matrix=school_matrix.production.csv --device-matrix=pilot_device_test_matrix.production.csv
```

Variant la doortay haddii uusan manifest, school matrix iyo device matrix dhammaantood ku jirin, gate-ku si cad ayuu u diidayaa.

## 6. EAS Cloud Build Environment

Ka hor EAS build kasta, xaqiiji in manifest-ka, Expo config-ga iyo EAS profiles-ku isku school yihiin:

```powershell
npm run schools:build-contract:validate:production -- --variants=sanabil
```

Gate-kan wuxuu diidayaa:

- Preview profile aan dhisayn internal APK.
- Production profile aan dhisayn Android App Bundle.
- `APP_VARIANT` profile khaldan.
- App name, slug, scheme, package ama `school_id` is khilaafsan.
- EAS project ID placeholder ah.
- Icon, adaptive icon, splash ama favicon maqan.
- Production submit profile maqan.

EAS project kasta ku xaqiiji labada public mobile variables ee `preview` iyo `production` environments:

- `EXPO_PUBLIC_SUPABASE_URL`
- `EXPO_PUBLIC_SUPABASE_ANON_KEY`

Hubinta Sanabil preview:

```powershell
$env:APP_VARIANT="sanabil"
npx eas-cli env:list --environment preview
```

Hubinta production:

```powershell
npx eas-cli env:list --environment production
```

Local `.env` kuma filna EAS cloud build. EAS dashboard ama authenticated EAS CLI ayaa lagu maamulaa values-ka.

## 7. Deployment Marka 402 Baxo

### Tallaabada 1: Dry Run

```powershell
npm run release:check -- --variants=sanabil --school-matrix=school_matrix.production.csv --device-matrix=pilot_device_test_matrix.production.csv
```

Waa inuu ku dhammaadaa `DRY RUN PASSED`.

### Tallaabada 2: Deploy

Git worktree-ku waa inuu clean yahay, branch-ku `main` yahay, local iyo `origin/main`-na isku mid yihiin.

```powershell
npm run release:deploy -- --confirm-project=fmmatzjhhyhtkpabyhih --variants=sanabil --school-matrix=school_matrix.production.csv --device-matrix=pilot_device_test_matrix.production.csv
```

Command-kan wuxuu u kala hormarinayaa:

1. Database migrations
2. Shanta Edge Functions
3. Automated production smoke test

Cron si automatic ah uma shidmo. Taasi waa safety gate sax ah.

### Tallaabada 3: Build

Internal APK pilot:

```powershell
npx eas-cli build --platform android --profile sanabil
```

Play Store AAB:

```powershell
npx eas-cli build --platform android --profile production-sanabil
```

## 8. Client Pilot Acceptance Test

School kasta ku celi:

1. APK-ga ku rakib physical Android device.
2. Xaqiiji app name, icon, package iyo primary color.
3. Geli parent ku jira allowed list-ka school-kaas.
4. Codso WhatsApp OTP oo xaqiiji session-ka saxda ah inuu diro.
5. Geli OTP oo xaqiiji trusted device enrollment.
6. Dib u fur app-ka oo xaqiiji inaan OTP cusub la waydiin.
7. CI3 source-ka school-kaas ka dir absence, finance iyo notice messages.
8. Xaqiiji Realtime inbox iyo thread detail.
9. Xaqiiji push notification background/killed state.
10. Xaqiiji in school kale app-kiisa ama parent-kiisa uusan fariinta arkin.
11. Profile-ka ka revoke-garee device, kadib xaqiiji in session-ku baxo.
12. VPS OTP process restart-garee oo xaqiiji session/queue recovery.

## 9. Go/No-Go Go'aanka

**Code iyo local architecture:** GO

**Live Supabase deployment:** NO-GO ilaa 402 laga qaado

**Sanabil client pilot:** NO-GO ilaa real local matrices, EAS environments, deployment iyo acceptance test ay gudbaan

**Multi-school architecture:** GO marka school kasta uu leeyahay unique `school_id`, app variant, EAS project, CI3 endpoints, WhatsApp session iyo local test device

Hal Supabase project/plan ayaa schools badan wada qaadi kara sababtoo ah data access-ku `school_id` iyo authenticated parent identity ayuu ku xiran yahay. Kala-soocidda app instances-ku waxay ka dhacdaa build-time variant config; kala-soocidda backend data-kuna waxay ka dhacdaa database RPC/RLS tenant contract-ka.
