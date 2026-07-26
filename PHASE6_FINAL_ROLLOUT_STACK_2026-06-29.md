# Phase 6: Final Rollout Stack

Taariikh: `2026-06-29`

## Ujeeddada

In tijaabada buuran laga gaarsiiyo heerka ugu dambeeya ee execution support:

- preflight commands
- Supabase load command
- school-by-school rollout checklist
- rollout status tracker

## Waxa La Kordhiyay

### 1. Live rollout checklist generator

File:

- [scripts/generate-live-rollout-pack.js](/C:/Users/hp/SanabilMessages/scripts/generate-live-rollout-pack.js)

Run:

```powershell
npm run schools:rollout:generate
```

Waxa uu soo saarayaa:

- [generated/live_rollout_checklist.generated.md](/C:/Users/hp/SanabilMessages/generated/live_rollout_checklist.generated.md)

Checklist-kan wuxuu ka kooban yahay:

- global preflight
- Supabase load command
- school-by-school build/login/message routing steps
- final cross-school isolation test

### 2. Rollout status tracker

File:

- [rollout_status_tracker.csv](/C:/Users/hp/SanabilMessages/rollout_status_tracker.csv)

Isticmaal:

- school walba status-kiisa
- device walba status-kiisa
- owner
- last checked time
- notes

## Sida Loo Adeegsanayo

### Step 1

Run:

```powershell
npm run schools:rollout:generate
```

### Step 2

Fur:

- [generated/live_rollout_checklist.generated.md](/C:/Users/hp/SanabilMessages/generated/live_rollout_checklist.generated.md)

### Step 3

Isticmaal:

- [rollout_status_tracker.csv](/C:/Users/hp/SanabilMessages/rollout_status_tracker.csv)

si aad u calaamadiso:

- DONE
- BLOCKED
- RETEST
- FAILED

### Step 4

Global execution order:

1. validate manifests
2. validate school matrix
3. validate device matrix
4. generate pilot seed
5. generate runbook
6. generate live rollout checklist
7. load Supabase data
8. activate OTP sessions
9. build APKs
10. install on devices
11. run message routing tests
12. run cross-school isolation test

## Gunaanad

Hadda wixii dhinaca diyaarinta iyo qalabaynta ahaa way xiran yihiin.

Waxa xiga waa `actual rollout execution`:

- real URLs/tokens geli
- seed ku shub
- sessions shid
- APKs build-garee
- devices tijaabi

Tani waa meesha ay ka bilaabanayso tijaabada production-style ee dhabta ahi.
