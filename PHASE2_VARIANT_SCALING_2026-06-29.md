# Phase 2: Variant Scaling Foundation

Taariikh: `2026-06-29`

## Ujeeddada

In app instances badan loo diyaariyo qaab la maamuli karo, si schoolo badan loogu dari karo adigoon `app.config.js` mar kasta gacanta ku beddelin.

## Waxa La Beddelay

### 1. Variant config-ka waxaa laga saaray hardcode

Hore:

- [app.config.js](/C:/Users/hp/SanabilMessages/app.config.js) wuxuu school variants-ka si toos ah ugu dhex qori jiray file-ka

Hadda:

- variants-ka waxay ku jiraan [config/schools.manifest.json](/C:/Users/hp/SanabilMessages/config/schools.manifest.json)

Tani waxay kuu oggolaanaysaa:

- school cusub si fudud in loogu daro hal file
- package, schoolId, assets, colors, support info in hal meel lagu maamulo

### 2. Asset fallback

Hadda haddii school cusub aanu wali lahayn:

- icon
- adaptive icon
- splash icon

build-ku si toos ah uguma jabayo.

Waxa uu fallback ugu noqonayaa:

- default assets-ka Sanabil

Tani waa muhiim pilot-ka, sababtoo ah school cusub ayaa lagu dari karaa xitaa ka hor inta branding-ku dhammaan.

### 3. Validator script

Waxaa lagu daray:

- [scripts/validate-school-manifest.js](/C:/Users/hp/SanabilMessages/scripts/validate-school-manifest.js)

Script-kan wuxuu hubiyaa:

- fields-ka waajibka ah
- duplicate `package`
- duplicate `schoolId`
- asset paths maqan

Run:

```powershell
npm run schools:validate
```

### 4. Variant list script

Waxaa lagu daray:

- [scripts/list-school-variants.js](/C:/Users/hp/SanabilMessages/scripts/list-school-variants.js)

Run:

```powershell
npm run schools:list
```

Waxay kuu soo saaraysaa:

- variant
- schoolId
- package
- app name

## Sidee School Cusub Loogu Dari Karaa

1. Fur [config/schools.manifest.json](/C:/Users/hp/SanabilMessages/config/schools.manifest.json)
2. Ku dar object cusub
3. Geli:
   - `name`
   - `slug`
   - `scheme`
   - `package`
   - `schoolId`
   - `primaryColor`
   - `supportPhone`
   - `website`
4. Assets-ka haddii aanay wali diyaar ahayn, iska dhaaf ama default ha noqdaan
5. Orod:

```powershell
npm run schools:validate
```

## Xiriirka Phase 1 iyo Phase 2

### Phase 1

wuxuu diyaariyay:

- school matrix
- seed template
- onboarding structure

### Phase 2

wuxuu diyaariyay:

- app variant scaling structure
- build config source of truth

Labadoodu marka ay is biiraan:

- backend school rows
- app variants

ayaa isu ekaanaya qaab la xakameyn karo.

## Waxa Weli Ka Haray

Kani weli ma samayn:

- `eas.json` profiles 10 school oo dhamaystiran
- build automation full ah
- school-specific assets production-ready ah

Laakiin wuxuu sameeyay saldhigga saxda ah ee taas lagu gaari karo.

## Next Goal

Kadib Phase 2, shaqada xigta waa:

1. ku dar `4 pilot variants`
2. school matrix-ka la jaanqaad variant names-ka
3. build test APKs
4. ku rakib devices

## Gunaanad

Haddii variant scaling la’aan lagu socdo, 10 school waxay isu beddeli karaan config fowdo.

Hadda waxaan ka dhignay:

- school config = hal manifest
- app config = runtime reader
- validation = script
- asset gaps = fallback

Taasi waa foundation sax ah oo rollout-ka schoolo badan lagu bilaabi karo.
