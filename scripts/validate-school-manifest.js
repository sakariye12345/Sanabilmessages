const fs = require("fs");
const path = require("path");
const { isPlaceholder, isValidHttpsUrl } = require("./school-matrix-utils");

const root = path.resolve(__dirname, "..");
const manifestPath = path.join(root, "config", "schools.manifest.json");
const productionMode = process.argv.includes("--production");

const requiredFields = [
  "name",
  "slug",
  "easProjectId",
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
const seenSlugs = new Set();
const seenEasProjects = new Set();

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

  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(config.slug || "")) {
    fail(`${variant}: invalid Expo slug '${config.slug}'`);
  }

  const hasValidEasProjectId = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i
    .test(config.easProjectId || "");
  if (!hasValidEasProjectId) {
    const message = `${variant}: easProjectId must be the UUID of that school's EAS project`;
    if (productionMode) fail(message);
    else console.warn(`WARNING: ${message}. Push notification builds are not ready.`);
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

  if (seenSlugs.has(config.slug)) {
    fail(`${variant}: duplicate Expo slug '${config.slug}'`);
  }
  seenSlugs.add(config.slug);

  if (hasValidEasProjectId) {
    if (seenEasProjects.has(config.easProjectId)) {
      const message = `${variant}: duplicate EAS project ID '${config.easProjectId}'`;
      if (productionMode) fail(message);
      else console.warn(`WARNING: ${message}. Client apps should use independent EAS projects.`);
    }
    seenEasProjects.add(config.easProjectId);
  }

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
