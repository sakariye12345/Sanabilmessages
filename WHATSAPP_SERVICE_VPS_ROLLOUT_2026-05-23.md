# Warbixinta WhatsApp Service VPS Rollout

Taariikh: `2026-05-23`
Mashruuc: `Sanabil Messages`

## Ujeeddada Shaqadan

Shaqadan waxay ahayd in `whatsapp-service` laga dhigo mid si dhab ah ugu diyaarsan VPS rollout:

- config gooni ah
- PM2 process config
- health/summary endpoints
- runbook cad oo deployment-ka lagu qaado

## Waxa La Beddelay

### 1. Service-ku hadda wuxuu akhriyaa `.env` gudaha `whatsapp-service`

File:

- [whatsapp-service/server.js](/C:/Users/hp/SanabilMessages/whatsapp-service/server.js)

Waxa hadda dhacaya:

- marka hore wuxuu eegaa [whatsapp-service/.env.example](/C:/Users/hp/SanabilMessages/whatsapp-service/.env.example) u dhigma `.env`-ga service-ka
- kadib ayuu fallback uga qaataa root `.env`

Tani waxay kuu sahlaysaa in VPS-ka service-ku yeesho env u gaar ah, adigoon ku qasbanaan in app env-ga oo dhan la wadaago.

### 2. PM2 config waa la diyaariyay

File:

- [whatsapp-service/ecosystem.config.js](/C:/Users/hp/SanabilMessages/whatsapp-service/ecosystem.config.js)

Waxa uu qeexayaa:

- process name
- memory restart limit
- polling config
- retry/stale/autopause env values

## 3. Health iyo ops summary endpoints

File:

- [whatsapp-service/server.js](/C:/Users/hp/SanabilMessages/whatsapp-service/server.js)

Endpoints-ka cusub:

- `GET /health`
- `GET /api/wa/summary`

`/api/wa/summary` wuxuu soo saarayaa:

- school kasta session status-kiisa
- paused state
- cooldown/cap metadata
- queue counts:
  - `PENDING`
  - `PROCESSING`
  - `SENT`
  - `FAILED`
- school kasta client-ku ma connected yahay
- school-ku QR ma sugayaa

Tani waxay ka dhigaysaa debugging iyo support mid aad uga fudud.

### 4. package scripts waa la nadiifiyay

File:

- [whatsapp-service/package.json](/C:/Users/hp/SanabilMessages/whatsapp-service/package.json)

Waxaa lagu daray:

- `npm run check`

Si deployment ka hor syntax check loo sameeyo.

## Sida Loo Rollout Gareeyo VPS-ka

### 1. Repo update

```bash
cd /path/to/SanabilMessages
git pull origin main
```

### 2. WhatsApp service env diyaari

```bash
cd whatsapp-service
cp .env.example .env
```

Kadib geli:

- `EXPO_PUBLIC_SUPABASE_URL`
- `SUPABASE_SERVICE_ROLE_KEY`
- `WA_SERVER_NODE_ID`
- policy env values haddii loo baahdo

### 3. Dependencies

```bash
npm install
npm run check
```

### 4. PM2

```bash
pm2 start ecosystem.config.js
pm2 save
pm2 status
```

Haddii process hore u jiray:

```bash
pm2 restart sanabil-whatsapp-service
```

### 5. Health checks

```bash
curl http://127.0.0.1:4000/health
curl http://127.0.0.1:4000/api/wa/summary
```

### 6. WhatsApp session checks

School kasta:

```bash
curl http://127.0.0.1:4000/api/wa/status/1
curl -X POST http://127.0.0.1:4000/api/wa/start/1
```

Haddii QR loo baahdo:

- `GET /api/wa/status/:school_id`
- QR ka scan garee account-ka school-kaas

## Verification

Waxaan sameeyay:

- `node --check whatsapp-service/server.js`

Natiijo:

- syntax-ku waa sax

## Natiijada

Hadda qaybta repo-ga waxay diyaarisay:

- policy-hardened service code
- trusted device + revoke backend
- app-side device management
- VPS deployment assets

## Next Goal

`Actual VPS rollout + live OTP proof after restart`

Tallaabada xigta ee ugu saxan waa:

1. VPS-ka service-kan cusub ha la geeyo
2. `pm2 restart` ha lagu sameeyo
3. `/api/wa/summary` ha lagu xaqiijiyo
4. hal OTP real test ha la sameeyo si loo caddeeyo in:
   - queue
   - cooldown
   - session
   - delivery

ay runtime ahaan wada shaqaynayaan
