const fs = require("fs");
const path = require("path");
const {
  filterSelectedVariants,
  isPlaceholder,
  selectedVariants,
} = require("./school-matrix-utils");

const root = path.resolve(__dirname, "..");
const manifestPath = path.join(root, "config", "schools.manifest.json");
const easPath = path.join(root, "eas.json");
const appConfigPath = path.join(root, "app.config.js");
const productionMode = process.argv.includes("--production");
const selected = selectedVariants();
const errors = [];
const warnings = [];

function loadJson(filePath, label) {
  try {
    return JSON.parse(fs.readFileSync(filePath, "utf8"));
  } catch (error) {
    throw new Error(`${label} could not be loaded: ${error.message}`);
  }
}

function resolveExpoConfig(variant) {
  const previousVariant = process.env.APP_VARIANT;
  try {
    process.env.APP_VARIANT = variant;
    delete require.cache[require.resolve(appConfigPath)];
    return require(appConfigPath).expo;
  } finally {
    if (previousVariant === undefined) delete process.env.APP_VARIANT;
    else process.env.APP_VARIANT = previousVariant;
  }
}

function expectEqual(label, actual, expected) {
  if (actual !== expected) {
    errors.push(`${label} is '${actual ?? "MISSING"}'; expected '${expected}'`);
  }
}

function validateProfile(variant, profileName, profile, expectedEnvironment, expectedBuildType) {
  if (!profile) {
    errors.push(`${variant}: EAS build profile '${profileName}' is missing`);
    return;
  }

  expectEqual(`${variant}: ${profileName}.environment`, profile.environment, expectedEnvironment);
  expectEqual(`${variant}: ${profileName}.env.APP_VARIANT`, profile.env?.APP_VARIANT, variant);
  expectEqual(`${variant}: ${profileName}.android.buildType`, profile.android?.buildType, expectedBuildType);

  if (expectedBuildType === "apk" && profile.distribution !== "internal") {
    errors.push(`${variant}: ${profileName}.distribution must be 'internal' for pilot APKs`);
  }

  if (profile.android?.autoIncrement !== true) {
    errors.push(`${variant}: ${profileName}.android.autoIncrement must be true`);
  }
}

const manifest = loadJson(manifestPath, "School manifest");
const eas = loadJson(easPath, "EAS config");
const manifestEntries = Object.entries(manifest.schools || {});
let variants;

try {
  variants = filterSelectedVariants(
    manifestEntries,
    ([variant]) => variant,
    selected,
    "School manifest"
  );
} catch (error) {
  console.error(`ERROR: ${error.message}`);
  process.exit(1);
}

const knownVariants = new Set(manifestEntries.map(([variant]) => variant));

for (const [profileName, profile] of Object.entries(eas.build || {})) {
  const configuredVariant = profile.env?.APP_VARIANT;
  if (configuredVariant && !knownVariants.has(configuredVariant)) {
    errors.push(`${profileName}: APP_VARIANT '${configuredVariant}' does not exist in the school manifest`);
  }
}

for (const [variant, school] of variants) {
  let expo;
  try {
    expo = resolveExpoConfig(variant);
  } catch (error) {
    errors.push(`${variant}: app.config.js failed to resolve: ${error.message}`);
    continue;
  }

  expectEqual(`${variant}: Expo name`, expo.name, school.name);
  expectEqual(`${variant}: Expo slug`, expo.slug, school.slug);
  expectEqual(`${variant}: Expo scheme`, expo.scheme, school.scheme);
  expectEqual(`${variant}: Android package`, expo.android?.package, school.package);
  expectEqual(`${variant}: iOS bundle identifier`, expo.ios?.bundleIdentifier, school.package);
  expectEqual(`${variant}: extra.schoolId`, Number(expo.extra?.schoolId), Number(school.schoolId));
  expectEqual(`${variant}: extra.appName`, expo.extra?.appName, school.name);
  expectEqual(`${variant}: extra.appVariant`, expo.extra?.appVariant, variant);
  expectEqual(`${variant}: extra.primaryColor`, expo.extra?.primaryColor, school.primaryColor);
  expectEqual(`${variant}: EAS project ID`, expo.extra?.eas?.projectId, school.easProjectId);

  validateProfile(variant, variant, eas.build?.[variant], "preview", "apk");

  const productionProfileName = `production-${variant}`;
  validateProfile(
    variant,
    productionProfileName,
    eas.build?.[productionProfileName],
    "production",
    "app-bundle"
  );

  if (!Object.prototype.hasOwnProperty.call(eas.submit || {}, productionProfileName)) {
    errors.push(`${variant}: EAS submit profile '${productionProfileName}' is missing`);
  }

  if (productionMode) {
    if (
      isPlaceholder(school.easProjectId) ||
      !/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i
        .test(school.easProjectId || "")
    ) {
      errors.push(`${variant}: production build requires a real EAS project ID`);
    }

    for (const [assetName, assetPath] of Object.entries(school.assets || {})) {
      const normalized = String(assetPath).replace(/^\.\//, "");
      if (!assetPath || !fs.existsSync(path.join(root, normalized))) {
        errors.push(`${variant}: production asset '${assetName}' is missing at '${assetPath || "MISSING"}'`);
      }
    }
  } else if (isPlaceholder(school.easProjectId)) {
    warnings.push(`${variant}: EAS project ID is still a placeholder`);
  }
}

for (const warning of warnings) console.warn(`WARNING: ${warning}`);
for (const error of errors) console.error(`ERROR: ${error}`);

if (errors.length) {
  process.exitCode = 1;
} else {
  const mode = productionMode ? "production-ready" : "structurally valid";
  console.log(`Expo/EAS build contract is ${mode}. Variants: ${variants.map(([variant]) => variant).join(", ")}`);
}
