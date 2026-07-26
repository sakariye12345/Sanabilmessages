# Warbixin: Auth Guard + Hydration Hardening
**Taariikh:** 2026-05-25

## Ujeeddada shaqadan
Shaqadan waxay diiradda saartay xasiloonida gudaha ee login state-ka app-ka.

Core OTP, trusted device, iyo request policy way adkaadeen, laakiin wali waxaa jiray laba halisood oo muhiim ah:
- `hydrate()` meel ka badan hal mar ayuu ka socon karay
- `tabs` iyo `auth` routes si adag uguma xirnayn session state-ka

Taasi waxay keeni kartay:
- `onAuthStateChange` listeners badan
- redirects aan degganayn
- screen user-ku ku dhex jiro iyadoo session-ku maqan yahay
- login state race conditions

Marka shaqadan ujeeddadeedu waxay ahayd:
**in auth state, session hydration, iyo route guards laga dhigo hal nidaam oo deggan**

---

## Waxa la sameeyay

### 1. Hydration double-run waa la joojiyay
File: [src/store/auth.ts](/C:/Users/hp/SanabilMessages/src/store/auth.ts)

Waxa la beddelay:
- waxaa lagu daray:
  - `hydratePromise`
  - `authListenerAttached`
- `hydrate()` hadda hal mar oo keliya ayuu si dhab ah u shaqaynayaa xilliga uu socdo
- haddii hydrate mar labaad la waco inta kii hore socdo, wuxuu sugaa promise-kii hore halkii uu mar labaad wax uga bilaabi lahaa

Sababta:
- hydrate double-run wuxuu keeni karaa state jahawareer ah
- gaar ahaan marka root layout iyo screens kale ay isku mar mount-gareeyaan

---

### 2. Auth listener-ka waxaa laga dhigay “attach once”
File: [src/store/auth.ts](/C:/Users/hp/SanabilMessages/src/store/auth.ts)

Waxa la beddelay:
- `onAuthStateChange` hadda hal mar oo keliya ayaa la attach-gareeyaa
- ma jiro listener cusub oo mar kasta hydrate la sameeyo kusoo kordhaya

Sababta:
- listeners badan waxay sababi karaan:
  - duplicate sync
  - duplicate redirects
  - duplicate device-trust checks
  - state flicker

Tani waa hardening muhiim ah oo gudaha auth engine-ka ah.

---

### 3. Session trust sync waxaa loo sameeyay helper dhexe
File: [src/store/auth.ts](/C:/Users/hp/SanabilMessages/src/store/auth.ts)

Waxa la sameeyay:
- helper dhexe `syncSessionTrust(...)`

Function-kan wuxuu mideeyay:
- session validation
- phone normalization
- trusted device check
- forced sign-out haddii trust jabto
- silent push token sync

Sababta:
- logic-kan hore meelo badan ayuu ku kala qornaa
- hadda hal meel ayuu ka jiraa
- taasi waxay yareynaysaa inconsistency

---

### 4. `index.tsx` mar dambe ma kiciyo hydrate
File: [app/index.tsx](/C:/Users/hp/SanabilMessages/app/index.tsx)

Waxa la beddelay:
- `index.tsx` hadda hydrate mar dambe si gooni ah uma waco
- wuxuu kaliya eegaa `hydrated` iyo `session`

Sababta:
- root layout hore ayuu hydrate u sameynayay
- index-ku markuu mar kale sameeyo, wuxuu ahaa double-init source

Tani waxay ka dhigaysaa app startup-ka mid ka nadiifsan kii hore.

---

### 5. Tabs routes hadda waxay leeyihiin auth guard rasmi ah
File: [app/(tabs)/_layout.tsx](/C:/Users/hp/SanabilMessages/app/(tabs)/_layout.tsx)

Waxa la beddelay:
- haddii app-ku wali hydrate ku jiro, spinner ayaa la tusayaa
- haddii `session` maqan yahay:
  - redirect -> `/(auth)/phone`
- haddii session jiro:
  - tabs ayaa la furayaa

Sababta:
- user aan authenticated ahayn waa inuusan si toos ah ugu dhex bixi karin tabs routes
- tani waxay xireysaa route-level security gap

---

### 6. Auth routes hadda waxay leeyihiin reverse guard
File: [app/(auth)/_layout.tsx](/C:/Users/hp/SanabilMessages/app/(auth)/_layout.tsx)

Waxa la sameeyay:
- haddii app-ku wali hydrate ku jiro, spinner ayaa la tusayaa
- haddii session hore u jiro:
  - redirect -> `/(tabs)/inbox`
- haddii session maqan yahay:
  - auth stack ayaa la tusayaa

Sababta:
- user hore u galay mar dambe yuusan dib ugu laaban phone/verify screens si qalad ah
- tani waxay xoojinaysaa flow-ga “hal mar login, kadib gudaha joog”

---

## Natiijada shaqadan
Kadib hardening-kan:
- hydrate-ku waa controlled
- auth listener-ku waa single-source
- tabs routes waxay si adag ugu xiran yihiin session
- auth routes waxay si adag uga xiran yihiin logged-in users
- login state-ku wuxuu noqday mid deggan oo predictable ah

Tani si gaar ah muhiim ugu tahay:
- waalidiin aan tech badan aqoon
- app opening/reopening behavior
- revoked device handling
- silent session restore

---

## Sababta tani u tahay qodob core ah
Qof badan wuxuu moodi karaa in route guards ay yihiin detail yar, laakiin mashruucan taasi sax ma aha.

Sababta:
- haddii auth state-ku is dhex yaaco, OTP system-ka adag ee aan dhisnay qiimo badan ma yeelanayo
- haddii session maqan yahay oo user-ku screen gudaha ah ku jiro, experience-ku wuu jabayaa
- haddii listeners badan jiraan, debugging-ku aad buu u adkaanayaa

Marka tani waa:
**core stability work**

ma aha feature dheeraad ah.

---

## Verification
Waxa aan xaqiijiyay:
- files-ka guard/hydration ee la taabtay wax error cusub ah kama soo bixin targeted type-check filter-ka
- diff-ku wuxuu muujiyay:
  - `index.tsx` hydrate call waa laga saaray
  - `tabs` guard waa lagu daray
  - `auth` reverse guard waa lagu daray
  - auth store hydrate logic waa la mideeyay

---

## Gunaanad
Shaqadan waxay xirtay gudaha auth stack-ka qayb muhiim ah oo aan muuqaal badan lahayn laakiin aad u saameyn badan:

- hal hydrate flow
- hal auth listener
- route guards sax ah
- session restore behavior deggan

Tani waxay login/OTP stack-ka ka dhigaysaa mid aad ugu dhow inuu si dhab ah u xirmo.

---

## Next Goal
Shaqada xigta ee ugu saxan hadda waa:

**live end-to-end proof**

Waxaan rabnaa in lagu xaqiijiyo:
1. request OTP
2. WhatsApp delivery
3. verify success
4. trusted device register
5. app reopen without OTP
6. revoke current device -> forced logout behavior

Marka taas la sameeyo, login/OTP/auth core stack-ka si dhab ah ayuu u dhammaaday.
