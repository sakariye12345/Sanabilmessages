const fs = require("fs");
const path = require("path");
const { isPlaceholder, isValidHttpsUrl } = require("./school-matrix-utils");

const root = path.resolve(__dirname, "..");
const manifestPath = path.join(root, "config", "schools.manifest.json");
const productionMode = process.argv.includes("--production");

const requiredFields = [
  "name",
  "slug",
  "scheme",
  "package",
  "schoolId",
  "primaryColor",
  "supportPhone",
  "website",
];

function fail(message) {
  console.error(`VALIDATION FAILED: ${message}`);
  process.exitCode = 1;
}

function checkAsset(assetPath, fallbackAllowed = true) {
  if (!assetPath) return fallbackAllowed;
  const normalized = assetPath.startsWith("./") ? assetPath.slice(2) : assetPath;
  return fs.existsSync(path.join(root, normalized));
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const schools = manifest.schools || {};
const variants = Object.entries(schools);

if (!variants.length) {
  fail("No school variants were found in config/schools.manifest.json");
  process.exit(1);
}

const seenPackages = new Set();
const seenSchoolIds = new Set();

for (const [variant, config] of variants) {
  for (const field of requiredFields) {
    if (config[field] === undefined || config[field] === null || config[field] === "") {
      fail(`${variant}: missing required field '${field}'`);
    }
  }

  if (!Number.isInteger(Number(config.schoolId)) || Number(config.schoolId) <= 0) {
    fail(`${variant}: schoolId must be a positive integer`);
  }

  if (!/^com\.[a-z0-9]+(?:\.[a-z0-9]+)+$/i.test(config.package || "")) {
    fail(`${variant}: invalid Android package '${config.package}'`);
  }

  if (!/^#[0-9a-f]{6}$/i.test(config.primaryColor || "")) {
    fail(`${variant}: primaryColor must be a six-digit hex color`);
  }

  if (productionMode) {
    for (const field of ["name", "supportPhone", "website"]) {
      if (isPlaceholder(config[field])) {
        fail(`${variant}: production field '${field}' is still a placeholder`);
      }
    }

    if (!isValidHttpsUrl(config.website)) {
      fail(`${variant}: production website must be a real HTTPS URL`);
    }
  }

  if (seenPackages.has(config.package)) {
    fail(`${variant}: duplicate android package '${config.package}'`);
  }
  seenPackages.add(config.package);

  if (seenSchoolIds.has(String(config.schoolId))) {
    fail(`${variant}: duplicate schoolId '${config.schoolId}'`);
  }
  seenSchoolIds.add(String(config.schoolId));

  const assets = config.assets || {};
  for (const key of ["icon", "adaptiveIcon", "splashIcon", "favicon"]) {
    if (assets[key] && !checkAsset(assets[key])) {
      const message = `${variant}: asset '${key}' not found at '${assets[key]}'`;
      if (productionMode) {
        fail(message);
      } else {
        console.warn(`WARNING: ${message}. Build will fall back to default asset.`);
      }
    }
  }
}

if (process.exitCode !== 1) {
  const mode = productionMode ? "production-ready" : "structurally valid";
  console.log(`School manifest is ${mode}. Variants: ${variants.map(([variant]) => variant).join(", ")}`);
}
