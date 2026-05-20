# Warbixinta WhatsApp OTP Automation

Taariikh: `2026-05-19`  
Mashruuc: `Sanabil Messages`

## Ujeeddada Shaqadan

Ujeeddadu waxay ahayd in `OTP login` laga saaro manual mode-ka oo aan lagu tiirsanaan:

- SMS gateway lacag leh
- manual table lookup
- `otp_sender.py` Selenium-kii hal-school ahaa

Xalka aan doorannay waa:

## Xalka La Doortay

### `whatsapp-service/server.js` ha noqdo engine-ka rasmiga ah ee OTP delivery

Sababta:

- wuxuu si dabiici ah u taageeri karaa `multi-school`
- school walba wuxuu yeelan karaa WhatsApp session u gaar ah
- `request-otp` hore ayuu queue u dhisayay, marka architecture-ka core-ka lama burburin
- wuxuu ka fiican yahay Selenium old script-ka dhinaca stability iyo scale

Marka flow-ga cusub wuxuu noqonayaa:

1. parent app-ka ayuu ka codsadaa OTP
2. `request-otp` wuxuu:
   - xaqiijiyaa phone-ka
   - abuuraa OTP
   - diyaariyaa Supabase Auth user
   - ku ridaa `otp_queue`
3. `whatsapp-service` wuxuu eegaa `otp_queue`
4. service-ku wuxuu OTP-ga ku diraa WhatsApp iyadoo lagu salaynayo `school_id`
5. queue status wuxuu isu beddelaa `SENT`
6. `otp_logs` waxaa lagu qoriyaa taariikhda dirista

## Maxaa Laga Tagay

Waxaan si ula kac ah uga leexanay:

- `otp_sender.py`
- Selenium-based single-school workflow

Sababta:

- hal WhatsApp session ayuu si adag ugu xirnaa
- `SCHOOL_NAME` hardcoded ayuu ahaa
- multi-school production uma fiicnayn
- browser UI changes ayaa sahlan inay jebiyaan

Kani hadda wuxuu ahaan karaa oo keliya:

- fallback legacy script
- debugging helper

Laakiin ma aha jidka rasmiga ah ee mashruuca.

## Waxa La Beddelay

### 1. Queue schema hardening

Waxaa la diyaariyay:

- [supabase/migrations/whatsapp_otp_hardening.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/whatsapp_otp_hardening.sql)
- [WHATSAPP_OTP_HARDENING_2026-05-19.sql](</C:/Users/hp/SanabilMessages/WHATSAPP_OTP_HARDENING_2026-05-19.sql>)

Waxyaabaha lagu daray:

- `otp_queue.status` hadda wuxuu taageeraa:
  - `PENDING`
  - `PROCESSING`
  - `SENT`
  - `FAILED`
- `attempt_count`
- `error_message`
- `processing_started_at`
- `sent_at`
- `provider`
- `updated_at`

Waxaa kale oo la adkeeyay:

- `schools.wa_session_status`
- `schools.server_node_id`
- `otp_logs.school_id`
- `otp_logs.provider`
- `otp_logs.error_message`
- `otp_logs.sent_at`

Macnaha:

queue-gu hadda waa operational queue dhab ah, ma aha table fudud oo tijaabo keliya ah.

### 2. `request-otp` waa la waafajiyay

File:

- [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

Waxa la beddelay:

- queue insert-ku hadda wuxuu qorayaa `provider='whatsapp'`
- `updated_at` si sax ah ayuu u qorayaa
- haddii school-ku isticmaalo gateway kale mustaqbalka, queue row-ga si cad ayaa loogu calaamadin karaa
- haddii direct gateway success dhacdo, `sent_at` iyo `provider='gateway'` waa la qorayaa

### 3. `whatsapp-service/server.js` waa la beddelay oo la adkeeyay

File:

- [whatsapp-service/server.js](/C:/Users/hp/SanabilMessages/whatsapp-service/server.js)

Waxyaabaha cusub:

- `service role` key waa shardi rasmi ah
- `school_id` kasta wuxuu leeyahay `LocalAuth` session u gaar ah
- QR generation + status API waa la nadiifiyay
- queue rows waa la `claim`-gareeyaa ka hor inta aan la dirin
- school-specific WhatsApp client ayaa OTP diraya
- success/failure waxaa lagu qorayaa `otp_logs`
- `wa_session_status` waxaa lagu update-gareeyaa:
  - `WAITING_QR`
  - `CONNECTED`
  - `DISCONNECTED`
- `server_node_id` waxaa loo adeegsaday future scaling
- health endpoint waa jiraa
- `start` iyo `stop` endpoints waa jiraan

## Architecture-ka Cusub

### Qaybaha muhiimka ah

#### 1. `request-otp`

Shaqadiisu:

- parent-ka xaqiiji
- school-ka hel
- code samee
- auth user update/create
- queue row geli

#### 2. `otp_queue`

Kani waa safka sugitaanka delivery-ga.

Rows-ku waxay mari karaan:

- `PENDING`
- `PROCESSING`
- `SENT`
- `FAILED`

#### 3. `whatsapp-service`

Kani waa service-ka joogtada ah ee:

- school sessions maamula
- queue-da qaada
- OTP ku dira WhatsApp
- status iyo logs update-gareeya

#### 4. `schools`

Kani wuxuu noqday config-ka maamulka:

- school identity
- WhatsApp session status
- server node assignment

## Sidee Multi-School u Shaqaynayaa

Nidaamku hadda wuxuu ku shaqayn karaa sidan:

- school `1` -> WhatsApp session u gaar ah
- school `2` -> WhatsApp session u gaar ah
- school `3` -> WhatsApp session u gaar ah

Marka `request-otp` row geliyo:

- row-gu wuxuu watayaa `school_id`
- `whatsapp-service` wuxuu eegaa `school_id`
- wuxuu isticmaalaa client-ka school-kaas
- farriinta OTP-ga waxaa loo diraa session-ka school-kaas

Tani waa multi-school qaab sax ah.

## Maxaa Live Loo Fuliyay

Waxyaabaha live-ka lagu fuliyay:

- `request-otp` waa la redeploy-gareeyay
- [WHATSAPP_OTP_HARDENING_2026-05-19.sql](</C:/Users/hp/SanabilMessages/WHATSAPP_OTP_HARDENING_2026-05-19.sql>) waxaa lagu orodsiiyay live database-ka

## Waxa Aan Weli Live Loo Kicin

Waxa aanan wali samayn:

- `whatsapp-service` process-ka laftiisa lama kicin
- QR session lama scan-gareyn school kasta
- VPS runtime setup lama dhamaystirin

Sababta:

service-kan waa process gooni ah oo Node ah, mana aha Supabase function.

## Sida Loo Kiciyo

### Requirements

Waxaad u baahan tahay:

1. VPS ama machine joogto ah
2. Node.js
3. `SUPABASE_SERVICE_ROLE_KEY`
4. internet joogto ah
5. school walba WhatsApp account u gaar ah

### Command

Gudaha:

[whatsapp-service/package.json](/C:/Users/hp/SanabilMessages/whatsapp-service/package.json)

orod:

```bash
cd whatsapp-service
npm start
```

### Session setup

School kasta:

1. `POST /api/wa/start/:school_id`
2. `GET /api/wa/status/:school_id`
3. haddii `WAITING_QR` yimaado, QR-ga scan garee
4. marka `CONNECTED` noqdo, school-kaas OTP automation-kiisu wuu noolaanayaa

## Talo Operational ah

Habka ugu saxan ee rollout-ku waa:

### Phase 1

Sanabil kaliya ku shid:

- school_id = `1`
- hal WhatsApp session
- test OTP flow end-to-end

### Phase 2

Marka Sanabil stable noqdo:

- school labaad ku dar
- QR gaar ah u scan garee
- queue + logs + status la soco

### Phase 3

Kadib:

- dashboard yar oo maamulka u gaar ah
- school status monitor
- resend / fail queue tools

## Next Goal

Goal-ka xiga hadda waa:

### `Sanabil WhatsApp OTP end-to-end proof`

Taas macnaheedu waa:

1. `whatsapp-service` runtime la kiciyo
2. school `1` WhatsApp session la connect-gareeyo
3. OTP request laga sameeyo mobile app
4. `otp_queue` row-ga laga arko `PENDING -> PROCESSING -> SENT`
5. OTP-ga WhatsApp lagu helo
6. code-ka lagu galo app-ka
7. login flow-ga oo dhan la xaqiijiyo

## Gunaanad

Waxaan hadda ka soo saarnay OTP architecture-ka heerkii:

- manual queue lookup
- single-school Selenium workaround

una beddelnay foundation ah:

- queue-based
- WhatsApp automated
- multi-school aware
- school-specific sessions
- operationally scalable

Marka si cad:

`OTP automation via WhatsApp` foundation-keedu hadda waa diyaar.  
Shaqada xigta ma aha design kale.  
Shaqada xigta waa in service-kan la kiciyo oo Sanabil lagu sameeyo full live proof.
