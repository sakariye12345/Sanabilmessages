# Warbixinta Production Hardening

Taariikh: `2026-05-18`  
Mashruuc: `Sanabil Messages`  
Supabase Project: `fmmatzjhhyhtkpabyhih`

## Ujeeddada Shaqadan

Shaqadan ujeeddadeedu ma ahayn in feature cusub la dhiso. Ujeeddadu waxay ahayd in la adkeeyo qaybaha ugu muhiimsan ee production-ka saameeya, si system-ku u yeesho hal contract oo sax ah oo ay wadaagaan:

- mobile app-ka
- Supabase Edge Functions
- live database
- CI3 integration

Waxaan diiradda saarnay 4 meelood:

1. `phone number normalization`
2. `OTP/login flow`
3. `secure data access`
4. `live schema + RPC cleanup`

## Sawirka Guud

Ka hor shaqadan, system-ku wuxuu lahaa laba dhibaato oo waaweyn:

- qaybo kala duwan ayaa phone number-ka siyaabo kala duwan u fahmayay
- app-ka wali wuxuu lahaa meelo si toos ah uga akhrinayay tables public ah, halkii uu ka mari lahaa secure RPCs

Tani waxay keeni kartay:

- parent OTP request oo shaqeeya, laakiin verify/sign-in uu ku fashilmo format khaldan
- allowlist data oo mararka qaar `063...`, mararka qaar `252...` noqota
- app screens qaarkood oo ku xirnaada public table access halkii ay ku xirnaan lahaayeen auth-aware RPCs

Shaqadan waxay system-ka ka saartay drift-kaas.

## Waxa La Qabtay

### 1. Phone normalization waa la mideeyay

Waxaa la sameeyay hal rule oo rasmi ah oo system-ku oo dhan raaco:

- `0634458114` -> `252634458114`
- `634458114` -> `252634458114`
- `252634458114` -> sidiisii ayuu u sii joogayaa
- marka Supabase Auth loo dirayo, wuxuu noqdaa `+252634458114`

Helper-kan waxaa lagu daray:

- [src/utils/phone.ts](/C:/Users/hp/SanabilMessages/src/utils/phone.ts)
- [supabase/functions/_shared/phone.ts](/C:/Users/hp/SanabilMessages/supabase/functions/_shared/phone.ts)

Macnaha tani waa in app-ka, OTP flow-ga, parent sync, iyo bridge-sync dhammaantood hadda hal logic wadaagaan.

### 2. Auth iyo OTP flow waa la adkeeyay

Files-ka muhiimka ah:

- [app/(auth)/phone.tsx](/C:/Users/hp/SanabilMessages/app/(auth)/phone.tsx)
- [app/(auth)/verify.tsx](/C:/Users/hp/SanabilMessages/app/(auth)/verify.tsx)
- [src/services/notifications.ts](/C:/Users/hp/SanabilMessages/src/services/notifications.ts)
- [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

Waxa la saxay:

- app-ku hadda wuxuu `request-otp` u diraa phone hore loo nadiifiyay
- verify screen-ku ma isticmaalo raw input-kii user-ka; wuxuu isticmaalaa normalized E.164 phone
- push/device token sync-ku wuxuu hadda ku kaydiyaa database-ka phone normalized ah
- `request-otp` hadda wuxuu diidayaa parent aan `is_active = true` ahayn

Natiijadu waa in login flow-gu hadda yeeshay consistency dhab ah.

### 3. Parent sync iyo bridge sync waa la waafajiyay

Files-ka muhiimka ah:

- [supabase/functions/sync-parents/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/sync-parents/index.ts)
- [supabase/functions/bridge-sync/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/bridge-sync/index.ts)

Waxa la saxay:

- `sync-parents` hadda wuxuu normalize-gareeyaa phone numbers ka hor `upsert`
- `bridge-sync` hadda wuxuu isticmaalaa shared phone helper halkii uu ka ahaan lahaa logic googo’an
- haddii CI3 message uu wato phone khaldan ama madhan, si cad ayaa error loo qabsanayaa

Tani waxay ka hortagaysaa in future syncs ay dib u soo celiyaan data format khaldan.

### 4. Secure RPC layer waa la nadiifiyay

Waxaa la diyaariyay migration + SQL runbook:

- [supabase/migrations/production_message_hardening.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/production_message_hardening.sql)
- [PRODUCTION_MESSAGE_HARDENING_2026-05-18.sql](</C:/Users/hp/SanabilMessages/PRODUCTION_MESSAGE_HARDENING_2026-05-18.sql>)

Waxyaabaha live database-ka lagu sameeyay:

- `allowed_parents.phone_number` waa la normalize-gareeyay
- `message_recipients.phone_number` waa la normalize-gareeyay
- `user_devices.phone_number` waa la normalize-gareeyay
- `otp_queue.phone` waa la normalize-gareeyay
- RPC-yadii hore ee drift-ku ku jiray waa la beddelay
- waxaa lagu daray `get_message_detail()`
- waxaa lagu daray `get_my_profile()`
- execute access-ka RPC-yada waxaa lagu koobay `authenticated` iyo `service_role`

Tani waxay ahayd tallaabo muhiim ah, sababtoo ah schema-ga hadda jira wuxuu ku dhisan yahay `phone_number`, halka code/document qaar hore wali ugu xirnaayeen magacyo duug ah.

### 5. App screens-ka nugul waa la adkeeyay

Files-ka la hagaajiyay:

- [app/(tabs)/inbox.tsx](/C:/Users/hp/SanabilMessages/app/(tabs)/inbox.tsx)
- [app/profile.tsx](/C:/Users/hp/SanabilMessages/app/profile.tsx)
- [app/message/[id].tsx](</C:/Users/hp/SanabilMessages/app/message/[id].tsx>)

Waxa is beddelay:

- inbox profile info hadda wuxuu maraa `get_my_profile()`
- profile screen-kuna sidoo kale `get_my_profile()` ayuu maraa
- message detail hadda wuxuu maraa `get_message_detail()`
- debug/direct reads qaar waa laga saaray client-ka

Macnaha tani waa in app-ku uu ka sii baxay dependency-ga uu ku lahaa direct reads public ah.

### 6. Test tooling waa la waafajiyay schema-ga hadda jira

File:

- [create_test_message.py](/C:/Users/hp/SanabilMessages/create_test_message.py)

Waxa la saxay:

- script-kan hadda wuxuu isticmaalaa `phone_number`
- wuxuu la jaanqaaday schema-ga hadda jira

Tani waxay ka dhigaysaa debugging-ka iyo manual testing-ka kuwo sax ah marka la sameynayo end-to-end test.

## Waxa Live Ahaan Loo Fuliyay

Shaqadan gudaheeda, waxyaabaha hoos ku qoran live ayaa loo fuliyay:

- `bridge-sync` waa la redeploy-gareeyay
- `sync-parents` waa la redeploy-gareeyay
- `request-otp` waa la redeploy-gareeyay
- [PRODUCTION_MESSAGE_HARDENING_2026-05-18.sql](</C:/Users/hp/SanabilMessages/PRODUCTION_MESSAGE_HARDENING_2026-05-18.sql>) waxaa lagu orodsiiyay live database-ka

## Waxa La Xaqiijiyay

### 1. Functions-ka muhiimka ah way shaqaynayaan

Manual invoke kadib:

- `sync-parents` -> `200`
- `bridge-sync` -> `200`

Tani waxay caddeysay in hardening-kan cusub uusan jabin core integration-ka.

### 2. Sync logs-ku waa healthy

Log-yadii ugu dambeeyay waxay muujiyeen `SUCCESS` isku xigta.

Macnaha:

- 404-kii bridge-sync hore ugu jiray wali waa baxsan yahay
- hardening-kan cusub ma keenin regression cusub oo sync-ka jabiya

### 3. Live allowlist normalization waa guulaysatay

Ka hor shaqadan, waxaa jiray row live ah oo sidan ahaa:

- `0634458114`

Kadib hardening-ka:

- `252634458114`

Tani waa caddeyn toos ah oo muujinaysa in live data laftiisii la saxay, ee aan code keliya la beddelin.

### 4. OTP queue-ga sidoo kale waa la waafajiyay

Rows-ka dhaw ee `otp_queue` waxay muujiyeen numbers normalized ah sida:

- `252634370911`
- `252634370573`

### 5. Inactive parent guard waa shaqeeyay

Waxaa si ammaan ah loo tijaabiyay inactive allowlist parent:

- input: `0636666666`
- result: HTTP `400`
- response: parent-kaas lama oggolaan login/OTP

Macnaha:

- system-ku hadda ma siinayo OTP qof inactive ah
- auth/user creation aan loo baahnayn waa la yareeyay

## Waxa Hadda Si Rasmi Ah U Saxan

Qodobadan hadda waxaa loo tixgelin karaa in la saxay:

1. `phone number contract drift`
2. `OTP request` iyo `verify/sign-in` inconsistency
3. client-side dependency-ga qaarkii ee public direct reads
4. live allowlist row khaldan oo normalization ah
5. secure detail/profile access path-ka app-ka
6. parent sync, OTP, iyo bridge logic oo hadda hal normalization rule wadaaga

## Waxa Wali Furan

Inkasta oo hardening-ku guulaystay, waxaa wali jira shaqo muhiim ah oo harsan:

1. `message_recipients` wali waa `0`
2. `user_devices` wali waa `0` ilaa parent dhab ah uu login sameeyo
3. real CI3 endpoint `/messages/contacts` waqtigan wuxuu soo celinayaa `[]`
4. OTP delivery layer wali ma dhamaystirna maadaama gateway/WA session dhab ah uusan wali active ahayn

Qodobka ugu muhiimsan ee hadda furan waa kan koowaad:

System-ku hadda waa healthy, laakiin wax cusub lama soo gelinayo sababtoo ah source payload-ka real CI3 queue-ga ayaa waqtigan madhan.

## Natiijada Guud

Shaqadan nuxurkeedu wuxuu ahaa “system stabilization”.

Waxaan si gaar ah u xasilinay:

- config-ka core-ka
- auth contract-ka
- phone normalization-ka
- secure access pattern-ka
- live schema/RPC consistency

Marka runta hadda jirta waa tan:

Mashruucu ma joogo meel feature cusub looga boodo si indho la’aan ah. Laakiin hadda wuxuu jooga meel aasaaskiisii saxnaa dib loogu soo celiyay, lagana saaray drift-kii ugu halista badnaa ee production-ka si toos ah u saameynayay.

## Next Goal

`Next goal`-ka saxda ah hadda waa:

### Soo nooleynta real message pipeline end-to-end

Taasi waxay ka dhigan tahay:

1. in la helo ama la abuuro ugu yaraan hal `pending message` oo ku jira real CI3 backend
2. in `bridge-sync` markaas la trigger-gareeyo
3. in la xaqiijiyo in `message_recipients` uu ka bato `0`
4. in active parent login sameeyo
5. in la hubiyo flow-ga oo dhan:
   - OTP
   - sign-in
   - inbox
   - thread
   - message detail
   - device token registration

## Shaqada Xigta Ee Saxda Ah

Haddii aan hadda sii wadno, job-ka xiga wuxuu noqonayaa:

### `CI3 -> Supabase -> App end-to-end proof`

Waxaan sameyn doonaa:

1. baaritaan ku saabsan real CI3 queue-ga si loo ogaado sababta `/messages/contacts` uu u madhan yahay
2. haddii loo baahdo, hal test message oo controlled ah oo CI3 dhinaciisa laga dhaliyo
3. manual trigger ama cron wait si `bridge-sync` u qaado message-ka
4. xaqiijin live ah oo muujinaysa:
   - `messages` row
   - `message_recipients` row
   - app inbox visibility
   - thread visibility
   - detail visibility

Marka kooban:

Hardening-ka production-ka waa la qabtay.  
Goal-ka xiga hadda ma aha cleanup kale.  
Goal-ka xiga waa `prove the pipeline with real data`.
