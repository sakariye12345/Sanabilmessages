const fs = require("fs");
const path = require("path");
const { readSchoolMatrix, isPlaceholder, sqlString } = require("./school-matrix-utils");

const root = path.resolve(__dirname, "..");
const matrixPath = path.join(root, "school_matrix_template.csv");
const outputDir = path.join(root, "generated");
const outputPath = path.join(outputDir, "pilot_seed.generated.sql");

const matrix = readSchoolMatrix(matrixPath);

function buildParentName(row, index) {
  return `${row.school_code || row.app_variant || "SCHOOL"} Parent ${index + 1}`;
}

function numericSchoolPrefix(schoolId) {
  const n = Number(schoolId);
  if (!Number.isFinite(n)) return 90000;
  return n * 10000;
}

const validRows = matrix.filter((row) => {
  const required = [
    "school_name",
    "school_id",
    "app_variant",
    "parents_api_url",
    "parents_api_secret_name",
    "messages_api_url",
    "messages_api_secret_name",
    "otp_server_node_id",
    "test_parent_1",
    "test_parent_2",
    "test_parent_3",
  ];
  return required.every((field) => !isPlaceholder(row[field]));
});

const skipped = matrix.filter((row) => !validRows.includes(row));

const schoolValues = validRows.map((row) => {
  const ci3Url = !isPlaceholder(row.messages_api_url) ? row.messages_api_url : row.parents_api_url;
  return `  (
    ${Number(row.school_id)},
    ${sqlString(row.school_name)},
    ${sqlString(ci3Url)},
    ${sqlString(row.parents_api_url)},
    ${sqlString(row.messages_api_url)},
    ${sqlString(row.parents_api_secret_name)},
    ${sqlString(row.messages_api_secret_name)},
    TRUE,
    ${sqlString(row.otp_server_node_id)},
    ${sqlString(row.wa_session_status || "DISCONNECTED")},
    ${Number(row.otp_cooldown_seconds || 30)},
    ${Number(row.otp_daily_cap || 250)}
  )`;
});

const parentValues = validRows.flatMap((row) => {
  const base = numericSchoolPrefix(row.school_id);
  return ["test_parent_1", "test_parent_2", "test_parent_3"].map((field, index) => `  (
    ${Number(row.school_id)},
    ${base + index + 1},
    ${sqlString(buildParentName(row, index))},
    ${sqlString(row[field])},
    TRUE
  )`);
});

if (validRows.length === 0) {
  const diagnosticSql = `-- =========================================================
-- AUTO-GENERATED PILOT SEED
-- Source: school_matrix_template.csv
-- Generated: ${new Date().toISOString()}
-- =========================================================

-- No executable seed was generated because every row still contains
-- placeholders or fake test data. Complete and validate the matrix first.
SELECT 'NO_PRODUCTION_READY_SCHOOLS' AS seed_status;
`;

  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(outputPath, diagnosticSql, "utf8");
  console.log(`Generated diagnostic seed SQL: ${outputPath}`);
  console.warn(`Skipped rows still containing placeholders: ${skipped.map((row) => row.school_code || row.app_variant).join(", ")}`);
  process.exit(0);
}

const sql = `-- =========================================================
-- AUTO-GENERATED PILOT SEED
-- Source: school_matrix_template.csv
-- Generated: ${new Date().toISOString()}
-- =========================================================

-- Skipped rows with placeholders: ${skipped.map((row) => row.school_code || row.app_variant || "unknown").join(", ") || "none"}

INSERT INTO public.schools (
  id,
  name,
  ci3_url,
  parents_api_url,
  messages_api_url,
  parents_api_secret_name,
  messages_api_secret_name,
  is_active,
  server_node_id,
  wa_session_status,
  otp_cooldown_seconds,
  otp_daily_cap
)
VALUES
${schoolValues.join(",\n")}
ON CONFLICT (id) DO UPDATE
SET
  name = EXCLUDED.name,
  ci3_url = EXCLUDED.ci3_url,
  parents_api_url = EXCLUDED.parents_api_url,
  messages_api_url = EXCLUDED.messages_api_url,
  parents_api_secret_name = EXCLUDED.parents_api_secret_name,
  messages_api_secret_name = EXCLUDED.messages_api_secret_name,
  is_active = EXCLUDED.is_active,
  server_node_id = EXCLUDED.server_node_id,
  wa_session_status = EXCLUDED.wa_session_status,
  otp_cooldown_seconds = EXCLUDED.otp_cooldown_seconds,
  otp_daily_cap = EXCLUDED.otp_daily_cap;

INSERT INTO public.allowed_parents (
  school_id,
  parent_id,
  parent_name,
  phone_number,
  is_active
)
VALUES
${parentValues.join(",\n")}
ON CONFLICT DO NOTHING;

SELECT id, name, parents_api_url, messages_api_url,
       parents_api_secret_name, messages_api_secret_name,
       server_node_id, wa_session_status
FROM public.schools
WHERE id IN (${validRows.map((row) => Number(row.school_id)).join(", ")})
ORDER BY id;

SELECT school_id, parent_id, parent_name, phone_number, is_active
FROM public.allowed_parents
WHERE school_id IN (${validRows.map((row) => Number(row.school_id)).join(", ")})
ORDER BY school_id, parent_id;`;

fs.mkdirSync(outputDir, { recursive: true });
fs.writeFileSync(outputPath, sql, "utf8");

console.log(`Generated pilot seed SQL: ${outputPath}`);
console.log(`Included schools: ${validRows.map((row) => row.school_code || row.app_variant).join(", ") || "none"}`);
if (skipped.length) {
  console.warn(`Skipped rows still containing placeholders: ${skipped.map((row) => row.school_code || row.app_variant).join(", ")}`);
}
