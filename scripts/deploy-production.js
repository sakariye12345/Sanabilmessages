const fs = require("fs");
const path = require("path");
const { formatCommandFailure, runProcess } = require("./process-runner");

const root = path.resolve(__dirname, "..");
const npm = process.platform === "win32" ? "npm.cmd" : "npm";
const npx = process.platform === "win32" ? "npx.cmd" : "npx";
const execute = process.argv.includes("--execute");
const allowDirty = process.argv.includes("--allow-dirty");
const requiredSecrets = [
  "INTERNAL_CRON_SECRET",
  "NOTIFY_WEBHOOK_SECRET",
  "OTP_RATE_LIMIT_PEPPER",
];
const functionDeployments = [
  { name: "request-otp", noVerifyJwt: false },
  { name: "verify-otp", noVerifyJwt: false },
  { name: "bridge-sync", noVerifyJwt: true },
  { name: "sync-parents", noVerifyJwt: true },
  { name: "notify-parents", noVerifyJwt: true },
];

function argumentValue(name) {
  const prefix = `--${name}=`;
  const inline = process.argv.find((argument) => argument.startsWith(prefix));
  if (inline) return inline.slice(prefix.length).trim();

  const index = process.argv.indexOf(`--${name}`);
  return index >= 0 ? String(process.argv[index + 1] || "").trim() : "";
}

function loadEnv() {
  const envPath = path.join(root, ".env");
  if (!fs.existsSync(envPath)) return {};

  const values = {};
  for (const rawLine of fs.readFileSync(envPath, "utf8").split(/\r?\n/)) {
    const line = rawLine.trim();
    if (!line || line.startsWith("#") || !line.includes("=")) continue;
    const [name, ...rest] = line.split("=");
    values[name.trim()] = rest.join("=").trim().replace(/^['"]|['"]$/g, "");
  }
  return values;
}

async function run(command, args, options = {}) {
  const result = await runProcess(command, args, {
    cwd: root,
    env: { ...process.env, CI: "1" },
    capture: Boolean(options.capture),
    timeoutMs: options.timeoutMs || 180_000,
  });

  if (result.status !== 0) {
    throw new Error(formatCommandFailure(command, args, result));
  }

  return options.capture ? result.stdout : "";
}

async function assertCleanReleaseState() {
  if (!allowDirty) {
    const status = await run("git", ["status", "--porcelain"], { capture: true, timeoutMs: 30_000 });
    if (status) {
      throw new Error("Git worktree is dirty. Commit or stash changes before deployment.");
    }
  }

  const branch = await run("git", ["branch", "--show-current"], { capture: true, timeoutMs: 30_000 });
  if (execute && branch !== "main") {
    throw new Error(`Production deployment must run from main, not '${branch || "detached HEAD"}'.`);
  }

  if (execute) {
    const divergence = (await run(
      "git",
      ["rev-list", "--left-right", "--count", "HEAD...@{upstream}"],
      { capture: true, timeoutMs: 30_000 }
    )).split(/\s+/).map(Number);
    if (divergence.length !== 2 || divergence.some((count) => count !== 0)) {
      throw new Error("Local main and its upstream are not identical. Push/pull before deployment.");
    }
  }
}

async function probeSupabase(url, anonKey) {
  const headers = { apikey: anonKey, Authorization: `Bearer ${anonKey}` };
  const probes = [
    ["Auth", `${url}/auth/v1/health`],
    ["REST", `${url}/rest/v1/allowed_parents?select=id&limit=0`],
  ];

  for (const [label, endpoint] of probes) {
    const response = await fetch(endpoint, {
      headers,
      signal: AbortSignal.timeout(15_000),
    });
    if (!response.ok) {
      const body = (await response.text()).slice(0, 400);
      throw new Error(`${label} health failed: HTTP ${response.status} ${body}`);
    }
    console.log(`[release] ${label} health: HTTP ${response.status}`);
  }
}

async function validateSecrets(projectRef) {
  const raw = await run(
    npx,
    [
      "supabase",
      "secrets",
      "list",
      "--project-ref",
      projectRef,
      "--output-format",
      "json",
    ],
    { capture: true, timeoutMs: 120_000 }
  );
  const payload = JSON.parse(raw);
  const available = new Set((payload.secrets || []).map((secret) => secret.name));
  const missing = requiredSecrets.filter((name) => !available.has(name));
  if (missing.length) {
    throw new Error(`Required Edge secrets are missing: ${missing.join(", ")}`);
  }
  console.log(`[release] Required Edge secrets: ${requiredSecrets.length}/${requiredSecrets.length}`);
}

async function validateEasEnvironments(variants) {
  for (const variant of variants.split(",").map((value) => value.trim()).filter(Boolean)) {
    await run(
      process.execPath,
      ["./scripts/validate-eas-environment.js", `--variant=${variant}`],
      { timeoutMs: 300_000 }
    );
  }
}

async function deployFunctions(projectRef) {
  for (const deployment of functionDeployments) {
    const args = [
      "supabase",
      "functions",
      "deploy",
      deployment.name,
      "--project-ref",
      projectRef,
      "--use-api",
    ];
    if (deployment.noVerifyJwt) args.push("--no-verify-jwt");
    await run(npx, args, { timeoutMs: 300_000 });
  }
}

async function main() {
  const localEnv = loadEnv();
  const url = process.env.EXPO_PUBLIC_SUPABASE_URL || localEnv.EXPO_PUBLIC_SUPABASE_URL;
  const anonKey =
    process.env.EXPO_PUBLIC_SUPABASE_ANON_KEY || localEnv.EXPO_PUBLIC_SUPABASE_ANON_KEY;
  if (!url || !anonKey) {
    throw new Error("EXPO_PUBLIC_SUPABASE_URL and EXPO_PUBLIC_SUPABASE_ANON_KEY are required.");
  }

  const derivedProjectRef = new URL(url).hostname.split(".")[0];
  const projectRef = argumentValue("project-ref") || derivedProjectRef;
  const variants = argumentValue("variants") || process.env.PRODUCTION_VARIANTS || "";
  const schoolMatrix = argumentValue("school-matrix") || process.env.SCHOOL_MATRIX_PATH || "";
  const deviceMatrix = argumentValue("device-matrix") || process.env.DEVICE_MATRIX_PATH || "";
  if (projectRef !== derivedProjectRef) {
    throw new Error("--project-ref does not match EXPO_PUBLIC_SUPABASE_URL.");
  }

  if (execute && argumentValue("confirm-project") !== projectRef) {
    throw new Error(
      `Execution requires --confirm-project=${projectRef} to prevent a wrong-project deployment.`
    );
  }

  if (execute && !variants) {
    throw new Error(
      "Execution requires --variants=<comma-separated app variants> so only explicitly approved schools are released."
    );
  }

  console.log(`[release] Mode: ${execute ? "EXECUTE" : "DRY RUN"}`);
  console.log(`[release] Project: ${projectRef}`);
  console.log(`[release] Variants: ${variants || "ALL"}`);
  await assertCleanReleaseState();

  if (variants) await validateEasEnvironments(variants);
  await validateSecrets(projectRef);
  await probeSupabase(url, anonKey);
  const preflightArgs = ["run", "preflight:production", "--", "--skip-export"];
  if (variants) preflightArgs.push(`--variants=${variants}`);
  if (schoolMatrix) preflightArgs.push(`--school-matrix=${schoolMatrix}`);
  if (deviceMatrix) preflightArgs.push(`--device-matrix=${deviceMatrix}`);
  await run(npm, preflightArgs, { timeoutMs: 600_000 });

  if (!execute) {
    console.log("\n[release] DRY RUN PASSED");
    console.log("[release] Planned order: db push, then 5 Edge Function deployments.");
    console.log("[release] Cron activation remains a separate manual gate.");
    return;
  }

  await run(npx, ["supabase", "db", "push", "--linked", "--yes"], { timeoutMs: 300_000 });
  await deployFunctions(projectRef);
  await run(npm, ["run", "smoke:production"], { timeoutMs: 300_000 });

  console.log("\n[release] DEPLOYMENT COMPLETED");
  console.log("[release] Cron was not activated. Run the manual end-to-end pilot test first.");
}

main().catch((error) => {
  console.error(`\n[release] BLOCKED: ${error.message}`);
  process.exit(1);
});
