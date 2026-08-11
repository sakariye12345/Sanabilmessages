const fs = require("fs");
const path = require("path");
const { formatCommandFailure, runProcess } = require("./process-runner");

const root = path.resolve(__dirname, "..");
const codeOnly = process.argv.includes("--code-only");
const skipExport = process.argv.includes("--skip-export");
const npm = process.platform === "win32" ? "npm.cmd" : "npm";
const npx = process.platform === "win32" ? "npx.cmd" : "npx";
const node = process.execPath;
const failures = [];
const selectedVariants = argumentValue("variants");
const schoolMatrix = argumentValue("school-matrix");
const deviceMatrix = argumentValue("device-matrix");

function argumentValue(name) {
  const prefix = `--${name}=`;
  const inline = process.argv.find((argument) => argument.startsWith(prefix));
  if (inline) return inline.slice(prefix.length).trim();

  const index = process.argv.indexOf(`--${name}`);
  return index >= 0 ? String(process.argv[index + 1] || "").trim() : "";
}

async function run(label, command, args, timeoutMs = 120_000) {
  console.log(`\n[preflight] ${label}`);
  let result;
  try {
    result = await runProcess(command, args, {
    cwd: root,
    env: { ...process.env, CI: "1" },
      timeoutMs,
    });
  } catch (error) {
    console.error(`${label} could not start: ${error.message}`);
    failures.push(label);
    return;
  }

  if (result.status !== 0) {
    if (result.timedOut) console.error(formatCommandFailure(command, args, result));
    failures.push(label);
  }
}

async function fetchWithTimeout(endpoint, options = {}, timeoutMs = 15_000) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    return await fetch(endpoint, { ...options, signal: controller.signal });
  } finally {
    clearTimeout(timer);
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
    const headers = { apikey: key, Authorization: `Bearer ${key}` };
    // The OpenAPI root may be service-role-only. A zero-row table request
    // validates the anon key and PostgREST availability without reading data.
    const response = await fetchWithTimeout(`${url}/rest/v1/allowed_parents?select=id&limit=0`, {
      headers,
    });
    if (!response.ok) {
      const body = await response.text();
      console.error(`Supabase REST failed: HTTP ${response.status} ${body}`);
      failures.push("Live Supabase REST");
      return;
    }
    console.log(`Supabase REST passed: HTTP ${response.status}`);

    const sensitiveProbes = [
      ["schools credentials", "schools?select=id,ci3_token,parents_api_token,messages_api_token&limit=1"],
      ["OTP queue", "otp_queue?select=id,code&limit=1"],
      ["OTP logs", "otp_logs?select=id,message&limit=1"],
    ];

    for (const [label, path] of sensitiveProbes) {
      const probe = await fetchWithTimeout(`${url}/rest/v1/${path}`, {
        headers,
      });

      if (probe.status === 401 || probe.status === 403) {
        console.log(`Sensitive REST probe blocked: ${label} (HTTP ${probe.status})`);
        continue;
      }

      if (!probe.ok) {
        console.error(`Sensitive REST probe failed unexpectedly: ${label} (HTTP ${probe.status})`);
        failures.push("Supabase REST security");
        continue;
      }

      const rows = await probe.json();
      if (!Array.isArray(rows) || rows.length !== 0) {
        console.error(`Sensitive REST data is exposed to anon: ${label}`);
        failures.push("Supabase REST security");
      } else {
        console.log(`Sensitive REST probe returned no rows: ${label}`);
      }
    }
  } catch (error) {
    console.error(`Supabase REST failed: ${error.message}`);
    failures.push("Live Supabase REST");
  }
}

async function main() {
  await run("Secret scan", npm, ["run", "secrets:scan"], 60_000);
  await run("TypeScript", npm, ["run", "typecheck"], 120_000);
  await run("Expo Doctor", npx, ["expo-doctor"], 180_000);

  const variantArg = selectedVariants ? [`--variants=${selectedVariants}`] : [];
  const schoolMatrixArg = schoolMatrix ? [`--school-matrix=${schoolMatrix}`] : [];
  const deviceMatrixArg = deviceMatrix ? [`--device-matrix=${deviceMatrix}`] : [];
  await run("School manifest structure", node, [
    "./scripts/validate-school-manifest.js",
  ], 30_000);
  await run("School matrix structure", node, [
    "./scripts/validate-school-matrix.js",
  ], 30_000);
  await run("Device matrix structure", node, [
    "./scripts/validate-device-matrix.js",
  ], 30_000);

  if (!codeOnly) {
    await run("Approved school manifest", node, [
      "./scripts/validate-school-manifest.js",
      "--production",
      ...variantArg,
    ], 30_000);
    await run("Approved school matrix", node, [
      "./scripts/validate-school-matrix.js",
      "--production",
      ...variantArg,
      ...schoolMatrixArg,
    ], 30_000);
    await run("Approved device matrix", node, [
      "./scripts/validate-device-matrix.js",
      "--production",
      ...variantArg,
      ...schoolMatrixArg,
      ...deviceMatrixArg,
    ], 30_000);
  }

  await run("WhatsApp service syntax", npm, [
    "--prefix",
    "whatsapp-service",
    "run",
    "check",
  ], 60_000);
  await run("WhatsApp production dependency audit", npm, [
    "--prefix",
    "whatsapp-service",
    "run",
    "audit:production",
  ], 120_000);

  await run("Edge Function type checks", npx, [
    "--yes",
    "deno-bin",
    "check",
    "supabase/functions/bridge-sync/index.ts",
    "supabase/functions/notify-parents/index.ts",
    "supabase/functions/request-otp/index.ts",
    "supabase/functions/sync-parents/index.ts",
    "supabase/functions/verify-otp/index.ts",
  ], 240_000);

  if (!skipExport) {
    await run("Android production export", npx, [
      "expo",
      "export",
      "--platform",
      "android",
      "--clear",
    ], 600_000);
  }

  if (!codeOnly) {
    await run("Supabase migration dry-run", npx, [
      "supabase",
      "db",
      "push",
      "--linked",
      "--dry-run",
    ], 180_000);

    const localEnv = loadEnv();
    const url =
      process.env.EXPO_PUBLIC_SUPABASE_URL ||
      localEnv.EXPO_PUBLIC_SUPABASE_URL ||
      "";
    const projectRef = new URL(url).hostname.split(".")[0];
    await run("Supabase Edge Functions", npx, [
      "supabase",
      "functions",
      "list",
      "--project-ref",
      projectRef,
    ], 120_000);
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
