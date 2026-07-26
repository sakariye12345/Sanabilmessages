# Multi-School Production Test Plan

Taariikh: `2026-06-29`

## Ujeeddada

In la sameeyo tijaabo ballaaran oo production-style ah si loo xaqiijiyo in:

- hal `Supabase` project
- schoolo badan
- app instances kala duwan
- mobiles kala duwan
- WhatsApp OTP sessions kala duwan
- CI3/demo message sources kala duwan

ay si sax ah u wada shaqaynayaan iyada oo:

- parent walba arko oo keliya fariimaha school-kiisa
- OTP walba ku xirmo school-ka saxda ah
- app kasta uu qaato config-giisa saxda ah
- Supabase uu noqdo `central hub`

---

## Xaaladda Hadda

Waxyaabaha hadda la xaqiijiyay inay shaqaynayaan:

1. `Allowed parent login` wuu shaqaynayaa
2. `WhatsApp OTP request + verify` way shaqaynayaan
3. `Trusted device` flow wuu shaqaynayaa
4. `CI3/demo messages -> Supabase -> app` flow-gu wuu shaqaynayaa
5. `school_id` backend ahaan waa jiraa
6. `parents_api_url` iyo `messages_api_url` school walba way kala go’an karaan
7. `whatsapp-service` school walba session gooni ah ayuu qaadi karaa

Marka core foundation-ku wuu nool yahay.

---

## Qaabka Architecture-ka Hadda

Nidaamka hadda jira wuxuu ku shaqayn karaa laba hab:

### 1. Shared backend + copied app instances

Tani waa qaabka aad hadda u dhowdahay:

- hal `Supabase project`
- school walba `school_id`
- app walba waa isla codebase-ka
- laakiin build/config ayaa kala duwan

Tani waa waxa hadda repo-gu si dabiici ah u taageerayo.

### 2. Hal shared app oo schoolo badan wada qaata

Tani wali si buuxda looma nadiifin app-side.

Sababta:

- app-ku meelo qaar wali wuxuu ku tiirsan yahay `SchoolConfig.SCHOOL_ID`
- taas oo ka timid build-time config

Marka:

- haddii aad rabto copied apps per school: system-ku hadda aad buu ugu dhow yahay
- haddii aad rabto hal app shared ah: app-side cleanup weli waa in la sameeyaa

---

## Go’aanka Saxda ah ee Tijaabadan

Maadaama aad rabto:

- apps kala magac duwan
- config kala duwan
- mobiles kala duwan

qaabka ugu saxan ee hadda lagu tijaabin karo waa:

**`copied app instances + one shared Supabase backend`**

Taasi waxay la jaanqaadaysaa repo-ga hadda jira.

---

## Sidee 10 School ugu Wada Shaqayn Karaan Hal Supabase Plan?

Haa, way wada shaqayn karaan haddii aan si sax ah u kala xadidno:

### Supabase wuxuu qabanayaa:

- `schools` registry
- `allowed_parents`
- `messages`
- `message_recipients`
- `otp_queue`
- `otp_logs`
- `sync_logs`
- `user_devices`

### School isolation-ku wuxuu ku salaysan yahay:

- `school_id`
- phone number-ka parent-ka
- OTP request school context
- message recipient targeting

### Tani waxay shaqaynaysaa haddii:

1. school walba uu leeyahay `school_id` sax ah
2. `allowed_parents` school walba loo kala geliyo si sax ah
3. `messages` school walba loo kala xiro
4. OTP queue walba `school_id` sax ah loo geliyo
5. WhatsApp session walba school gaar ah loo xiro

### Xadka dhabta ah ma aha “10 school”

Xadka dhabta ahi waa:

- total OTP volume
- total message volume
- realtime subscriptions
- Edge Function calls
- message duplication quality
- data hygiene

Marka 10 school farsamo ahaan dhib ma aha, haddii volume-ku dhexdhexaad yahay.

---

## Tijaabada Ballaaran: Scope-ka Aan Sameynayno

Waxaan tijaabin doonaa 5 lakab:

### Layer 1: Supabase multi-school data

Waxaan gelin doonaa:

- schoolo demo badan
- allowed parents badan
- message sources badan

### Layer 2: App instances

Waxaan build-gareyn doonaa:

- app instance 1
- app instance 2
- app instance 3
- app instance 4

mid kasta oo leh:

- app name gaar ah
- package name gaar ah
- school config gaar ah

### Layer 3: Mobile devices

Waxaan ku rakibi doonaa:

- 4 mobile

mid kasta:

- app kale
- parent kale
- school kale

### Layer 4: WhatsApp OTP sessions

Waxaan diyaarin doonaa:

- school walba OTP session gaar ah
- ama ugu yaraan school kasta oo test-ka ku jira session u gaar ah

### Layer 5: Message routing validation

Waxaan xaqiijin doonaa:

- school A message-ku uusan gaarin parent school B
- school B message-ku uusan gaarin parent school C
- parent walba helo oo keliya fariintiisa

---

## Qorshaha Tijaabada: Marxaladaha

## Phase 1: Demo Data Expansion

### Hadaf

In Supabase lagu diyaariyo schoolo demo badan iyo users badan.

### Waxa la sameynayo

1. Ku dar `schools` rows cusub
2. School walba u samee:
   - `parents_api_url`
   - `messages_api_url`
   - `otp policy`
3. Geli `allowed_parents` badan school walba
4. Hubi in number walba uu si sax ah u normalize-garan yahay
5. Hubi in school walba messages-ku ka imanayaan source sax ah

### Output

Ugu yaraan:

- `4 school demo`
- school walba `5-10 allowed parents`
- school walba source testable ah

---

## Phase 2: School Config Matrix

### Hadaf

In la sameeyo school matrix rasmi ah.

### School matrix-ka waa inuu yeeshaa

School kasta:

- `school_id`
- `school_name`
- `app_variant`
- `android_package`
- `app_display_name`
- `parents_api_url`
- `messages_api_url`
- `otp_session_status`
- `test_parent_numbers`
- `test_message_source`

### Sababta

Tani waxay ka dhigaysaa onboarding-ka iyo testing-ga mid nidaamsan.

---

## Phase 3: App Instance Build Prep

### Hadaf

In app kasta loo diyaariyo build profile sax ah.

### Xaaladda hadda

Repo-gu wuxuu hadda leeyahay profiles:

- `sanabil`
- `alsunna`
- `alxikma`

kuwaas waxay ku jiraan:

- [eas.json](/C:/Users/hp/SanabilMessages/eas.json)
- [app.config.js](/C:/Users/hp/SanabilMessages/app.config.js)

### Waxa ka hadhay

Haddii aad rabto 4 ama 10 school:

- school kasta profile buuxa waa in lagu daraa
- ama `app.config.js` laga dhigo env-driven generic config

### Talo

Tijaabadan weyn ka hor:

samee ugu yaraan `4 production-like variants` si rasmi ah.

---

## Phase 4: OTP Session Expansion

### Hadaf

In school kasta oo test-ka ku jira uu yeesho WhatsApp OTP session sax ah.

### Xaaladda hadda

`whatsapp-service` wuxuu durba school-aware u yahay:

- client walba `school_${schoolId}`
- session walba gooni
- summary/status walba gooni

Taasi waxay ka dhigan tahay:

**hal Node service ayaa qaadi karta sessions badan**

Marka khasab ma aha:

- hal VPS bot per school

haddii volume-ku yaryahay.

### Talo practical ah

Phase-kan:

- 4 school = 4 WhatsApp sessions
- hal VPS service ayaa ku filan test-ka

### Haddii volume weyn timaado mustaqbalka

markaas:

- sessions-ka waxaa loo qaybin karaa nodes kala duwan
- `server_node_id` ayaa taas taageeri kara

---

## Phase 5: Device Test Matrix

### Hadaf

In si dhab ah loo tijaabiyo app instances badan oo mobiles kala duwan saaran.

### Test matrix-ka ugu yar

#### Device 1
- App: School A
- Parent: School A allowed parent
- Expected: School A messages only

#### Device 2
- App: School B
- Parent: School B allowed parent
- Expected: School B messages only

#### Device 3
- App: School C
- Parent: School C allowed parent
- Expected: School C messages only

#### Device 4
- App: School D
- Parent: School D allowed parent
- Expected: School D messages only

### Waxyaabaha la hubinayo device kasta

1. app installs successfully
2. app icon/name/package waa sax
3. OTP arrives via WhatsApp
4. verify succeeds
5. inbox opens
6. thread opens
7. message detail opens
8. app reopen does not ask OTP again

---

## Phase 6: Message Routing Test

### Hadaf

In la xaqiijiyo in fariin kasta gaadho parent-ka saxda ah oo school-ka saxda ah ku jira.

### Tijaabada

School kasta:

1. kasoo dir message source-ka school-kaas
2. xaqiiji `messages` table
3. xaqiiji `message_recipients`
4. xaqiiji mobile-ka saxda ah
5. xaqiiji mobiles kale inaanay helin

### Acceptance criteria

- School A message -> only School A allowed parent
- School B message -> only School B allowed parent
- no leakage
- no duplicate routing

---

## Phase 7: Cross-School Isolation Test

### Hadaf

In la jebiyo system-ka si loo arko haddii leakage jiro.

### Tijaabooyin

1. isku day School A number gudaha School B app
2. isku day School B OTP gudaha School A flow
3. isku day message source khaldan
4. isku day parent aan allowed ahayn
5. isku day duplicate OTP request

### Waxa la filayo

- login rejected haddii parent-ku school-kaas ka mid ahayn
- OTP context-ku sax ahaado
- messages aan ka boodin school boundary

---

## Waxa Hadda Ka Hadhay Shaqo ahaan

Kuwa ugu muhiimsan waa kuwan:

### 1. School variants badan oo rasmi ah

Hadda profiles-ku waa yar yihiin.

Waxa la qabanayo:

- ku dar 4+ school profiles
- ama generic config template samee

### 2. Test data bulk loading

Waxa la diyaarinayo:

- demo schools
- demo allowed parents
- demo message source coverage

### 3. School onboarding sheet

School kasta waa in lagu hayaa row rasmi ah oo leh:

- school_id
- app variant
- package
- OTP session
- CI3/demo source
- parent test numbers

### 4. Thread screen cleanup haddii hal shared app la rabo mustaqbalka

File-ka:

- [app/thread/[type].tsx](</C:/Users/hp/SanabilMessages/app/thread/[type].tsx>)

wuxuu wali leeyahay filtering ku xiran `SchoolConfig.SCHOOL_ID`.

Haddii copied apps la isticmaalayo:

- tani blocker degdeg ah ma aha

Haddii hal app shared ah la rabo:

- waa in laga saaraa

### 5. Full test runbook

Waa in la sameeyaa checklist la raaco marka school kasta la tijaabinayo.

---

## Risk-yada Ugu Weyn

### 1. Config drift

School row database-ku wax kale ha sheego, app variant-kuna wax kale.

### 2. Phone normalization drift

School qaar `063...`
kuwo kale `252...`

Tani waxay jebin kartaa login iyo routing.

### 3. Wrong app / wrong school pairing

Haddii parent School B uu ku galo app School A, waa in behavior-ku si cad loo go’aamiyaa.

### 4. OTP session sprawl

Haddii sessions badan la furo adigoon naming iyo ownership control lahayn, maintenance way adkaan doontaa.

---

## Talo Architecture ah oo Cad

Haddii aad hadda rabto rollout degdeg ah:

### Qaabka ugu fiican

- hal Supabase project
- copied app instances per school
- school walba `school_id`
- WhatsApp OTP session walba school gaar ah

### Sababta

Tani waxay la jaanqaadaysaa code-ka hadda jira, waxayna kuu oggolaanaysaa:

- rollout degdeg ah
- tijaabo ballaaran
- changes yar oo code ah

### Ha samayn hadda

- ha u boodin hal shared app oo 10 school wada qaata

Sababta:

app-side cleanup weli wuu ka harsan yahay.

---

## Tijaabada Buuran: Qaabka Fulinta

### Step 1

Diyaari `4 school demo`

### Step 2

School walba geli:

- allowed parents
- source config
- OTP session

### Step 3

Build-garee `4 APK`

### Step 4

Ku rakib `4 mobile`

### Step 5

School walba kasoo dir:

- OTP test
- message test

### Step 6

Xaqiiji:

- parent sax ah
- school sax ah
- no leakage
- no duplicate wrong routing

---

## Acceptance Criteria

Tijaabadan waxaa la oran karaa way guulaysatay haddii:

1. 4 school si gooni ah u galaan
2. 4 app variants si sax ah u shaqeeyaan
3. 4 phones si sax ah u helaan OTP
4. school walba parent-kiisu helo message sax ah
5. school kale uusan helin
6. app reopen-ku aanu mar kale OTP dalban
7. duplicate OTP aysan dhicin
8. no cross-school leakage

---

## Gunaanad

Mashruucu maanta wuxuu marayaa meel wanaagsan:

- single-school proof: waa done
- multi-school backend foundation: waa done
- multi-instance app model: waa la taageerayaa
- multi-session WhatsApp OTP: foundation way jirtaa

Waxa hadda xiga ma aha “build feature cusub”.
Waxa xiga waa:

- school matrix diyaarinta
- demo data expansion
- variant build expansion
- device test matrix
- isolation validation

Tani waa tijaabada saxda ah ee naga saari doonta “single-school success” una gudbin doonta “multi-school production readiness”.
