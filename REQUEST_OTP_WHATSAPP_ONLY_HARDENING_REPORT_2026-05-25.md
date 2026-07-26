# Warbixin: Request OTP WhatsApp-Only Hardening
**Taariikh:** 2026-05-25

## Ujeeddada shaqadan
Shaqadan waxay diiradda saartay `request-otp` layer-ka, sababtoo ah meeshaas ayay ka bilaabataa dhammaan OTP login flow-gu.

Inkasta oo WhatsApp OTP automation la dhisay, `request-otp` wali wuxuu sitay behavior duug ah oo aan ku habboonayn architecture-ka cusub. Gaar ahaan:
- wali wuxuu watay `gateway` logic duug ah
- parent-ku haddii uu badhanka dhowr jeer riixo, wuxuu samayn karay rows badan oo `otp_queue` ah
- request-side cooldown adag kuma jirin
- school paused state laguma tixgelin jirin

Marka shaqadan ujeeddadeedu waxay ahayd:
**in `request-otp` laga dhigo queue-first, WhatsApp-only, cooldown-aware, oo aan duplicate OTP requests samaynin**

---

## Waxa la saxay

### 1. Gateway logic-kii duugga ahaa waa laga saaray
File: [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

Waxa la beddelay:
- function-ku mar dambe si toos ah uma wacayo `otp_gateway_url`
- mar dambe ma jiro paid gateway fallback oo gudaha request flow-ga ku jira
- `request-otp` hadda wuxuu si cad u sameeyaa:
  - auth user update
  - OTP code generation
  - queue insert

Sababta:
- architecture-ka aan hadda rabno waa `WhatsApp automation only`
- direct gateway send iyo queue-send in la isugu daro waxay dhalin kartaa state jahawareer ah
- source of truth waa inuu noqdaa `otp_queue`

---

### 2. Request-side cooldown ayaa lagu daray
File: [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

Waxa la beddelay:
- function-ku hadda wuxuu akhriyaa `school.otp_cooldown_seconds`
- wuxuu hubiyaa haddii isla parent-ka uu dhawaan leeyahay `PENDING` ama `PROCESSING` OTP
- haddii uu jiro request dhaw, function-ku ma abuuro row cusub

Natiijada:
- parent-ku markuu marar badan riixo badhanka, system-ku ma abuuro queue spam
- OTP request volume-ku wuu yaraadaa
- WhatsApp sender-ka lama saaro culays aan loo baahnayn

Tani aad bay muhiim ugu tahay anti-ban discipline-ka aad rabtay.

---

### 3. School pause state hadda request layer-ka way joojisaa
File: [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

Waxa la beddelay:
- haddii school-ku `otp_is_paused = true` yahay
- function-ku wuxuu diidayaa request-ka
- wuxuuna soo celiyaa response cad oo wata sababta pause-ka

Sababta:
- sender service-ka kaliya ma aha meesha policy-ga lagu xakameeyo
- request layer-kuna waa inuu ixtiraamaa pause state-ka

Tani waxay ka dhigaysaa system-ka mid isku xirnaan fiican leh:
- request side
- queue side
- sender side

dhammaantood hal policy bay raacayaan

---

### 4. OTP requests-kii hore ee active-ka ahaa waa la supersede-gareeyaa
File: [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

Waxa la beddelay:
- ka hor inta aan row cusub la gelin
- rows-kii hore ee isla `school_id + phone` oo `PENDING/PROCESSING` ahaa waxaa loo rogaa:
  - `FAILED`
  - `error_message = Superseded by a newer OTP request`

Sababta:
- parent-ku waa inuu yeesho hal OTP oo current ah
- queue-gu ma aha inuu qaado rows badan oo is khilaafsan
- sender-ku markuu akhriyo queue-ga, waa inuu helo hal request oo sax ah

Tani waxay nadiifinaysaa queue behavior-ka si aad u fiican.

---

### 5. Response formatting-ka function-ka waa la hagaajiyay
File: [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

Waxa la beddelay:
- response helper ayaa lagu daray
- success, pause, duplicate-request, iyo error responses hadda waa kuwo si isku mid ah loo qaabeeyey

Sababta:
- app-side behavior-ku wuu ka sii cadnaanayaa
- debugging-ku wuu fududaanayaa

---

## Optimization database-level
File: [supabase/migrations/otp_request_queue_index_2026_05_25.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/otp_request_queue_index_2026_05_25.sql)

Waxa aan ku daray:
- index cusub oo loogu talagalay hot path-ka request-side checks:
  - `school_id`
  - `phone`
  - `status`
  - `created_at DESC`

Sababta:
- cooldown check-ka cusub iyo active-request lookup-ku waa inay noqdaan kuwo degdeg ah
- gaar ahaan marka requests-ku bataan

Ogow:
- file-kan migration-ka ah repo-ga waa lagu daray
- laakiin live database-ka si gaar ah weli looma riixin session-kan, sababtoo ah repo-ga migrations-kiisu ma wada raacaan naming pattern-ka CLI-ga caadiga ah
- function-ka laftiisa se live waa la deploy-gareeyay

---

## Waxa live ahaan la sameeyay
Waxaan live u deploy-gareeyay:
- `request-otp` function version `8`

Xaqiijinta:
- [Supabase function list] waxay muujisay:
  - `request-otp`
  - `STATUS = ACTIVE`
  - `VERSION = 8`
  - `UPDATED_AT = 2026-05-25 17:46:02 UTC`

Taasi waxay ka dhigan tahay:
**WhatsApp-only request logic-ga cusub hadda live ayuu ku shaqaynayaa**

---

## Natiijada shaqadan
Kadib hardening-kan, `request-otp` wuxuu noqday:
- WhatsApp-only
- cooldown-aware
- pause-aware
- duplicate-resistant
- queue-clean

Taasi waxay si gaar ah muhiim ugu tahay:
- parents badan oo marar badan riixi kara badhanka
- sender policy discipline
- anti-ban strategy
- single current OTP behavior

---

## Sababta tani u ahayd shaqo core ah
Haddii `request-otp` daciif ahaado:
- sender-ka dambe oo dhan ayuu wasakheeyaa
- queue-gu wuxuu buuxsami karaa OTP requests badan oo is dul saaran
- OTP state-ku wuxuu lumin karaa caddeyntii ahayd “kee baa current ah”
- WhatsApp volume-ku wuxuu kori karaa si aan loo baahnayn

Marka tani ma ahayn UI ama feature dheeraad ah.
Waxay ahayd:
**core control point hardening**

---

## Gunaanad
Shaqadan waxay nadiifisay albaabka ugu horreeya ee OTP login flow-ga.

Hadda flow-gu wuxuu noqday:
1. app -> `request-otp`
2. `request-otp` -> policy check
3. `request-otp` -> clean queue insert
4. `whatsapp-service` -> send
5. app -> verify
6. trusted device -> register

Taasi waxay ka dhigaysaa system-ka mid aad uga adkaysan badan kii hore.

---

## Next Goal
Shaqada xigta ee ugu saxan hadda waa:

**live end-to-end OTP proof**

Waxaan rabnaa hal test xaqiijinaya:
1. request cusub
2. queue row cusub oo sax ah
3. WhatsApp delivery
4. app verify success
5. trusted device row update
6. push token row sync

Marka taas la helo, OTP login stack-ka core ahaan wuxuu noqonayaa mid si dhab ah loo xiray.
