# Warbixin: OTP Expiry + Single-Use Verification
**Taariikh:** 2026-05-25

## Ujeeddada shaqadan
Shaqadan waxay xirtay qodobka ugu culus ee wali ka furnaa core-ka login/OTP:

**OTP-ga ma aha inuu u dhaqmo sida password joogto ah.**

Qorshihii hore wuxuu lahaa daciifnimo muhiim ah:
- `request-otp` wuxuu password-ka auth user-ka ka dhigi jiray isla OTP code-ka
- `verify` screen-kuna si toos ah ayuu ugu gali jiray `signInWithPassword`

Natiijada:
- OTP-gu wuxuu ahaan karay “password” ilaa request cusub la sameeyo
- expiry dhab ah ma jirin
- single-use dhab ah ma jirin

Marka shaqadan ujeeddadeedu waxay ahayd:
**in OTP-gu noqdo mid waqti leh, hal mar la isticmaalo, kadibna si toos ah u baaba’a**

---

## Waxa la sameeyay

### 1. `request-otp` mar dambe ma dhigo OTP code-ka inuu noqdo password
File: [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

Waxa la beddelay:
- markii OTP request la sameeyo, auth user password-ka looma dhigayo code-ka
- halkii, waxaa loo sameeyaa `invalidation password` random ah

Sababta:
- haddii user-ku isku dayo inuu si toos ah `signInWithPassword` ku galo adigoon verify flow marin
- OTP code-ku mar dambe ma shaqaynayo sida password

Tani waa isbeddelka ugu muhiimsan ee security-ga.

---

### 2. Waxaa la sameeyay edge function cusub: `verify-otp`
File: [supabase/functions/verify-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/verify-otp/index.ts)

Shaqada function-kan cusub:
1. wuxuu qaataa:
   - `phone`
   - `code`
2. wuxuu hubiyaa parent-ka `allowed_parents`
3. wuxuu soo qaataa latest active OTP row
4. wuxuu hubiyaa:
   - row ma jiraa
   - code-ku ma sax yahay
   - waqtigiisu ma dhicin
5. haddii uu sax yahay:
   - wuxuu sameeyaa `session_password` random ah
   - auth user password-ka wuxuu u beddelaa session password-kaas
   - `otp_queue` row-ga wuxuu ka dhigaa `VERIFIED`
   - log ayuu geliyaa
6. kadib ayuu app-ka kusoo celiyaa:
   - `phone`
   - `session_password`

Sababta:
- app-ku ma gali karo ilaa uu maro verify gate-kan
- OTP-gu hadda waa step xaqiijin, ma aha login credential joogto ah

---

### 3. Verify screen-ka app-ka waa la beddelay
File: [app/(auth)/verify.tsx](/C:/Users/hp/SanabilMessages/app/(auth)/verify.tsx)

Waxa la beddelay:
- screen-ku mar dambe si toos ah uguma galo:
  - `signInWithPassword(phone, otpCode)`
- hadda wuxuu sameeyaa:
  1. `verify-otp` invoke
  2. haddii verify guulaysto:
     - wuxuu qaataa `session_password`
  3. markaas ayuu ku galaa:
     - `signInWithPassword(phone, session_password)`

Sababta:
- login credential-ka dhabta ahi hadda wuxuu dhalanayaa **kadib** verify success
- taas ayaa OTP-ga ka dhigaysa single-use

---

### 4. OTP status lifecycle waa la ballaariyay
File: [supabase/migrations/20260525195000_otp_verify_single_use.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/20260525195000_otp_verify_single_use.sql)

Waxa migration-kan sameeyay:
- `otp_queue_status_check` waxaa lagu daray status cusub:
  - `VERIFIED`

Sababta:
- markaan OTP si guul leh loo isticmaalo, waa in row-gu yeesho state cad
- `SENT` kuma filna
- `FAILED` sax ma aha haddii verify guulaystay

Marka hadda lifecycle-ku wuxuu noqonayaa:
- `PENDING`
- `PROCESSING`
- `SENT`
- `VERIFIED`
- `FAILED`

---

### 5. Active request uniqueness waxaa loo ballaariyay `SENT`
File: [supabase/migrations/20260525195000_otp_verify_single_use.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/20260525195000_otp_verify_single_use.sql)

Waxa la beddelay:
- unique active request index-kii hore wuxuu ilaalinayay:
  - `PENDING`
  - `PROCESSING`
- hadda waxaa lagu daray:
  - `SENT`

Sababta:
- OTP la diray laakiin aan wali la verify-gareyn wali waa active OTP
- hal parent waa inuusan hal mar wada haysan laba OTP oo “live” ah xitaa haddii mid la diray

Tani waxay xoojisay single-use / one-live-OTP model-ka.

---

### 6. Old active OTP rows cleanup
Isla migration-kan gudaheeda:
- haddii ay jiraan rows badan oo active ah (`PENDING/PROCESSING/SENT`)
- kii ugu dambeeyay ayaa current ahaan u haraya
- kuwii kale waxaa loo rogaa `FAILED`

Sababta:
- ka hor xeerka cusub waa in data-ga hore la nadiifiyaa

---

## Waxa live ahaan la sameeyay
Waxaan live u deploy-gareeyay:
- `request-otp` -> `VERSION = 10`
- `verify-otp` -> `VERSION = 1`

Waxaan live u riixay migration:
- `20260525195000_otp_verify_single_use.sql`

Xaqiijinta remote:
- `supabase functions list` waxay muujisay:
  - `request-otp ACTIVE version 10`
  - `verify-otp ACTIVE version 1`
- `supabase migration list --linked` waxay muujisay:
  - `20260525195000` local
  - `20260525195000` remote

Taas macnaheedu waa:
**expiry + single-use hardening-kan cusub hadda remote production stack-ka ayuu ku jiraa**

---

## Natiijada shaqadan
Kadib hardening-kan:
- OTP code ma aha password joogto ah
- OTP-ga waqtigiisa waa la xadiday (`10 minutes`)
- OTP-gu hal mar ayuu u shaqayn karaa verify flow-ga
- kadib verify success, system-ku wuxuu sameeyaa session password random ah
- `otp_queue` row-gu wuxuu noqdaa `VERIFIED`

Tani waxay si weyn u xoojisay:
- security
- correctness
- auditability
- login consistency

---

## Sababta tani u tahay qodobka ugu muhiimsan
Haddii OTP-gu u ekaado password joogto ah:
- security-gu wuu daciifayaa
- old code ayaa mar dambe la isticmaali karaa
- “OTP verification” macnihiisii wuu luminayaa

Marka shaqadan ma aha detail yar.
Waxay ahayd:
**core authentication correctness fix**

---

## Gunaanad
Shaqadan waxay beddeshay sida login-ku u shaqeeyo gudaha:

Hore:
1. request OTP
2. OTP code = password
3. sign in directly

Hadda:
1. request OTP
2. OTP WhatsApp ku yimaada
3. `verify-otp` validates code + expiry
4. system creates one-time session password
5. app signs in with session password
6. trusted device register

Tani waa qaab aad uga adag, aad uga saxan, uguna production-ready badan kii hore.

---

## Next Goal
Shaqada xigta ee ugu saxan hadda waa:

**live end-to-end proof**

Waxaan rabnaa in la xaqiijiyo:
1. request cusub
2. WhatsApp OTP arrive
3. verify-otp success
4. sign-in success
5. trusted device row
6. second app open without OTP
7. revoke current device -> forced logout

Marka taas la helo, login/OTP/auth core stack-ka si dhab ah ayuu u xirmayaa.
