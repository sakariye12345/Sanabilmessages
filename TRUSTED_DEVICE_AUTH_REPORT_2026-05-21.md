# Warbixinta Trusted Device Auth

Taariikh: `2026-05-21`
Mashruuc: `Sanabil Messages`

## Ujeeddada Shaqadan

Shaqadan ujeeddadeedu waxay ahayd in login-ka app-ka laga saaro qaabka ah:

- OTP mar kasta la diro
- user-ku marar badan dib isu xaqiijiyo
- session-ku ku ekaado restore fudud oo aan lahayn device control

Qaabka cusub ee la dhisay waa:

- parent-ka hal mar ha isku xaqiijiyo `WhatsApp OTP`
- device-kaas ha noqdo `trusted device`
- session-ku ha sii noolaado si otomaatig ah
- OTP mar kale ha dhacdo oo keliya marka ay jirto:
  - `new device`
  - `reinstall`
  - `recovery`
  - `device revoke`

## Waxa La Beddelay

### 1. `user_devices` waxaa loo beddelay trusted-device registry

Waxaa lagu daray:

- `device_id`
- `device_name`
- `app_variant`
- `trusted_at`
- `revoked_at`
- `last_login_at`
- `updated_at`

Tani waxay ka dhigan tahay in `user_devices` aanu hadda ahayn table push token oo keliya, balse uu noqday diiwaanka qalabka uu user-ku ku aaminan yahay.

### 2. Waxaa la sameeyay secure RPCs cusub

Waxaa la dhisay:

- `register_my_device(...)`
- `get_my_device_trust(device_id)`

Faa’iidada ay leeyihiin:

- app-ku si toos ah uma maamulo `user_devices` row-yo furan
- phone-ka user-ka waxaa laga qaataa `JWT auth context`
- trusted-device logic-ku wuxuu galay database contract rasmi ah

### 3. App-ku wuxuu helay `persistent device identity`

File cusub:

- [src/services/deviceTrust.ts](/C:/Users/hp/SanabilMessages/src/services/deviceTrust.ts)

Shaqadiisu waa:

- inuu hal `device_id` ku kaydiyo `SecureStore`
- inuu qalabkaas ku diiwaangaliyo trusted-device ahaan
- inuu hubiyo marka app-ku furmo in device-kan wali active yahay

### 4. Auth hydration waa la adkeeyay

File:

- [src/store/auth.ts](/C:/Users/hp/SanabilMessages/src/store/auth.ts)

Waxa hadda dhacaya marka app-ku furmo:

- session-ka Supabase waa la soo ceshanayaa
- haddii session jiro, trusted-device check ayaa la sameynayaa
- haddii device-ka la revoke gareeyay ama la damiyay, session-ka waa la saarayaa
- haddii uu yahay device sax ah, app-ku si caadi ah ayuu u sii furmayaa

### 5. OTP verify flow waxaa lagu xidhay trusted-device registration

File:

- [app/(auth)/verify.tsx](</C:/Users/hp/SanabilMessages/app/(auth)/verify.tsx>)

Waxa hadda dhacaya kadib marka OTP-gu sax noqdo:

- Supabase session waa la abuuraa
- qalabka hadda la isticmaalay waxaa lagu qoraa trusted-device registry
- push token haddii la heli karo waa lala kaydiyaa
- user-ka si toos ah ayaa loogu gudbiyaa inbox-ka

## Waxa Live Loo Fuliyay

Migration-kan waxaa live ahaan lagu orodsiiyay database-ka:

- [supabase/migrations/trusted_device_auth.sql](/C:/Users/hp/SanabilMessages/supabase/migrations/trusted_device_auth.sql)

Waxaan sidoo kale xaqiijiyay in RPC-yada cusub ay database-ka ka jiraan:

- `register_my_device`
- `get_my_device_trust`

## Natiijada Shaqadan

Marka architecture ahaan hadda waxaan gaarnay qaabkan:

1. parent-ka waa inuu ku jiro `allowed_parents`
2. parent-ku hal mar ayuu ku galaa `WhatsApp OTP`
3. device-kaas waxaa lagu diiwaangeliyaa `trusted`
4. app-ku wuxuu mar kasta isku dayaa `session restore`
5. haddii device-ku trusted yahay, user-ka mar kale OTP looma baahna

Tani waa qaabka saxda ah ee waalidka loogu fududeyn karo isticmaalka app-ka, iyadoo weli la ilaalinayo xakamayn iyo revoke capability.

## Waxa Weli Haray

Shaqadan weli waxaa ka haray 3 waxyaabood oo muhiim ah:

1. `logout/revoke UX`
   - admin ama user sidee u daminayaa device gaar ah
2. `request-otp suppression`
   - app-ka sidee uga fogaanayaa OTP request marka user-ku horeba trusted u yahay oo session-ku socdo
3. `WhatsApp OTP policy hardening`
   - queue caps
   - cooldown
   - pause/resume per school

## Next Goal

`WhatsApp OTP policy hardening + device revoke controls`

Tani waa shaqada xigta ee ugu saxan sababtoo ah:

- login-ka hal-mar ah architecture ahaan waa la dhisay
- hadda waa in la xakameeyo volume-ka OTP
- waa in la sii diyaariyaa control-ka haddii school ama admin doonayo inuu device damiyo
