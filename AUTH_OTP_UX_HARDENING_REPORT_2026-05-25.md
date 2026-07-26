# Warbixin: Auth + OTP UX Hardening
**Taariikh:** 2026-05-25

## Ujeeddada shaqadan
Shaqadan waxay diiradda saartay qaybta user-ku si toos ah u arko:

**phone screen -> OTP request -> verify screen**

Core backend-ka iyo queue logic-ga waan adkeynay, laakiin haddii app-ka auth screens-ku weli yihiin kuwo basic ah:
- user-ku ma fahmayo in OTP WhatsApp lagu dirayo
- user-ku ma fahmayo sababta mar labaad request loo diiday
- user-ku ma arko inta uu cooldown-ku ka harsan yahay
- resend behavior-ku wuxuu ahaanayaa mid qallafsan

Marka shaqadan ujeeddadeedu waxay ahayd:
**in login/OTP flow-ga loo dhigo mid user-friendly, laakiin weli adkaynaya policy-ga core-ka ah**

---

## Waxa la sameeyay

### 1. `request-otp` response contract-ka waa la caddeeyay
File: [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

Waxa lagu daray response fields cad:
- `status`
- `cooldown_seconds`
- `provider`
- `paused`
- `message`

Status-yada cusub:
- `queued`
- `existing_active`
- `paused`

Sababta:
- app-ka waa inuu backend-ka ka helo response la fahmi karo
- ma fiicna in app-ku guess sameeyo ama string message random ah ku tiirsanaado

Tani waxay ka dhigaysaa contract-ka request layer mid rasmi ah.

---

### 2. Phone screen-ka waa la hagaajiyay
File: [app/(auth)/phone.tsx](/C:/Users/hp/SanabilMessages/app/(auth)/phone.tsx)

Waxa la beddelay:
- text-ka screen-ka waxaa loo caddeeyay in OTP-gu ku imanayo `WhatsApp`
- backend response-ka hadda si sax ah ayaa loo fahmayaa
- haddii service-ku paused yahay, user-ku wuxuu helayaa error nadiif ah
- marka request-ku guulaysto, verify screen-ka waxaa loo gudbiyaa:
  - `phone`
  - `cooldown`
  - `statusMessage`

Sababta:
- verify screen-ku waa inuu ogaadaa xaaladda request-ka ugu dambeeyay
- user-ku marka uu gaaro verify screen-ka waa inuu durba arkaa waxa socda

---

### 3. Verify screen-ka waxaa lagu daray cooldown-aware resend flow
File: [app/(auth)/verify.tsx](/C:/Users/hp/SanabilMessages/app/(auth)/verify.tsx)

Waxa la beddelay:
- verify screen-ku hadda wuxuu muujinayaa:
  - xaaladda codsiga OTP
  - fariin sharxaysa in OTP WhatsApp lagu soo dirayo
  - cooldown countdown
- waxaa lagu daray `Resend OTP` button
- button-kaasi:
  - wuu xanniban yahay inta cooldown-ku socdo
  - wuu furmaa marka waqtigu dhammaado
- resend request-ku wuxuu mar kale wacayaa `request-otp`
- response-ka cusub wuxuu update-gareynayaa:
  - status message
  - cooldown timer

Sababta:
- backend-ku hore ayuu u diidi jiray retries degdegga ah
- laakiin user-ku ma arki jirin sababta
- hadda app-ku policy-ga backend-ka si muuqata ayuu u tarjumayaa

Tani waa improvement core ah, sababtoo ah waxay yaraynaysaa:
- jahawareer user
- repeated taps
- support friction

---

### 4. First-login flow weli trusted-device ayuu ku dhammaanayaa
File: [app/(auth)/verify.tsx](/C:/Users/hp/SanabilMessages/app/(auth)/verify.tsx)

Waxii hore ee trusted device registration-ka waan sii haynay:
- OTP verify success
- session abuurid
- trusted device registration

Sababta:
- UX hardening-ku ma beddelin core auth design-ka
- wuxuu kaliya ka dhigay user experience-ka mid cad oo xooggan

---

## Waxa live ahaan la sameeyay
Waxaan live u deploy-gareeyay mar kale:
- `request-otp`

Sababta:
- response fields-ka cusub ee app-ku ku tiirsan yahay waa in backend-ka remote-ku hayaa
- haddii aan deploy la samayn, app-ka cusub iyo function-ka duugga ahi isma fahmi lahaayeen si fiican

Tani waxay ka dhigan tahay:
**request-otp contract-ka cusub hadda live ayuu jiraa**

---

## Natiijada shaqadan
Kadib hardening-kan, auth flow-gu wuxuu noqday mid aad u cad:

1. user phone-ka ayuu geliyaa
2. app-ku wuxuu si cad u ogyahay in OTP WhatsApp lagu dirayo
3. haddii request hore u jiro, user-ku wuxuu arkaa fariin sax ah
4. verify screen-ku wuxuu muujinayaa cooldown
5. resend lama oggola ilaa waqtiga saxda ahi gaaro
6. verify guulaysta -> trusted device register

Taasi waxay ka dhigaysaa experience-ka:
- fudud
- la fahmi karo
- aan buuq lahayn
- la jaanqaadaya policy-ga anti-spam ee backend-ka

---

## Sababta tani u tahay qodob core ah
Dad badan waxay u arkaan resend/countdown inuu yahay “UX detail” keliya, laakiin mashruucaaga tani sidaas ma aha.

Sababta:
- OTP volume-ka waa in la xakameeyaa
- WhatsApp automation-ka waa in laga ilaaliyaa retries aan loo baahnayn
- waalidka aan tech badan aqoon waa in aan la wareerin

Marka screen-level clarity waxay si toos ah u taageeraysaa:
- OTP policy
- ban-risk reduction
- login success rate

Sidaas darteed tani waa hardening core, ma aha qurxin kaliya.

---

## Verification
Waxa aan xaqiijiyay:
- `request-otp` mar kale live ayaa loo deploy-gareeyay
- targeted type-check filter-ka files-kan wax error cusub ah kama soo saarin

Ogow:
- repo-ga oo dhan wali wuxuu yeelan karaa type issues duug ah oo meelo kale yaal
- laakiin files-ka auth/OTP ee aan shaqadan ku taabtay wax issue cusub ah kama soo bixin

---

## Gunaanad
Shaqadan waxay ka dhigtay login/OTP flow-ga mid:
- backend-policy aware
- user-friendly
- resend disciplined
- WhatsApp-only architecture la jaanqaadaya

Core stack-ga hadda wuxuu sii dhowaanayaa xidhitaan dhab ah.

---

## Next Goal
Shaqada xigta ee ugu saxan hadda waa:

**live OTP proof on real device**

Waxaan rabnaa hal test oo dhammeystiran:
1. phone screen request
2. verify screen countdown
3. WhatsApp OTP arrival
4. verify success
5. trusted device row
6. push token sync
7. second app open without OTP

Marka taas la xaqiijiyo, login/OTP stack-ka waxaa lagu sheegi karaa mid si dhab ah u xirmay.
