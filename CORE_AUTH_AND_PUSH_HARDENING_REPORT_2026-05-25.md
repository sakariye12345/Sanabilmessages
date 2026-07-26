# Warbixin: Core Auth + Push Hardening
**Taariikh:** 2026-05-25

## Ujeeddada shaqadan
Shaqadan waxay diiradda saartay hal meel oo core ah oo wali daciif ahayd: isku xirka u dhexeeya `OTP login`, `trusted device`, `session restore`, iyo `push notifications`.

Waxaan hore u dhisnay:
- `allowed_parents` gate
- `OTP verification`
- `trusted devices`
- `WhatsApp OTP automation`

Laakiin wali waxaa jiray hal daciifnimo oo muhiim ah:
- `push token` sync-kii app-ka wuxuu wali isticmaalayay hab duug ah oo si toos ah ugu qori jiray `user_devices`
- habkaas cusub ee `trusted device` schema-ga lama jaanqaadi karin si nadiif ah
- taasina waxay halis u ahayd in `user_devices` uu yeesho state aan isku xirnayn, gaar ahaan marka session dib loo soo celiyo ama app-ka la furo mar kale

Marka shaqadan ujeeddadeedu waxay ahayd in:
**hal source oo keliya uu maamulo device trust + push token registration + session heartbeat**

---

## Waxa la saxay

### 1. Push token sync-kii duugga ahaa waa la nadiifiyay
File: [src/services/notifications.ts](/C:/Users/hp/SanabilMessages/src/services/notifications.ts)

Waxa la beddelay:
- `notifications.ts` hadda mas’uul kama aha inuu si toos ah ugu qoro `user_devices`
- waxaa laga saaray direct `.from('user_devices').upsert(...)` flow-gii hore
- file-kan hadda shaqadiisu waa keliya:
  - inuu soo saaro push token
  - inuu xushmeeyo permission state-ka device-ka

Sababta:
- `user_devices` hadda waa trusted-device registry, ma aha table guud oo token keliya lagu shubo
- haddii push sync iyo trusted device ay laba jid kala maraan, state-ku wuu kala jabayaa

---

### 2. Push token registration-ka waxaa loo wareejiyay trusted-device flow
File: [src/services/deviceTrust.ts](/C:/Users/hp/SanabilMessages/src/services/deviceTrust.ts)

Waxa la beddelay:
- `runDeviceRegistration(...)` hadda wuxuu qaataa options ku saabsan push permission
- `registerCurrentDeviceAfterLogin(...)` wuxuu si rasmi ah u sameeyaa:
  - trusted device registration
  - push token registration
  - `mark_login = true`
- waxaa lagu daray function cusub:
  - `syncCurrentDevicePushToken(...)`

Function-kan cusub wuxuu muhiim u yahay:
- session-ka marka dib loo soo celiyo
- app-ka marka la furo mar kale
- push token-ka marka la doonayo in si aamusan loo sync-gareeyo

Sababta:
- hadda device trust iyo push token labaduba waxay maraan `register_my_device` RPC
- taas macnaheedu waa hal contract, hal logic, hal source of truth

---

### 3. Session restore-ka waxaa lagu xidhay silent push sync
File: [src/store/auth.ts](/C:/Users/hp/SanabilMessages/src/store/auth.ts)

Waxa la beddelay:
- `hydrate()`
- `revalidateTrust()`
- `onAuthStateChange(...)`

Dhammaan meelahan hadda waxay sameeyaan:
1. session check
2. trusted device validation
3. haddii qalabku sax yahay, `syncCurrentDevicePushToken(..., { requestPermission: false })`

Sababta:
- app-ku markuu dib u furmo user-ka lama waydiinayo permission mar kasta
- laakiin haddii permission horay loo siiyay, token-ku si aamusan ayuu isu cusboonaysiin karaa
- tani waxay xoojinaysaa:
  - session continuity
  - push readiness
  - device last-seen freshness

Tani waxay si fiican ula jaanqaadaysaa hadafkaaga:
**waalidku hal mar ayuu is caddeeyaa, kadib app-ku si deggan ayuu u sii shaqeeyaa**

---

### 4. OTP verify flow-ga waxaa laga dhigay mid ka adag kii hore
File: [app/(auth)/verify.tsx](/C:/Users/hp/SanabilMessages/app/(auth)/verify.tsx)

Waxa la beddelay:
- trusted device registration-ka hadda lama tuuro si background ah oo keliya
- waxaa la sugayaa attempt-kiisa marka login-ku guulaysto

Sababta:
- first login waa moment-ka ugu muhiimsan ee device-ka la trust-gareynayo
- haddii aanan halkaas si rasmi ah u qaban, qalabku wuxuu gali karaa state uu session leeyahay laakiin registration-kiisu kala go’an yahay

Hadda flow-gu waa sidan:
1. user OTP ayuu geliyaa
2. Supabase auth session ayaa abuurma
3. current device waxaa si rasmi ah loogu diiwaangeliyaa trusted device
4. push token ayaa lagu lifaaqaa haddii la heli karo
5. user wuxuu galayaa inbox-ka

---

## Natiijada shaqadan
Kadib hardening-kan, system-ku wuxuu noqday mid aad u mideysan:

- `OTP login` waa hal mar
- `trusted device` waa source of truth
- `push token` isla flow-gaas ayuu la socdaa
- `session restore` si aamusan ayuu device-ka u cusboonaysiiyaa
- lama hayo direct write duug ah oo schema-ga cusub jebin kara

Tani waxay si gaar ah muhiim ugu tahay:
- waalidiinta aan tech badan aqoon
- login aan la celcelin
- notification readiness
- device revocation oo sax ah

---

## Maxaa muhiim u ahaa in tan hadda la sameeyo
Haddii shaqadan aan la qaban:
- push tokens waxay sii mari lahaayeen logic duug ah
- `user_devices` wuxuu yeelan lahaa rows ama updates aan la jaanqaadin trusted-device contract-ka
- push readiness iyo trusted-session state way kala fogaan lahaayeen
- dhibaatooyinkaasi waxay si gaar ah u muuqan lahaayeen marka user-ku:
  - app-ka dib u furo
  - session restore sameeyo
  - device cusub galo
  - revoke/logout sameeyo

Marka tani waxay ahayd hardening core ah, ma ahayn feature cosmetic ah.

---

## Waxa live ahaan la filayo kadib shaqadan
Marka user-ku hal mar OTP ku galo:
- device-ka trusted buu noqdaa
- haddii push permission la siiyo, token-ku isla markiiba wuu diiwaangashan yahay

Marka app-ka dib loo furo:
- session-ka waa la soo ceshanayaa
- trusted device waa la hubinayaa
- push token haddii hore loo oggolaaday si aamusan ayuu isu sync-gareynayaa

Marka current device la revoke-gareeyo:
- access-ku wuu dhacayaa
- session-ku ma sii ahaanayo mid aamusan u shaqaynaya

---

## Verification
Waxa aan xaqiijiyay:
- [whatsapp-service/server.js](/C:/Users/hp/SanabilMessages/whatsapp-service/server.js) wali `node --check` wuu gudbay
- files-ka aan shaqadan ku taabtay kama soo bixin type errors cusub markii aan sameeyay targeted type-check filter

Ogow:
- `npx tsc --noEmit` guud ahaan repo-ga wali waxaa ku jiri kara errors duug ah oo aan shaqadan la xiriirin
- laakiin hardening-kan cusub ma keenin cilad cusub oo ka timid files-kan la taabtay

---

## Gunaanad
Shaqadan waxay xirtay meel aad muhiim u ahayd:
**auth, trusted device, iyo push token hadda waa hal nidaam oo isku xiran**

Taasi waxay ka dhigan tahay:
- app-ku wuxuu sii dhowaanayaa production readiness dhab ah
- user experience-ku wuu fududaanayaa
- backend contract-ku wuu sii adkaanayaa
- multi-device management iyo future push notifications waxay yeelanayaan saldhig sax ah

---

## Next Goal
Shaqada xigta ee ugu saxan hadda waa:

**live end-to-end OTP proof + push/device proof**

Taas macnaheedu waa in hal test dhammaystiran la xaqiijiyo:
1. app -> `request-otp`
2. WhatsApp OTP -> user receives code
3. verify -> trusted device registered
4. `user_devices` -> current device row updated correctly
5. message ingest -> app visible
6. push notification readiness -> token present on trusted device row

Halkaas kadib, waxaan si kalsooni leh u oran karnaa:
**core auth + delivery + device trust stack-ku wuu adkaystay**
