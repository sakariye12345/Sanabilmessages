const fs = require("fs");
const path = require("path");
const { readSchoolMatrix } = require("./school-matrix-utils");

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
const schools = readSchoolMatrix(path.join(root, "school_matrix_template.csv"));
const devices = readSimpleCsv(path.join(root, "pilot_device_test_matrix.csv"));

const schoolByVariant = new Map(schools.map((row) => [row.app_variant, row]));
const outputDir = path.join(root, "generated");
const outputPath = path.join(outputDir, "pilot_execution_runbook.generated.md");

const sections = devices.map((device) => {
  const school = schoolByVariant.get(device.app_variant);
  if (!school) {
    return `## ${device.device_label}\n\nMissing school matrix row for variant \`${device.app_variant}\`.\n`;
  }

  return `## ${device.device_label}

- App Variant: \`${device.app_variant}\`
- Expected School ID: \`${device.expected_school_id}\`
- Expected App Name: \`${device.expected_app_name}\`
- Test Parent Phone: \`${device.test_parent_phone}\`
- Parents Source: \`${school.parents_api_url}\`
- Messages Source: \`${school.messages_api_url}\`
- OTP Node: \`${school.otp_server_node_id}\`
- OTP Session Status: \`${school.wa_session_status}\`

### Build
\`\`\`powershell
npx eas-cli build --platform android --profile ${device.app_variant}
\`\`\`

### Install Checklist
1. Install APK on ${device.device_label}
2. Confirm app name is \`${device.expected_app_name}\`
3. Confirm login number used is \`${device.test_parent_phone}\`
4. Request WhatsApp OTP
5. Confirm OTP arrives from the correct school session
6. Verify login
7. Confirm inbox opens
8. Confirm only school ${device.phone_owner_school} messages appear

### Message Routing Test
1. Send a message from \`${device.message_source_school}\` source
2. Confirm message lands in Supabase
3. Confirm \`${device.device_label}\` receives the message
4. Confirm the other devices do not receive it
`;
});

const markdown = `# Pilot Execution Runbook

Generated: ${new Date().toISOString()}

This runbook was generated from:

- \`school_matrix_template.csv\`
- \`pilot_device_test_matrix.csv\`

Use it during the live multi-school pilot.

${sections.join("\n")}
`;

fs.mkdirSync(outputDir, { recursive: true });
fs.writeFileSync(outputPath, markdown, "utf8");

console.log(`Generated pilot execution runbook: ${outputPath}`);
