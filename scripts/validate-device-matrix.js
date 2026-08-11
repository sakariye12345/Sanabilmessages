const fs = require("fs");
const path = require("path");
const {
  filterSelectedVariants,
  readSchoolMatrix,
  isPlaceholder,
  resolveInputPath,
  selectedVariants,
} = require("./school-matrix-utils");

function readSimpleCsv(csvPath) {
  const content = fs.readFileSync(csvPath, "utf8").trim();
  const [headerLine, ...lines] = content.split(/\r?\n/);
  const headers = headerLine.split(",");
  return lines
    .filter(Boolean)
    .map((line) => {
      const cols = line.split(",");
      const row = {};
      headers.forEach((header, index) => {
        row[header] = (cols[index] || "").trim();
      });
      return row;
    });
}

const root = path.resolve(__dirname, "..");
const productionMode = process.argv.includes("--production");
const schoolMatrixPath = resolveInputPath(
  root,
  "school-matrix",
  "SCHOOL_MATRIX_PATH",
  "school_matrix_template.csv"
);
const deviceMatrixPath = resolveInputPath(
  root,
  "device-matrix",
  "DEVICE_MATRIX_PATH",
  "pilot_device_test_matrix.csv"
);
const selected = selectedVariants();
let schoolMatrix;
let deviceMatrix;

try {
  schoolMatrix = filterSelectedVariants(
    readSchoolMatrix(schoolMatrixPath),
    (row) => row.app_variant,
    selected,
    "School matrix"
  );
  deviceMatrix = filterSelectedVariants(
    readSimpleCsv(deviceMatrixPath),
    (row) => row.app_variant,
    selected,
    "Pilot device matrix"
  );
} catch (error) {
  console.error(`ERROR: ${error.message}`);
  process.exit(1);
}
const manifest = JSON.parse(fs.readFileSync(path.join(root, "config", "schools.manifest.json"), "utf8"));

const schoolByVariant = new Map(schoolMatrix.map((row) => [row.app_variant, row]));
const errors = [];
const warnings = [];
const seenDevices = new Set();
const seenVariants = new Set();

for (const row of deviceMatrix) {
  const label = row.device_label || "(unknown device)";
  const school = schoolByVariant.get(row.app_variant);
  const variantConfig = manifest.schools?.[row.app_variant];

  if (!school) {
    errors.push(`${label}: app_variant '${row.app_variant}' not found in school matrix`);
    continue;
  }

  if (!variantConfig) {
    errors.push(`${label}: app_variant '${row.app_variant}' not found in manifest`);
    continue;
  }

  if (seenDevices.has(label)) {
    errors.push(`${label}: duplicate device_label`);
  }
  seenDevices.add(label);

  if (seenVariants.has(row.app_variant)) {
    errors.push(`${label}: duplicate app_variant '${row.app_variant}' in device matrix`);
  }
  seenVariants.add(row.app_variant);

  if (String(row.expected_school_id) !== String(school.school_id)) {
    errors.push(`${label}: expected_school_id '${row.expected_school_id}' does not match school matrix school_id '${school.school_id}'`);
  }

  if (row.expected_app_name && row.expected_app_name !== variantConfig.name) {
    errors.push(`${label}: expected_app_name '${row.expected_app_name}' does not match manifest name '${variantConfig.name}'`);
  }

  const testParents = [school.test_parent_1, school.test_parent_2, school.test_parent_3];
  if (!testParents.includes(row.test_parent_phone)) {
    const target = productionMode ? errors : warnings;
    target.push(`${label}: test_parent_phone '${row.test_parent_phone}' is not one of the matrix test parents for variant '${row.app_variant}'`);
  }

  if (isPlaceholder(row.test_parent_phone) || !/^\d{10,15}$/.test(row.test_parent_phone || "")) {
    const target = productionMode ? errors : warnings;
    target.push(`${label}: test_parent_phone must be a real normalized phone`);
  }

  if (productionMode && !["READY", "PASS"].includes(String(row.status).toUpperCase())) {
    errors.push(`${label}: status must be READY or PASS before production pilot`);
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
  console.log(`Pilot device matrix is ${mode}. Devices: ${deviceMatrix.length}`);
}
