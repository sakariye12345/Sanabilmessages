# Pilot Execution Runbook

Generated: 2026-06-29T18:29:37.319Z

This runbook was generated from:

- `school_matrix_template.csv`
- `pilot_device_test_matrix.csv`

Use it during the live multi-school pilot.

## Device-1

- App Variant: `sanabil`
- Expected School ID: `1`
- Expected App Name: `Sanabil Messages`
- Test Parent Phone: `252630000111`
- Parents Source: `https://demo.saafisystems.com`
- Messages Source: `https://schoolsfls443dr4rsm53m.shihaab.tech`
- OTP Node: `VPS-1`
- OTP Session Status: `CONNECTED`

### Build
```powershell
APP_VARIANT=sanabil eas build --platform android --profile school-apk
```

### Install Checklist
1. Install APK on Device-1
2. Confirm app name is `Sanabil Messages`
3. Confirm login number used is `252630000111`
4. Request WhatsApp OTP
5. Confirm OTP arrives from the correct school session
6. Verify login
7. Confirm inbox opens
8. Confirm only school Sanabil messages appear

### Message Routing Test
1. Send a message from `Sanabil` source
2. Confirm message lands in Supabase
3. Confirm `Device-1` receives the message
4. Confirm the other devices do not receive it
## Device-2

- App Variant: `schoolb`
- Expected School ID: `4`
- Expected App Name: `School B Messages`
- Test Parent Phone: `252630000221`
- Parents Source: `https://demo-schoolb-parents.example`
- Messages Source: `https://demo-schoolb-messages.example`
- OTP Node: `VPS-1`
- OTP Session Status: `DISCONNECTED`

### Build
```powershell
APP_VARIANT=schoolb eas build --platform android --profile school-apk
```

### Install Checklist
1. Install APK on Device-2
2. Confirm app name is `School B Messages`
3. Confirm login number used is `252630000221`
4. Request WhatsApp OTP
5. Confirm OTP arrives from the correct school session
6. Verify login
7. Confirm inbox opens
8. Confirm only school School B messages appear

### Message Routing Test
1. Send a message from `School B` source
2. Confirm message lands in Supabase
3. Confirm `Device-2` receives the message
4. Confirm the other devices do not receive it

## Device-3

- App Variant: `schoolc`
- Expected School ID: `5`
- Expected App Name: `School C Messages`
- Test Parent Phone: `252630000331`
- Parents Source: `https://demo-schoolc-parents.example`
- Messages Source: `https://demo-schoolc-messages.example`
- OTP Node: `VPS-1`
- OTP Session Status: `DISCONNECTED`

### Build
```powershell
APP_VARIANT=schoolc eas build --platform android --profile school-apk
```

### Install Checklist
1. Install APK on Device-3
2. Confirm app name is `School C Messages`
3. Confirm login number used is `252630000331`
4. Request WhatsApp OTP
5. Confirm OTP arrives from the correct school session
6. Verify login
7. Confirm inbox opens
8. Confirm only school School C messages appear

### Message Routing Test
1. Send a message from `School C` source
2. Confirm message lands in Supabase
3. Confirm `Device-3` receives the message
4. Confirm the other devices do not receive it

## Device-4

- App Variant: `schoold`
- Expected School ID: `6`
- Expected App Name: `School D Messages`
- Test Parent Phone: `252630000441`
- Parents Source: `https://demo-schoold-parents.example`
- Messages Source: `https://demo-schoold-messages.example`
- OTP Node: `VPS-1`
- OTP Session Status: `DISCONNECTED`

### Build
```powershell
APP_VARIANT=schoold eas build --platform android --profile school-apk
```

### Install Checklist
1. Install APK on Device-4
2. Confirm app name is `School D Messages`
3. Confirm login number used is `252630000441`
4. Request WhatsApp OTP
5. Confirm OTP arrives from the correct school session
6. Verify login
7. Confirm inbox opens
8. Confirm only school School D messages appear

### Message Routing Test
1. Send a message from `School D` source
2. Confirm message lands in Supabase
3. Confirm `Device-4` receives the message
4. Confirm the other devices do not receive it
