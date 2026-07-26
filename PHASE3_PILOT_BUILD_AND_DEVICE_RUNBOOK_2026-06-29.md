# Phase 3: Pilot Build and Device Runbook

Taariikh: `2026-06-29`

## Ujeeddada

In tijaabada buuran laga dhigo mid la fulin karo:

- school variants badan oo la build-gareeyo
- APKs lagu rakibo mobiles kala duwan
- school walba lagu tijaabiyo OTP + login + message routing

## Waxa La Saxay Ka Hor Phase 3

### 1. Thread screen-ka waxaa laga saaray hardcoded school filter

File:

- [app/thread/[type].tsx](</C:/Users/hp/SanabilMessages/app/thread/[type].tsx>)

Hore:

- screen-ku wuxuu ku filter-gareynayay `SchoolConfig.SCHOOL_ID`

Taasi waxay keeni kartay:

- in message sax ah la qariyo haddii build config iyo backend school mapping kala duwanaadaan

Hadda:

- thread-ku wuxuu ku tiirsan yahay backend RPC scoping
- parent-ka authenticated ah ayaa go’aaminaya waxa la arko

Tani waa muhiim pilot-ka, sababtoo ah waxay yaraynaysaa false negatives.

### 2. Generic build profiles ayaa lagu daray

File:

- [eas.json](/C:/Users/hp/SanabilMessages/eas.json)

Waxaa hadda jira:

- `school-apk`
- `school-internal`

Tani waxay kuu oggolaanaysaa:

- in school kasta lagu build-gareeyo `APP_VARIANT` env
- adigoon `eas.json` school kasta profile gaar ah ugu darin

### 3. Build helper script ayaa lagu daray

File:

- [scripts/school-build-helper.js](/C:/Users/hp/SanabilMessages/scripts/school-build-helper.js)

Run:

```powershell
npm run schools:build-help -- sanabil
```

Waxay kuu sheegeysaa:

- schoolId
- package
- build command-ka saxda ah

### 4. Device matrix template ayaa lagu daray

File:

- [pilot_device_test_matrix.csv](/C:/Users/hp/SanabilMessages/pilot_device_test_matrix.csv)

Tani waxay noqonaysaa source-ka koowaad ee runta ee:

- mobile kasta
- app variant-ka saaran
- test parent-ka la gelinayo
- school-ka expected-ka ah

## Build Process-ka Saxda ah

## Step 1: Validate variants

```powershell
npm run schools:validate
```

## Step 2: List variants

```powershell
npm run schools:list
```

## Step 3: Get helper command

```powershell
npm run schools:build-help -- sanabil
```

Tusaale:

```powershell
APP_VARIANT=sanabil eas build --platform android --profile school-apk
```

## Step 4: Build pilot APKs

Pilot-ka koowaad:

1. `sanabil`
2. `schoolb`
3. `schoolc`
4. `schoold`

Ogow:

`schoolb`, `schoolc`, `schoold` waa in marka hore lagu daraa:

- [config/schools.manifest.json](/C:/Users/hp/SanabilMessages/config/schools.manifest.json)

si ay ula jaanqaadaan:

- [school_matrix_template.csv](/C:/Users/hp/SanabilMessages/school_matrix_template.csv)

## Device Installation Runbook

School/device kasta:

1. APK ku rakib mobile-ka
2. fur app-ka
3. xaqiiji app name iyo icon
4. geli `allowed parent` number-ka school-kaas
5. xaqiiji OTP WhatsApp ku yimaado
6. verify geli
7. xaqiiji inbox uu furmo
8. xaqiiji school-ka saxda ah

## Message Routing Test Runbook

School kasta:

1. kasoo dir source-kiisa demo/CI3
2. xaqiiji `messages`
3. xaqiiji `message_recipients`
4. xaqiiji device-ka saxda ah
5. xaqiiji devices kale inaanay helin

## Cross-School Isolation Runbook

Pilot-ka ka dib:

1. School B parent ku geli app School C
2. School C parent ku geli app School D
3. isku day OTP duplicate
4. isku day parent aan allowed ahayn

Expected:

- no leakage
- no wrong inbox
- no wrong OTP routing

## Next Goal

Kadib Phase 3:

1. ku dar `schoolb`, `schoolc`, `schoold` manifest-ka
2. buuxi matrix-ka
3. build 4 pilot APKs
4. bilaab Phase 4 device rollout

## Gunaanad

Phase 3 wuxuu xiray qaybtii ugu muhiimsanayd ee rollout-ka:

- build profiles generic ah
- helper commands
- device matrix
- client-side school filter cleanup

Hadda tijaabada buuran waxay ka guureysaa `planning` una guureysaa `actual pilot execution`.
