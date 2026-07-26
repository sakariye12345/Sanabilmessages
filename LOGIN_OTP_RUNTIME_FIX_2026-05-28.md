# Login iyo OTP Runtime Fix

Taariikh: `2026-05-28`

## Ujeeddada

In la saxo labada blocker ee joojinayay login-ka mobile app-ka:

- `Invalid API key`
- `request-otp` oo ku dhacayay qaladka ah `Password cannot be longer than 72 characters`

## Mushkiladdii La Helay

Markii app-ka laga codsaday OTP, waxaa jiray laba calaamadood:

1. Expo logs-ku waxay muujiyeen `AuthApiError: Invalid API key`
2. `request-otp` Edge Function-ku wuxuu ku soo noqonayay error gudaha ah oo oranayay:
   `Password cannot be longer than 72 characters`

## Root Cause

### 1. Supabase anon key

`.env`-ga local-ka waxaa ku jiray `anon key` khaldan. Taasi waxay keentay in app-ku uusan si sax ah ula hadli karin `Supabase Auth`.

### 2. OTP secret password length

`request-otp` iyo `verify-otp` waxay sameynayeen password-yo aad u dhaadheer:

- `otp-${uuid}-${uuid}`
- `session-${uuid}-${uuid}`

Kuwaas waxay dhaafayeen xadka password-ka uu oggol yahay `Supabase Auth`, sidaas darteed request-ku wuu fashilmay ka hor inta uusan queue-ga OTP gelin.

## Waxa La Saxay

### Local env

Waxaa la saxay:

- `EXPO_PUBLIC_SUPABASE_ANON_KEY`

### Edge Functions

Waxaa la gaabiyay password generators-ka:

- `supabase/functions/request-otp/index.ts`
- `supabase/functions/verify-otp/index.ts`

Qaabka cusub:

- `otp-${uuid}`
- `session-${uuid}`

Tani waxay ka dhigaysaa password-ka mid ka hooseeya xadka `72 characters`.

## Verification

Waxaa la sameeyay smoke test live ah oo toos loogu wacay:

- `request-otp`

Natiijada:

```json
{
  "success": true,
  "status": "queued",
  "queued": true,
  "provider": "whatsapp",
  "cooldown_seconds": 30,
  "message": "OTP queued for WhatsApp delivery."
}
```

Tani waxay caddeysay in:

- `Invalid API key` blocker-kii local-ka la saxay
- `72-character password` blocker-kii backend-ka la saxay
- `request-otp` hadda queue-ga si sax ah ayuu u gelayaa

## Waxa Hadda Laga Rabo App-ka

Si Expo uu u qaato `.env`-ga cusub iyo route cache-ka la nadiifiyo, waa in la sameeyaa:

```powershell
npx expo start -c
```

Kadib:

1. QR-ga cusub scan garee
2. phone number geli
3. OTP codso
4. WhatsApp-ka ka hubi code-ka
5. code-ka geli verify screen-ka

## Xaaladda Hadda

Dhanka `login + OTP request`, blocker-kii ugu weynaa waa la xiray.

Waxa xiga ee muhiimka ah:

1. live OTP verify test
2. trusted device confirm
3. app reopen test adigoon OTP mar labaad la waydiin

## Gunaanad

Dhibaatadu ma ahayn hal bug oo keliya. Waxay ahayd isku darka:

- `local env mismatch`
- `backend password length bug`

Labadii waa la saxay. Hadda nidaamku wuxuu marayaa heerkii lagu samayn lahaa `live login proof`.
