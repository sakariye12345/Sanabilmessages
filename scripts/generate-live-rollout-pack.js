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
const deviceByVariant = new Map(devices.map((row) => [row.app_variant, row]));
const outputDir = path.join(root, "generated");
const outputPath = path.join(outputDir, "live_rollout_checklist.generated.md");

const schoolSections = schools.map((school) => {
  const device = deviceByVariant.get(school.app_variant);
  return `## ${school.school_code} / ${school.app_variant}

- School ID: \`${school.school_id}\`
- App Name: \`${school.app_display_name}\`
- Package: \`${school.android_package}\`
- Parents Source: \`${school.parents_api_url}\`
- Messages Source: \`${school.messages_api_url}\`
- OTP Node: \`${school.otp_server_node_id}\`
- OTP Status Target: \`${school.wa_session_status}\`
- Test Parents:
  - \`${school.test_parent_1}\`
  - \`${school.test_parent_2}\`
  - \`${school.test_parent_3}\`
${device ? `- Device: \`${device.device_label}\`` : "- Device: `UNASSIGNED`"}

### Build Command
\`\`\`powershell
npx eas-cli build --platform android --profile ${school.app_variant}
\`\`\`

### OTP Session Activation
1. Open OTP dashboard for your VPS node
2. Start session for school ID \`${school.school_id}\`
3. Scan QR if status is not CONNECTED
4. Confirm status becomes CONNECTED

### Pilot Login Check
1. Install APK on ${device ? device.device_label : "assigned device"}
2. Login with \`${device ? device.test_parent_phone : school.test_parent_1}\`
3. Confirm WhatsApp OTP arrives
4. Verify login
5. Confirm inbox opens

### Message Routing Check
1. Send a message from \`${school.school_name}\` message source
2. Confirm message lands in Supabase
3. Confirm it appears on ${device ? device.device_label : "the target device"}
4. Confirm it does not appear on the other devices
`;
});

const md = `# Live Rollout Checklist

Generated: ${new Date().toISOString()}

## Global Preflight

Run these first:

\`\`\`powershell
npm ci
npm ci --prefix whatsapp-service --omit=optional
npm run preflight:production
npm run schools:seed:generate
npm run schools:runbook:generate
\`\`\`

Do not continue unless \`npm run preflight:production\` passes. It rejects
placeholder school data, disconnected WhatsApp sessions, unready devices,
misaligned migrations, inactive Edge Functions, and Supabase REST failures such
as \`402 exceed_db_size_quota\`.

## Supabase Load

Review:

- [generated/pilot_seed.generated.sql](/C:/Users/hp/SanabilMessages/generated/pilot_seed.generated.sql)

Then open the linked project's **Supabase SQL Editor**, paste the reviewed SQL,
and run it there. The current Supabase CLI does not provide a
\`supabase db query --linked\` command.

Before executing the seed:

1. Replace every \`.example\` URL and placeholder phone with real pilot data.
2. Confirm every \`school_id\` matches the corresponding app variant.
3. Keep CI3/API credentials in Supabase Vault or Edge Function secrets, not in
   the generated SQL or Git.

## School-by-School Execution

${schoolSections.join("\n")}

## Final Cross-School Isolation Test

1. Send School B message and confirm only Device-2 receives it
2. Send School C message and confirm only Device-3 receives it
3. Send School D message and confirm only Device-4 receives it
4. Attempt login with a parent from one school inside another school's app
5. Confirm there is no cross-school leakage
`;

fs.mkdirSync(outputDir, { recursive: true });
fs.writeFileSync(outputPath, md, "utf8");
console.log(`Generated live rollout checklist: ${outputPath}`);
