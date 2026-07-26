# Warbixinta Device Management iyo Trust Re-check

Taariikh: `2026-05-23`
Mashruuc: `Sanabil Messages`

## Ujeeddada Shaqadan

Shaqadan waxay dhammeystiraysay laba meelood oo wali ka maqnaa trusted-device architecture-ka:

1. user-ku inuu arki karo `trusted devices`-kiisa gudaha app-ka
2. device revoke-ku inuu dhaqan galo xitaa haddii app-ku furan yahay, marka app-ku dib foreground ugu soo noqdo

## Waxa La Qabtay

### 1. Profile-ka waxaa lagu daray `Trusted Devices`

File:

- [app/profile.tsx](/C:/Users/hp/SanabilMessages/app/profile.tsx)

Waxyaabaha cusub:

- current device waa la calaamadeeyaa
- devices kale way kasoo muuqdaan
- `last_seen` iyo `last_login` way muuqdaan
- device kasta waa laga saari karaa trusted devices
- haddii current device la saaro, user-ku si toos ah ayuu uga baxayaa app-ka

### 2. App-ku wuxuu sameeyaa `trust re-check` marka uu foreground ku soo laabto

Files:

- [app/_layout.tsx](/C:/Users/hp/SanabilMessages/app/_layout.tsx)
- [src/store/auth.ts](/C:/Users/hp/SanabilMessages/src/store/auth.ts)

Waxa hadda dhacaya:

- app-ku marka uu `active` noqdo, wuxuu mar kale xaqiijiyaa in current device-ku wali trusted yahay
- haddii device-ka dibadda laga revoke gareeyay, session-ku waa la saarayaa

Tani waa muhiim sababtoo ah revoke-ku hadda kuma xirna oo keliya app restart.

### 3. Device trust helpers waa la ballaariyay

File:

- [src/services/deviceTrust.ts](/C:/Users/hp/SanabilMessages/src/services/deviceTrust.ts)

Waxa lagu daray:

- `listMyDevices()`
- `revokeDeviceTrust(deviceId)`
- typed device rows oo profile-ku si ammaan ah u isticmaalo

### 4. UI colors waxaa lagu daray `error`

File:

- [constants/Colors.ts](/C:/Users/hp/SanabilMessages/constants/Colors.ts)

Sababtu waxay ahayd in destructive actions sida revoke/logout ay yeeshaan color rasmi ah oo consistent ah.

## Verification

Waxaan sameeyay:

- targeted type check ku wajahan files-ka shaqadan taabanaya

Natiijo:

- files-ka cusub ma keenin type error

## Natiijada

Hadda trusted-device system-ku wuxuu gaaray heerkan:

1. device trust waa la diiwaangeliyaa
2. device trust waa la arki karaa
3. device trust waa laga saari karaa
4. revoke-ku wuxuu dhaqan gelayaa marka app-ku dib active noqdo

Tani waxay si weyn u dhammaystirtay security-ga iyo support flow-ga dhinaca user-ka.

## Next Goal

`VPS rollout for whatsapp-service hardening`

Tani wali waa qodobka ugu muhiimsan ee ka haray operationally, sababtoo ah:

- code-ka hardening-ku repo-ga wuu yaal
- DB policy-ga live wuu yaal
- laakiin service-ka ku socda VPS-ka waa inuu qaataa code-kan cusub si cooldown, caps, pause, iyo stale recovery ay runtii u shaqeeyaan
