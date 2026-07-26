# Sanabil Messages: Production Finalization Report

**Taariikh:** 26 July 2026
**Ujeeddo:** In la diiwaangeliyo hardening-kii ugu dambeeyey, waxa live-ka lagu
deploy-gareeyey, iyo waxyaabaha hadda kaliya owner/onboarding action u baahan.

## 1. Xaaladda ugu dambaysa

Code preflight-ka waa `PASS`:

- Secret scan: PASS
- TypeScript: PASS
- Expo Doctor: 18/18 PASS
- School manifest structural validation: PASS
- School matrix structural validation: PASS
- Device matrix structural validation: PASS
- WhatsApp service syntax: PASS
- WhatsApp production dependency audit: 0 findings
- Edge Functions Deno checks: 5/5 PASS
- Android production export: PASS
- Supabase migrations local/remote: ALIGNED
- Edge Functions live: 5 ACTIVE

Full production preflight-ku si sax ah ayuu u xannibayaa launch-ka ilaa afarta
external category la dhammeeyo:

1. Real school branding/support information.
2. Real school API URLs, phones, iyo connected WhatsApp sessions.
3. Real pilot devices oo status-koodu yahay `READY` ama `PASS`.
4. Supabase `402 exceed_db_size_quota` restriction.

## 2. Hardening-ka la dhammeeyey

### Secret protection

- CI3 token dhab ah ayaa laga saaray 9 current source/SQL files.
- Python tools-ku hadda waxay akhriyaan `CI3_API_TOKEN` environment variable.
- SQL templates-ku waxay isticmaalaan placeholders.
- `npm run secrets:scan` ayaa diidaya:
  - GitHub PAT
  - Supabase secret key
  - JWT-like credential
  - Hard-coded CI3/API/service-role token
- GitHub Actions code quality workflow ayaa secret scan-ka si automatic ah u
  fulinaya.

Token-kii hore wali Git history ayuu ku jiraa. Sidaas darteed CI3 provider-ka
waa in token cusub laga sameeyo, kii horena la revoke-gareeyo.

### Migration hygiene

- 20 un-timestamped historical SQL files waxaa loo raray
  `supabase/legacy-migrations`.
- Legacy files-ku production migration chain kama mid aha.
- `supabase db push --linked --dry-run` hadda warnings legacy ah ma bixiyo.
- Local iyo remote migration history waxay leeyihiin 10 timestamped migrations
  oo aligned ah.

Migration-ka cusub ee live lagu deploy-gareeyey:

```text
20260726100000_whatsapp_node_safety.sql
```

Wuxuu canonical contract ka dhigay:

- School WhatsApp session status
- VPS `server_node_id`
- OTP cooldown/daily cap/pause state
- OTP failure counters
- OTP queue processing metadata
- Constraints iyo queue/node indexes

### Production preflight

Commands-ka cusub:

```powershell
npm run preflight:code
npm run preflight:production
npm run secrets:scan
```

`preflight:code` waxaa loogu talagalay code/CI validation.
`preflight:production` wuxuu intaas ku daraa real data validators, Supabase
migration dry-run, live Functions, iyo REST health.

Production validators-ku hadda waxay diidayaan:

- `.example` URLs
- `xxxx` ama fake phones
- Placeholder school names/support details
- Missing icons/splash assets
- WhatsApp sessions aan `CONNECTED` ahayn
- Pilot devices aan `READY` ama `PASS` ahayn

### WhatsApp dashboard security

Dashboard-ka iyo operator APIs-ka waxaa lagu daray:

- HTTP Basic operator authentication
- Password minimum 16 characters
- Constant-time credential comparison
- Per-IP rate limiting
- CSP, clickjacking, MIME, referrer, iyo cache security headers
- `school_id` input validation
- Node-scoped school visibility
- Atomic school-to-node claim
- Exact `server_node_id` queue ownership
- Cross-node start/stop/pause/resume protection
- Graceful `SIGTERM`/`SIGINT` shutdown
- Public `/health` response oo xog kooban keliya bixiya
- Default bind `127.0.0.1`

Runtime contract test:

| Test | Natiijo |
|---|---:|
| Public `/health` | HTTP 200 |
| Dashboard auth la'aan | HTTP 401 |
| Dashboard correct auth | HTTP 200 |
| Invalid school ID | HTTP 400 |
| Security headers | PASS |

### WhatsApp dependencies

- Subproject `package-lock.json` waa la sameeyey.
- Production Node requirement: `>=20`.
- Production install: `npm ci --omit=optional`.
- `whatsapp-web.js` optional archive packages looma baahna LocalAuth flow-ga.
- Production audit: 0 vulnerabilities.

## 3. GitHub CI

Workflow:

```text
.github/workflows/code-quality.yml
```

Push ama pull request kasta wuxuu fulinayaa:

1. Root deterministic `npm ci`.
2. WhatsApp `npm ci --omit=optional`.
3. Full code preflight.
4. Critical dependency audit gate.

## 4. VPS deployment safety

SSH attempt-ka waxaa joojiyey security warning:

```text
REMOTE HOST IDENTIFICATION HAS CHANGED
ED25519 fingerprint:
SHA256:2SzApniJ6S5LiYtqGjwskkHZjbpNuPxq9RDhxhrFAlY
```

Host key checking lama bypass-gareyn. Kahor VPS deployment:

1. Fingerprint-kan ka xaqiiji provider console-ka ama VPS terminal-ka.
2. Haddii server-ka si ula kac ah dib loo dhisay, markaas known host record-ka
   sax.
3. Ha isticmaalin `StrictHostKeyChecking=no`.
4. VPS `.env` ku dar:

```dotenv
HOST=127.0.0.1
WA_DASHBOARD_USERNAME=operator
WA_DASHBOARD_PASSWORD=A_REAL_RANDOM_PASSWORD_OF_16_OR_MORE_CHARACTERS
WA_DASHBOARD_RATE_LIMIT=120
WA_DASHBOARD_RATE_WINDOW_MS=60000
TRUST_PROXY_HOPS=1
```

5. Nginx/Caddy HTTPS reverse proxy ku hor mari `127.0.0.1:4000`.
6. Kadib isticmaal:

```bash
npm ci --omit=optional
npm run check
npm run audit:production
pm2 reload sanabil-whatsapp-service --update-env
```

## 5. Waxa wali external action ah

### Supabase

Live REST wali wuxuu soo celinayaa HTTP `402`. Secure bridge iyo parent-sync
cron jobs waa paused. Owner-ku waa inuu:

1. Upgrade-gareeyo organization-ka Pro, ama
2. Haddii uu Pro yahay, disable-gareeyo Spend Cap, ama
3. Sugo billing cycle reset.

Cron jobs lama shidayo ilaa REST probe uu HTTP `200` noqdo.

### Real school onboarding

School kasta waxaa wali looga baahan yahay:

- Real name iyo branding
- Real support phone/website
- Real parents API
- Real messages API
- New CI3 token stored in Vault/secrets
- Real test parents
- Connected WhatsApp session
- Assigned Android test device

### VPS

- Xaqiiji SSH host fingerprint.
- Geli operator credentials.
- Deploy dashboard security build.
- Ku hor mari HTTPS reverse proxy.

## 6. Go'aanka

Code, database migrations, security guards, CI, iyo local release checks waa
dhammeystiran yihiin. System-ku real production traffic ma gelayo ilaa:

```text
Supabase REST = 200
Production preflight = PASS
VPS host fingerprint = VERIFIED
Real school/device matrices = READY
```

Marka afartaas la helo, shaqada hadhay waa operational rollout iyo 24-48
saacadood multi-school soak test, ma aha architecture rewrite.
