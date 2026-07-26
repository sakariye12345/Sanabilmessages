const fs = require("fs");
const path = require("path");
const { spawnSync } = require("child_process");

const root = path.resolve(__dirname, "..");
const codeOnly = process.argv.includes("--code-only");
const skipExport = process.argv.includes("--skip-export");
const npm = process.platform === "win32" ? "npm.cmd" : "npm";
const npx = process.platform === "win32" ? "npx.cmd" : "npx";
const node = process.execPath;
const failures = [];

function run(label, command, args) {
  console.log(`\n[preflight] ${label}`);
  const result = spawnSync(command, args, {
    cwd: root,
    env: { ...process.env, CI: "1" },
    shell:
      process.platform === "win32" &&
      /\.(cmd|bat)$/i.test(command),
    stdio: "inherit",
  });
  if (result.error) {
    console.error(`${label} could not start: ${result.error.message}`);
  }
  if (result.status !== 0) {
    failures.push(label);
  }
}

function loadEnv() {
  const envPath = path.join(root, ".env");
  if (!fs.existsSync(envPath)) return {};
  const values = {};
  for (const rawLine of fs.readFileSync(envPath, "utf8").split(/\r?\n/)) {
    const line = rawLine.trim();
    if (!line || line.startsWith("#") || !line.includes("=")) continue;
    const [name, ...rest] = line.split("=");
    values[name] = rest.join("=").trim().replace(/^['"]|['"]$/g, "");
  }
  return values;
}

async function probeSupabase() {
  console.log("\n[preflight] Live Supabase REST");
  const localEnv = loadEnv();
  const url =
    process.env.EXPO_PUBLIC_SUPABASE_URL ||
    localEnv.EXPO_PUBLIC_SUPABASE_URL;
  const key =
    process.env.EXPO_PUBLIC_SUPABASE_ANON_KEY ||
    localEnv.EXPO_PUBLIC_SUPABASE_ANON_KEY;

  if (!url || !key) {
    failures.push("Live Supabase REST");
    console.error("Missing EXPO_PUBLIC_SUPABASE_URL or anon key.");
    return;
  }

  try {
    const response = await fetch(`${url}/rest/v1/schools?select=id&limit=1`, {
      headers: { apikey: key, Authorization: `Bearer ${key}` },
    });
    if (!response.ok) {
      const body = await response.text();
      console.error(`Supabase REST failed: HTTP ${response.status} ${body}`);
      failures.push("Live Supabase REST");
      return;
    }
    console.log(`Supabase REST passed: HTTP ${response.status}`);
  } catch (error) {
    console.error(`Supabase REST failed: ${error.message}`);
    failures.push("Live Supabase REST");
  }
}

async function main() {
  run("Secret scan", npm, ["run", "secrets:scan"]);
  run("TypeScript", npm, ["run", "typecheck"]);
  run("Expo Doctor", npx, ["expo-doctor"]);

  const productionArg = codeOnly ? [] : ["--production"];
  run("School manifest", node, [
    "./scripts/validate-school-manifest.js",
    ...productionArg,
  ]);
  run("School matrix", node, [
    "./scripts/validate-school-matrix.js",
    ...productionArg,
  ]);
  run("Device matrix", node, [
    "./scripts/validate-device-matrix.js",
    ...productionArg,
  ]);

  run("WhatsApp service syntax", npm, [
    "--prefix",
    "whatsapp-service",
    "run",
    "check",
  ]);
  run("WhatsApp production dependency audit", npm, [
    "--prefix",
    "whatsapp-service",
    "run",
    "audit:production",
  ]);

  run("Edge Function type checks", npx, [
    "--yes",
    "deno-bin",
    "check",
    "supabase/functions/bridge-sync/index.ts",
    "supabase/functions/notify-parents/index.ts",
    "supabase/functions/request-otp/index.ts",
    "supabase/functions/sync-parents/index.ts",
    "supabase/functions/verify-otp/index.ts",
  ]);

  if (!skipExport) {
    run("Android production export", npx, [
      "expo",
      "export",
      "--platform",
      "android",
      "--clear",
    ]);
  }

  if (!codeOnly) {
    run("Supabase migration dry-run", npx, [
      "supabase",
      "db",
      "push",
      "--linked",
      "--dry-run",
    ]);

    const localEnv = loadEnv();
    const url =
      process.env.EXPO_PUBLIC_SUPABASE_URL ||
      localEnv.EXPO_PUBLIC_SUPABASE_URL ||
      "";
    const projectRef = new URL(url).hostname.split(".")[0];
    run("Supabase Edge Functions", npx, [
      "supabase",
      "functions",
      "list",
      "--project-ref",
      projectRef,
    ]);
    await probeSupabase();
  }

  if (failures.length) {
    console.error(`\nPRODUCTION PREFLIGHT FAILED (${failures.length})`);
    for (const failure of failures) {
      console.error(`- ${failure}`);
    }
    process.exit(1);
  }

  console.log("\nPRODUCTION PREFLIGHT PASSED");
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
