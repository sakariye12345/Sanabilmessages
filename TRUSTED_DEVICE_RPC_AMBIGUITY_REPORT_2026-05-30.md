# Trusted Device RPC Ambiguity

Taariikh: `2026-05-30`

## Ujeeddada

In la xalliyo qaladka:

`column reference "phone_number" is ambiguous`

kaas oo ka soo baxayay marka app-ku sameynayo:

- `hydrate()`
- `device trust sync`
- `session restore`

## Waxa La Helay

Qaladka kama imanayn:

- Expo
- auth screen
- OTP request

Wuxuu ka imanayay `trusted device RPC` dhinaca database-ka, gaar ahaan:

- `register_my_device(...)`

### Root Cause

Function-ka live ee `register_my_device` wuxuu leeyahay:

```sql
ON CONFLICT (phone_number, device_id)
```

isla markaana `RETURNS TABLE`-kiisu wuxuu leeyahay output variables isla magacyadaas wata:

- `phone_number`
- `device_id`

Taasi waxay sababi kartaa in Postgres u arko reference-kaas mid `ambiguous`.

## Saamaynta

Marka app-ku isku dayo:

- inuu device-ka bootstrap gareeyo
- ama trust row-ga soo cusbooneysiiyo

RPC-gu wuu qarxayaa, waxaana app-ka ka soo baxaya:

- `Hydration failed`
- `Device trust sync failed`

## Waxa La Saxay Hadda

Gudaha app-ka:

- [src/store/auth.ts](/C:/Users/hp/SanabilMessages/src/store/auth.ts)

waxaa lagu daray fallback adag:

- haddii trusted-device RPC-gu ku dhaco qaladkan gaarka ah
- session-ka lama burburinayo
- user-ka lama saarayao app-ka
- auth state-ku si ku meel gaar ah ayuu u sii shaqaynayaa

Tani waxay noo oggolaanaysaa:

- in app-ku furmo
- in hydration-ku dhammaado
- in testing-gu sii socdo

## Xaaladda Saxda ah

Waxaa jira laba heer:

### 1. Ku-meel-gaar / App-side resilience

Tani hadda waa la sameeyay.
Waxay joojisay in bug-ga server-ku uu si toos ah u jebiyo app-ka.

### 2. Permanent fix / Database-side

Tani wali waa in live DB-ga lagu saxo:

`register_my_device` waa in conflict target-kiisa loo beddelaa qaab aan ambiguous ahayn, sida:

```sql
ON CONFLICT ("phone_number", "device_id")
```

ama qaab kale oo si rasmi ah u ka saaraya magaca is qabsiga.

## Files-ka La Diyaariyay

Permanent SQL fix-ka waxaa loo diyaariyay:

- [supabase/migrations/20260530093000_fix_register_my_device_ambiguity.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/20260530093000_fix_register_my_device_ambiguity.sql)

Waxaa sidoo kale la waafajiyay file-kan:

- [supabase/migrations/trusted_device_auth.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/trusted_device_auth.sql)

## Maxaa Hadda La Filayaa

Kadib patch-kan app-side:

- hydration crash-ku waa inuu baaba'aa
- verify/login flow-gu waa inuu sii socdaa
- trusted-device persistence-ka permanent ahaan wali wuxuu ku xirnaanayaa in SQL fix-ka live la orodsiiyo

## Next Goal

1. xaqiiji in app-ku hadda ka gudbayo `hydrate`
2. xaqiiji in login/verify flow-gu marayo
3. kadib live DB-ga ku orodsiinta SQL patch-ka si trusted-device flow-gu u noqdo fully solid

## Gunaanad

Bug-ga hadda lama inkirin, balse si sax ah ayaa loo kala saaray:

- app-side crash: waa la xaliyay
- database-side root cause: wali waxaa loo diyaariyay permanent SQL fix

Tani waxay noo ilaalinaysaa in app-ku shaqeeyo hadda, iyadoo weli jihada saxda ah ee permanent fix-ka la hayo.
