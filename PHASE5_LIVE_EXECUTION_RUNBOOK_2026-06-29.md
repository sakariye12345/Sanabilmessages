# Phase 5: Live Execution Runbook

Taariikh: `2026-06-29`

## Ujeeddada

In tijaabada buuran laga dhigo mid si toos ah loo fulin karo:

- Supabase data loaded
- OTP sessions activated
- APKs built
- devices tested
- school isolation verified

## Waxa La Kordhiyay

### 1. Device matrix validator

File:

- [scripts/validate-device-matrix.js](/C:/Users/hp/SanabilMessages/scripts/validate-device-matrix.js)

Run:

```powershell
npm run schools:devices:validate
```

Waxa uu hubiyaa:

- device row kasta inuu ku xiran yahay `app_variant` sax ah
- `expected_school_id` inuu la jaanqaado school matrix
- `expected_app_name` inuu la jaanqaado manifest
- `test_parent_phone` inuu la jaanqaado school matrix test parents

### 2. Pilot execution runbook generator

File:

- [scripts/generate-pilot-runbook.js](/C:/Users/hp/SanabilMessages/scripts/generate-pilot-runbook.js)

Run:

```powershell
npm run schools:runbook:generate
```

Waxa uu soo saarayaa:

- [generated/pilot_execution_runbook.generated.md](/C:/Users/hp/SanabilMessages/generated/pilot_execution_runbook.generated.md)

Runbook-kan wuxuu kuu kala saaraya:

- device walba
- app variant-kiisa
- test parent-ka
- source endpoints
- build command
- install checklist
- message routing checklist

## Full Pilot Order

Order-ka saxda ah ee pilot-ka hadda waa:

1. `npm run schools:validate`
2. `npm run schools:matrix:validate`
3. `npm run schools:devices:validate`
4. `npm run schools:seed:generate`
5. review [generated/pilot_seed.generated.sql](/C:/Users/hp/SanabilMessages/generated/pilot_seed.generated.sql)
6. `npm run schools:runbook:generate`
7. review [generated/pilot_execution_runbook.generated.md](/C:/Users/hp/SanabilMessages/generated/pilot_execution_runbook.generated.md)
8. load pilot seed into Supabase
9. activate OTP sessions school walba
10. build APKs
11. install on devices
12. perform routing/isolation tests

## Acceptance Criteria

Pilot-ka waxaa la oran karaa wuu dhammaaday haddii:

1. school walba uu login sameeyo
2. OTP school walba ka yimaado session sax ah
3. app walba ku furmo magaca saxda ah
4. message source school walba gaadho device sax ah
5. school kale aysan helin

## Gunaanad

Hadda tijaabada buuran waxay leedahay:

- school matrix
- build matrix
- device matrix
- data seed generator
- execution runbook generator

Tani waa saldhigga ugu dhow ee lagu bilaabi karo `actual pilot execution`.
