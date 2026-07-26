# Phase 4: Pilot Data Automation

Taariikh: `2026-06-29`

## Ujeeddada

In school matrix-ka laga dhigo source shaqaynaya oo si toos ah uga soo saara:

- validation
- pilot seed SQL
- rollout readiness

Tani waxay yareynaysaa khaladaadka gacanta lagu sameeyo marka schoolo badan la gelinayo.

## Waxa La Kordhiyay

### 1. Matrix validator

File:

- [scripts/validate-school-matrix.js](/C:/Users/hp/SanabilMessages/scripts/validate-school-matrix.js)

Run:

```powershell
npm run schools:matrix:validate
```

Waxa uu hubiyaa:

- `app_variant` inuu ku jiro manifest-ka
- `school_id` inuusan duplicate ahayn
- `android_package` inuu la jaanqaado manifest-ka
- placeholders badan inay wali ku jiraan iyo in kale

### 2. Pilot seed generator

File:

- [scripts/generate-pilot-seed.js](/C:/Users/hp/SanabilMessages/scripts/generate-pilot-seed.js)

Run:

```powershell
npm run schools:seed:generate
```

Waxa uu soo saarayaa:

- [generated/pilot_seed.generated.sql](/C:/Users/hp/SanabilMessages/generated/pilot_seed.generated.sql)

### 3. CSV parser helper

File:

- [scripts/school-matrix-utils.js](/C:/Users/hp/SanabilMessages/scripts/school-matrix-utils.js)

Tani waa utility-ga ay labada script wadaagaan.

## Sida Loo Adeegsanayo

### Step 1

Buuxi:

- [school_matrix_template.csv](/C:/Users/hp/SanabilMessages/school_matrix_template.csv)

### Step 2

Validate garee:

```powershell
npm run schools:matrix:validate
```

### Step 3

Generate garee pilot seed:

```powershell
npm run schools:seed:generate
```

### Step 4

Fur:

- [generated/pilot_seed.generated.sql](/C:/Users/hp/SanabilMessages/generated/pilot_seed.generated.sql)

Kadib mari review gaaban.

### Step 5

Marka uu sax noqdo, ku orodsi remote DB-ga.

## Ogaal Muhiim Ah

Generator-ku wuxuu:

- ka boodaa rows-ka wali placeholders ku jira
- ku daraa oo keliya schoolo buuxsamay
- sameeyaa parent names default ah sida:
  - `SCHOOL_B Parent 1`
  - `SCHOOL_B Parent 2`

Taasi waxay ku filan tahay pilot-ka.

## Xaaladda Hadda

Pilot variant foundation:

- waa diyaar

Pilot matrix automation:

- waa diyaar

Pilot seed generation:

- waa diyaar

## Next Goal

Kadib marka matrix-ka la buuxiyo:

1. orod `schools:matrix:validate`
2. orod `schools:seed:generate`
3. ku shub SQL-ga remote DB
4. billow OTP sessions school walba
5. build APKs
6. ku rakib devices

## Gunaanad

Hadda matrix-ku ma aha warqad qorshe oo keliya.
Wuxuu noqday source shaqo oo laga dhalin karo pilot data-ga Supabase.

Tani waa tallaabo muhiim ah oo tijaabada buuran ka dhigaysa mid la fulin karo, la celin karo, oo aan ku xirnayn xasuus ama qoris badan oo gacanta ah.
