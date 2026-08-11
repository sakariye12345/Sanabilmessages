const fs = require("fs");
const path = require("path");

const root = path.resolve(__dirname, "..");
const manifestPath = path.join(root, "config", "schools.manifest.json");
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));

const variant = process.argv[2];

if (!variant) {
  console.error("Usage: node ./scripts/school-build-helper.js <variant>");
  process.exit(1);
}

const config = manifest.schools?.[variant];

if (!config) {
  console.error(`Unknown variant '${variant}'. Run 'npm run schools:list' to see valid variants.`);
  process.exit(1);
}

console.log(`Variant: ${variant}`);
console.log(`School ID: ${config.schoolId}`);
console.log(`App Name: ${config.name}`);
console.log(`Package: ${config.package}`);
console.log(`Recommended Android APK build command:`);
console.log(`npm run eas:cli -- build --platform android --profile ${variant}`);
console.log(`Recommended Android production AAB command:`);
console.log(`npm run eas:cli -- build --platform android --profile production-${variant}`);
console.log(`EAS cloud environment validation command:`);
console.log(`npm run eas:env:validate -- --variant=${variant}`);
console.log(`Build contract validation command:`);
console.log(`node ./scripts/validate-build-contract.js --production --variants=${variant}`);
console.log(`Local config inspection command (PowerShell):`);
console.log(`$env:APP_VARIANT="${variant}"; npx expo config --type public`);
