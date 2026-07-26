# Phase 1: Multi-School Foundation

Taariikh: `2026-06-29`

## Ujeeddada

`Phase 1` ujeeddadiisu waa in la sameeyo saldhig adag oo lagu bilaabi karo tijaabada ballaaran ee:

- schoolo badan
- allowed parents badan
- message sources badan
- WhatsApp OTP sessions badan
- app instances kala duwan

Tani weli ma aha rollout-ka mobiles-ka.
Tani waa wejiga diyaarinta saldhigga.

## Output-ka Phase 1

Marka Phase 1 dhammaado waa in aad haysataa:

1. `school matrix` buuxa
2. `schools` rows diyaar ah
3. `allowed parents` demo data diyaarsan
4. `parents_api_url` iyo `messages_api_url` school walba loo kala qoray
5. `OTP policy` school walba loo qeexay
6. `app variant plan` school walba loo qeexay
7. `test parent numbers` school walba loo diyaariyay

## Files-ka Phase 1

Waxaan kuu diyaariyay:

1. [school_matrix_template.csv](/C:/Users/hp/SanabilMessages/school_matrix_template.csv)
2. [demo_multi_school_seed_template.sql](/C:/Users/hp/SanabilMessages/demo_multi_school_seed_template.sql)
3. [PHASE1_MULTI_SCHOOL_FOUNDATION_2026-06-29.md](</C:/Users/hp/SanabilMessages/PHASE1_MULTI_SCHOOL_FOUNDATION_2026-06-29.md>)

## Sidee Loo Adeegsanayaa

## Step 1: Buuxi School Matrix

Fur:

- [school_matrix_template.csv](/C:/Users/hp/SanabilMessages/school_matrix_template.csv)

School kasta geli:

- `school_code`
- `school_name`
- `app_variant`
- `android_package`
- `app_display_name`
- `parents_api_url`
- `messages_api_url`
- `otp_node_id`
- `otp_cooldown_seconds`
- `otp_daily_cap`
- `test parents`

### Xeerka muhiimka ah

School kasta waa inuu yeeshaa:

- hal `school_id`
- hal `app_variant`
- hal `OTP session ownership`
- ugu yaraan `3 test parents`

## Step 2: Go’aami Demo Schools-ka Koowaad

Tijaabada koowaad waxaan kugula talinayaa:

- `4 school demo`

Qaabkan:

1. School A
2. School B
3. School C
4. School D

Sababta:

- 1 school aad horay u xaqiijisay
- 4 school ayaa ku filan in cross-school leakage si dhab ah loo qabto
- 10 school hal mar gelintiisu way culus tahay ka hor intaan pattern-ku xasillin

## Step 3: Diyaari Seed SQL

Fur:

- [demo_multi_school_seed_template.sql](/C:/Users/hp/SanabilMessages/demo_multi_school_seed_template.sql)

Waxa ku jira:

- `schools` insert template
- `allowed_parents` insert template
- `students` optional linkage notes
- `verification queries`

### Sida loo buuxinayo

School kasta:

1. geli row-ga `schools`
2. geli `allowed_parents`
3. hubi phone numbers normalization
4. hubi `school_id` consistency

## Step 4: Qeex Ownership-ka Sources-ka

School kasta si cad ugu qor matrix-ka:

- `parents_api_url`
- `parents_api_token`
- `messages_api_url`
- `messages_api_token`

### Sababta

Maadaama aad hadda isticmaalayso:

- allowlist source gooni ah
- message source gooni ah

waa muhiim in school kasta uu yeesho `source contract` cad.

## Step 5: Qeex OTP Session Ownership

School kasta waa in lagu cadeeyaa:

- `server_node_id`
- `wa_session_status`
- WhatsApp account-ka uu isticmaalayo

### Hadafka

In aan la isku qasin:

- school row
- OTP sender
- WhatsApp session

## Step 6: Diyaari App Variant Plan

Waxa hadda repo-gu taageerayo waa `copied app instances`.

School kasta u qor:

- `app_variant`
- `app_display_name`
- `android_package`
- `primary_color`
- `support_phone`
- `website`

### Ogaal

Tani waxay weli noqonaysaa:

- hal backend
- apps kala duwan config ahaan

Taasi waa qaabka ugu saxan ee hadda repo-gu ku socdo.

## Phase 1 Acceptance Criteria

Phase 1 waxaa la oran karaa wuu dhammaaday haddii:

1. `4 school demo` matrix-koodu dhammaystiran yahay
2. school walba leeyahay `source URLs` sax ah
3. school walba leeyahay `test parents`
4. school walba leeyahay `OTP session ownership`
5. school walba leeyahay `app variant plan`
6. seed SQL school walba diyaar u yahay

## Waxa Aan Weli Phase 1 ku Jirin

Kuwani Phase 1 ma aha:

- APK build final
- mobile installation
- live message routing test
- cross-device test

Kuwaas waxay bilaabanayaan Phase 2 iyo Phase 3.

## Risk-yada Ugu Muhiimsan

### 1. School IDs oo is qasma

Haddii `school_id` si random ah loo isticmaalo, routing-ku wuu qasmi karaa.

### 2. Phones aan la normalize-gareyn

Haddii school qaar ku jiraan:

- `063...`

iyo kuwo kale:

- `252...`

markaas OTP iyo visibility way jabayaan.

### 3. Source drift

Haddii matrix-ku wax kale sheego, `schools` table-kuna wax kale hayo, debugging way adkaanaysaa.

### 4. Variant naming drift

Haddii `app_variant`, `package`, iyo `school_id` aan la mideyn, build-phase-ku wuu qasmi karaa.

## Talo Cad

Ka hor inta aanad 10 school gelin:

1. samee `4 school pilot matrix`
2. pilot-ka ka xaqiiji:
   - OTP
   - message routing
   - app variant correctness
3. kadib ku ballaari `10 school`

Tani waa jidka ugu ammaan badan.

## Next Goal

Kadib marka aad buuxiso matrix-ka iyo seed template-ka:

`Phase 2` wuxuu noqonayaa:

- demo data loading
- school rows creation
- allowed parents bulk insert
- source validation school walba

## Gunaanad

`Phase 1` ma aha coding badan.
Waa `structure and control`.

Haddii Phase 1 si adag loo dhiso:

- rollout-ka multi-school wuu degdegi doonaa
- bugs-ka leakage-ka ah way yaraan doonaan
- onboarding-ka school cusubna wuxuu noqon doonaa mid la celin karo oo nadiif ah.
