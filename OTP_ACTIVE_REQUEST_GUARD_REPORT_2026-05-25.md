# Warbixin: OTP Active Request Guard
**Taariikh:** 2026-05-25

## Ujeeddada shaqadan
Shaqadan waxay ahayd hardening core ah oo ku saabsan halis aad muhiim u ah:

**sidee loo hubiyaa in hal parent uusan hal mar wada haysan laba OTP request oo `active` ah?**

Inkasta oo aan hore ugu darnay:
- request-side cooldown
- supersede logic gudaha `request-otp`

haddana waxaa wali jiri karta halis yar oo concurrency ah, gaar ahaan haddii laba request ay isku mar dhacaan. Taasi waxay keeni kartaa:
- laba row oo `PENDING`
- ama mid `PENDING` iyo mid `PROCESSING`
- taas oo sender-ka WhatsApp ku ridi karta state aan nadiif ahayn

Marka shaqadan ujeeddadeedu waxay ahayd:
**database-ka laftiisa ha noqdo ilaaliyaha ugu dambeeya ee “one active OTP per parent per school”**

---

## Waxa la sameeyay

### 1. Active OTP cleanup migration
File: [supabase/migrations/20260525182000_otp_active_request_uniqueness.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/20260525182000_otp_active_request_uniqueness.sql)

Waxa migration-ku sameynayaa:
1. wuxuu raadiyaa rows-ka `otp_queue` ee leh:
   - isla `school_id`
   - isla `phone`
   - status `PENDING` ama `PROCESSING`
2. haddii ay jiraan wax ka badan hal row:
   - kii ugu dambeeyay ayuu current ka dhigaa
   - kuwii ka horreeyay wuxuu u rogaa `FAILED`
   - `error_message` ayuu ku qoraa in la supersede-gareeyay

Sababta:
- queue-ga waa in marka hore la nadiifiyaa
- ka dib ayaana xeer cusub lagu adkeyn karaa

---

### 2. Unique partial index
Isla migration-kan gudaheeda waxaan ku darnay:

- `idx_otp_queue_one_active_request_per_parent`

Waxa uu enforce-gareynayaa:
- hal `school_id + phone` ma yeelan karo wax ka badan hal row oo:
  - `PENDING`
  - ama `PROCESSING`

Tani waa xeer aad muhiim u ah sababtoo ah:
- request-side checks way fiican yihiin
- laakiin database-level constraint ayaa ah difaaca ugu dambeeya

Marka hadda xitaa haddii concurrency dhacdo, DB-ga laftiisa ayaa diidaya state khaldan.

---

## Waxa live ahaan la sameeyay
Waxaan migration-kan live ugu riixay remote database-ka.

Xaqiijinta:
- `supabase db push --linked --dry-run` wuxuu caddeeyay in migration-kan keliya la riixayo
- kadib `supabase db push --linked` si guul leh ayuu u apply-gareeyay
- `supabase migration list --linked` wuxuu muujiyay:
  - `20260525182000` local
  - `20260525182000` remote

Tani waxay ka dhigan tahay:
**constraint-kan cusub hadda remote DB-ga wuu ku jiraa, ma aha local-only**

---

## Sidee tani ula xiriirtaa request-otp hardening-kii hore
Shaqadii hore ee [REQUEST_OTP_WHATSAPP_ONLY_HARDENING_REPORT_2026-05-25.md](</C:/Users/hp/SanabilMessages/REQUEST_OTP_WHATSAPP_ONLY_HARDENING_REPORT_2026-05-25.md>) waxay xoojisay:
- cooldown
- pause handling
- queue-only flow
- supersede logic

Shaqadan cusubna waxay ku dartay:
- **DB-enforced active request uniqueness**

Marka labada shaqo marka la isu geeyo, nidaamku wuxuu noqday:
1. app request
2. request-side policy check
3. old active rows cleanup
4. DB-level uniqueness enforcement
5. sender reads clean queue

---

## Sababta tani u tahay qodob core ah
Haddii hal parent uu yeesho laba active OTP:
- user-ku ma kala garan karo code-keebaa current ah
- sender-ku wuxuu diri karaa code khaldan ama laba code
- support/debugging way adkaanaysaa
- WhatsApp volume aan loo baahnayn ayuu kordhin karaa

Tani waxay si gaar ah muhiim ugu tahay mashruucaaga, sababtoo ah adigu si cad ayaad u rabtay:
- login fudud
- hal mar verification
- automation discipline
- anti-ban taxaddar

Marka xeerkan DB-level ah waa qayb ka mid ah “solid core”.

---

## Gunaanad
Shaqadan waxay xirtay halis muhiim ah oo ah race-condition / duplicate-active-OTP state.

Hadda `otp_queue` wuxuu leeyahay laba difaac:
- logic-level difaac gudaha `request-otp`
- database-level difaac gudaha partial unique index

Taasi waxay OTP login-ka ka dhigaysaa mid aad uga adag kana nadiifsan kii hore.

---

## Next Goal
Shaqada xigta ee ugu saxan hadda waa:

**hal live OTP proof oo dhamaystiran**

Waxaan rabnaa in si dhab ah loo xaqiijiyo:
1. request cusub
2. hal active queue row oo keliya
3. WhatsApp delivery
4. verify success
5. trusted device row update
6. push token sync

Marka taas la sameeyo, waxaan si adag u oran karnaa:
**OTP core stack-ku wuu xirmay oo wuu adkaystay**
