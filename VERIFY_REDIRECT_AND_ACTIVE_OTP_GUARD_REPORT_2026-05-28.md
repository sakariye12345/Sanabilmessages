# Verify Redirect iyo Active OTP Guard

Taariikh: `2026-05-28`

## Ujeeddada

In la xiro laba mushkiladood oo toos u saameynayay `login + OTP verification`:

1. `Verify` kadib user-ka inuu dib ugu laabto `OTP request page`
2. In isla number-ka loo diri karo `OTP` cusub iyadoo kii hore wali shaqaynayo

## Waxa La Arkay

Tijaabada mobile-ka waxay muujisay:

- `request-otp` wuu shaqeeyay, WhatsApp-na OTP wuu diray
- laakiin `verify` kadib app-ku mar kale wuxuu dib ugu laabanayay screen-kii hore
- sidoo kale isla user-ka ayaa heli karay laba OTP muddo gaaban gudahood

Labadan arrimood waa kuwo `core auth flow` jebin kara, sidaas darteed waa la mudnaansiiyay.

## Mushkiladda 1: Verify kadib dib ugu noqoshada auth page

### Root Cause

Marka `verify-otp` uu guulaysto:

1. app-ku wuxuu sameynayay `signInWithPassword`
2. kadib trusted device ayuu register-gareynayay
3. kadib ayuu u wareegayay `/(tabs)/inbox`

Laakiin `TabsLayout` iyo `AuthLayout` waxay ku tiirsanaayeen `useAuthStore.session`.
Xaalado qaar, `Supabase session` wuu jiray, laakiin `Zustand auth store` wali si buuxda uma xasilloonayn ka hor inta aan route-ku is beddelin.

Natiijadu waxay ahayd:

- user-ka wuxuu u wareegayay tabs
- tabs guard-ku wuxuu arki jiray `session = null`
- kadib wuxuu dib ugu celin jiray `/(auth)/phone`

### Waxa La Saxay

Waxaa lagu daray `syncActiveSession()` gudaha:

- [src/store/auth.ts](/C:/Users/hp/SanabilMessages/src/store/auth.ts)

Shaqadiisu waa:

- inuu `Supabase` ka soo qaato current session-ka
- inuu ku dhaqaajiyo isla `trust sync` logic-ka
- inuu si toos ah u buuxiyo `auth store`

Kadib `verify.tsx`:

- trusted device register-ka kadib
- wuxuu wacayaa `syncActiveSession()`
- markaas oo keliya ayuu u wareegayaa inbox

Tani waxay ka dhigaysaa transition-ka:

`verify -> session sync -> trusted state -> inbox`

halkii ay ka ahaan lahayd:

`verify -> inbox -> auth guard race`

## Mushkiladda 2: OTP labaad iyadoo kii hore wali active yahay

### Root Cause

`request-otp` wuxuu hore u fiirinayay keliya `cooldown` gaaban, tusaale `30 seconds`.

Taasi waxay ka dhigan tahay:

- haddii user-ku sugo wax yar
- laakiin OTP-gii hore wali yahay `valid`
- haddana system-ku wuxuu samayn karay OTP cusub

Tani ma fiicna sababtoo ah:

- user-ku wuu wareerayaa
- OTP-yo badan ayaa baxaya
- WhatsApp volume ayaa si aan loo baahnayn u kordhaya
- verification flow-gu wuu wasakhoobayaa

### Waxa La Saxay

Gudaha:

- [supabase/functions/request-otp/index.ts](/C:/Users/hp/SanabilMessages/supabase/functions/request-otp/index.ts)

waxaa lagu daray `active OTP guard`.

Qaabka cusub:

1. system-ku marka hore wuxuu eegaa OTP-ga ugu dambeeya ee `PENDING / PROCESSING / SENT`
2. haddii uu wali ku jiro muddada ansaxnimada (`10 minutes`)
   - OTP cusub lama abuuro
   - user-ku wuxuu helayaa response ah `existing_active`
3. haddii kii hore dhacay
   - waxaa loo calaamadeeyaa `FAILED`
   - markaas kaliya OTP cusub ayaa la oggolaanayaa

Tani waxay ka dhigaysaa nidaamka:

- hal active OTP oo keliya
- hal valid OTP window oo keliya
- resend-yadu ma abuuraan code cusub ilaa kii hore dhammaado ama la isticmaalo

## Verification

Waxaa la sameeyay live smoke test oo laba jeer isku xigta loo wacay `request-otp`.

Natiijo:

```json
{
  "status": "existing_active",
  "queued": false,
  "reused": true
}
```

Tani waxay caddeysay:

- system-ku ma abuurayo OTP cusub
- wuxuu ilaalinayaa kii hore ee active-ka ahaa

## Faa’iidada Ganacsi ahaan iyo Technical ahaan

Fix-kan wuxuu xallinayaa laba qodob oo muhiim ah:

### User experience

- waalidku verify kadib dib uguma laabanayo page-kii hore
- waalidku ma helayo OTP-yo badan oo is burinaya

### Operations

- WhatsApp OTP volume-ka waa la xakameeyay
- queue-gu ma samaynayo requests aan loo baahnayn
- auth flow-gu wuxuu noqday mid deggan

## Xaaladda Hadda

Qaybaha la saxay:

- `verify -> session state stabilization`
- `active OTP reuse / no duplicate OTP while valid`

## Waxa Xiga

Tallaabada xigta ee saxda ah:

1. mobile app-ka `reload` ama `expo start -c`
2. samee hal login test:
   - request OTP
   - geli code-ka
   - xaqiiji inuu si toos ah kuu geliyo inbox
3. isku day `request OTP` mar labaad inta kii hore wali shaqaynayo
   - waa inuu kuu soo celiyo `existing_active`, mana aha inuu kuu diro code cusub

## Gunaanad

Labadan fix waxay xireen laba meelood oo core ah:

- `routing/auth state race`
- `duplicate OTP issuance`

Marka la eego dhismaha guud ee mashruuca, tani waa hardening muhiim ah oo si toos ah u xasillinaya `login + OTP verification` flow-ga.
