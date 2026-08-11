const path = require("path");
const fs = require("fs");
const {
  readSchoolMatrix,
  filterSelectedVariants,
  isPlaceholder,
  isValidHttpsUrl,
  resolveInputPath,
  selectedVariants,
} = require("./school-matrix-utils");

const root = path.resolve(__dirname, "..");
const matrixPath = resolveInputPath(
  root,
  "school-matrix",
  "SCHOOL_MATRIX_PATH",
  "school_matrix_template.csv"
);
const manifestPath = path.join(root, "config", "schools.manifest.json");
const productionMode = process.argv.includes("--production");

const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const selected = selectedVariants();
let matrix;

try {
  matrix = filterSelectedVariants(
    readSchoolMatrix(matrixPath),
    (row) => row.app_variant,
    selected,
    "School matrix"
  );
} catch (error) {
  console.error(`ERROR: ${error.message}`);
  process.exit(1);
}

const seenSchoolIds = new Set();
const seenVariants = new Set();
const errors = [];
const warnings = [];

for (const row of matrix) {
  const label = row.school_code || row.school_name || "(unknown school)";
  const variant = row.app_variant;
  const schoolId = row.school_id;

  if (!variant) {
    errors.push(`${label}: app_variant is required`);
    continue;
  }

  const variantConfig = manifest.schools?.[variant];
  if (!variantConfig) {
    errors.push(`${label}: app_variant '${variant}' does not exist in config/schools.manifest.json`);
    continue;
  }

  if (seenVariants.has(variant)) {
    errors.push(`${label}: duplicate app_variant '${variant}' in school matrix`);
  }
  seenVariants.add(variant);

  if (seenSchoolIds.has(String(schoolId))) {
    errors.push(`${label}: duplicate school_id '${schoolId}' in school matrix`);
  }
  seenSchoolIds.add(String(schoolId));

  if (String(variantConfig.schoolId) !== String(schoolId)) {
    errors.push(`${label}: school_id '${schoolId}' does not match manifest schoolId '${variantConfig.schoolId}'`);
  }

  if (row.android_package && row.android_package !== variantConfig.package) {
    errors.push(`${label}: android_package '${row.android_package}' does not match manifest package '${variantConfig.package}'`);
  }

  if (row.app_display_name && row.app_display_name !== variantConfig.name) {
    const target = productionMode ? errors : warnings;
    target.push(`${label}: app_display_name '${row.app_display_name}' differs from manifest name '${variantConfig.name}'`);
  }

  for (const required of [
    "school_name",
    "app_display_name",
    "android_package",
    "support_phone",
    "website",
    "parents_api_url",
    "parents_api_secret_name",
    "messages_api_url",
    "messages_api_secret_name",
    "otp_server_node_id",
  ]) {
    if (isPlaceholder(row[required])) {
      const target = productionMode ? errors : warnings;
      target.push(`${label}: field '${required}' still looks like a placeholder`);
    }
  }

  for (const urlField of ["website", "parents_api_url", "messages_api_url"]) {
    if (!isValidHttpsUrl(row[urlField])) {
      const target = productionMode ? errors : warnings;
      target.push(`${label}: ${urlField} must be a real HTTPS URL`);
    }
  }

  for (const secretField of ["parents_api_secret_name", "messages_api_secret_name"]) {
    if (!/^[A-Za-z0-9_-]{3,120}$/.test(row[secretField] || "")) {
      const target = productionMode ? errors : warnings;
      target.push(`${label}: ${secretField} must be a Vault secret name, never a token value`);
    }
  }

  for (const parentField of ["test_parent_1", "test_parent_2", "test_parent_3"]) {
    const phone = row[parentField] || "";
    if (isPlaceholder(phone) || !/^\d{10,15}$/.test(phone)) {
      const target = productionMode ? errors : warnings;
      target.push(`${label}: ${parentField} must be a real normalized test phone`);
    }
  }

  if (productionMode && String(row.wa_session_status).toUpperCase() !== "CONNECTED") {
    errors.push(`${label}: WhatsApp session must be CONNECTED before production pilot`);
  }
}

if (errors.length) {
  for (const error of errors) {
    console.error(`ERROR: ${error}`);
  }
}

for (const warning of warnings) {
  console.warn(`WARNING: ${warning}`);
}

if (errors.length) {
  process.exitCode = 1;
} else {
  const mode = productionMode ? "production-ready" : "structurally valid";
  console.log(`School matrix is ${mode}. Rows: ${matrix.length}`);
}
