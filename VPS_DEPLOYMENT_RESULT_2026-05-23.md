# Warbixinta VPS Deployment Result

Taariikh: `2026-05-23`
Mashruuc: `Sanabil Messages`
Server: `72.62.28.186`

## Ujeeddada Shaqadan

Ujeeddadu waxay ahayd in `whatsapp-service` cusub lagu geeyo VPS-ka, lagu kiciyo si ammaan ah gudaha folder gaar ah, iyada oo aan wax kale la taaban, kadibna la xaqiijiyo in service-ku runtii noolyahay.

## Waxa La Qabtay VPS-ka

Waxyaabaha si toos ah loogu sameeyay server-ka:

1. Waxaa la sameeyay folder-kan:
   - `/opt/OTPWhatsapp`
2. Waxaa lagu geeyay:
   - `server.js`
   - `package.json`
   - `ecosystem.config.js`
   - `.env`
   - `.env.example`
3. Waxaa lagu rakibay dependencies-ka service-ka
4. Waxaa lagu kiciyay `PM2`
5. Waxaa lagu sameeyay health checks
6. Waxaa la bilaabay school `1` WhatsApp session

## Runtime Xaaladda Hadda

Service-ka hadda wuxuu ku socdaa:

- process name: `sanabil-whatsapp-service`
- process manager: `PM2`
- port: `4000`
- status: `online`

Health endpoint:

- `GET /health` wuxuu soo celiyay:
  - `ok: true`
  - `node: VPS-1`

## Caqabaddii La Helay Inta Lagu Jiray Deployment-ka

Markii ugu horreysay service-ku wuu dhacay sababtan:

- VPS-ku wuxuu isticmaalayay `Node 20`
- Supabase client-ku wuxuu u baahday `WebSocket transport` si cad loogu siiyo

Fix la sameeyay:

- waxaa lagu daray package-ka `ws`
- `server.js` waxaa lagu daray `realtime.transport = WebSocket`

Kadib fix-kan:

- service-ku si sax ah ayuu u kacay
- health endpoint wuu shaqeeyay

## WhatsApp Session Xaaladdiisa

School `1` waxaa loo diray start request.

Natiijada hadda:

- `wa_session_status = WAITING_QR`
- `clientConnected = true`
- `waitingQr = true`

Tani waxay ka dhigan tahay:

- Node process-ku wuu shaqaynayaa
- WhatsApp client-ku wuu kacsan yahay
- laakiin wali waxaa ka dhiman **QR scan**

## Queue Xaaladda Hadda

Markii summary la eegay, school `1` wuxuu muujiyay:

- `PENDING = 0`
- `PROCESSING = 0`
- `SENT = 38`
- `FAILED = 5`

Macnaha:

- service-ku hadda ma hayo OTP sugaysa diris
- qaar ka mid ah requests-kii hore waxay galeen `FAILED`
- QR scan la’aanta darteed, delivery dhab ah wali ma bilaaban

## Waxa Hadda Si Rasmi Ah U Dhammaaday

Waxyaabahan hadda waa done:

- VPS folder gooni ah waa la sameeyay
- service code cusub waa la geeyay
- dependencies waa la rakibay
- PM2 process waa online
- health endpoint waa shaqaynayaa
- school `1` session start waa la sameeyay
- QR waiting state waa la xaqiijiyay

## Waxa Keliya Ee Harsan

Qodobka keliya ee dhab ahaan ka haray si `WhatsApp OTP` uu u noqdo live:

### `QR scan`

Waxa la sameynayo:

1. `GET /api/wa/status/1` ha laga qaato QR-ga
2. WhatsApp account-ka school-ka ha ku scan gareeyo
3. marka uu noqdo `CONNECTED`, OTP delivery dhab ah ayaa bilaaban karta

## Next Goal

`QR scan + one live OTP proof`

Tani waa tallaabada keliya ee xigta ee muhiimka ah, sababtoo ah:

- code-ku waa diyaar
- VPS-ku waa diyaar
- process-ku waa online
- health check waa sax
- waxa keliya ee ka maqan waa WhatsApp login handshake
