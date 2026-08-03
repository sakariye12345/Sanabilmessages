const fs = require("fs");
const path = require("path");
const { spawnSync } = require("child_process");

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

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    cwd: root,
    env: { ...process.env, CI: "1" },
    encoding: options.capture ? "utf8" : undefined,
    shell: process.platform === "win32" && /\.(cmd|bat)$/i.test(command),
    stdio: options.capture ? ["ignore", "pipe", "pipe"] : "inherit",
  });

  if (result.error) throw result.error;
  if (result.status !== 0) {
    const detail = options.capture
      ? `${result.stdout || ""}\n${result.stderr || ""}`.trim()
      : "";
    throw new Error(
      `Command failed: ${command} ${args.join(" ")}${detail ? `\n${detail}` : ""}`
    );
  }

  return options.capture ? String(result.stdout || "").trim() : "";
}

function assertCleanReleaseState() {
  if (!allowDirty) {
    const status = run("git", ["status", "--porcelain"], { capture: true });
    if (status) {
      throw new Error("Git worktree is dirty. Commit or stash changes before deployment.");
    }
  }

  const branch = run("git", ["branch", "--show-current"], { capture: true });
  if (execute && branch !== "main") {
    throw new Error(`Production deployment must run from main, not '${branch || "detached HEAD"}'.`);
  }

  if (execute) {
    const divergence = run(
      "git",
      ["rev-list", "--left-right", "--count", "HEAD...@{upstream}"],
      { capture: true }
    ).split(/\s+/).map(Number);
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
    const response = await fetch(endpoint, { headers });
    if (!response.ok) {
      const body = (await response.text()).slice(0, 400);
      throw new Error(`${label} health failed: HTTP ${response.status} ${body}`);
    }
    console.log(`[release] ${label} health: HTTP ${response.status}`);
  }
}

function validateSecrets(projectRef) {
  const raw = run(
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
    { capture: true }
  );
  const payload = JSON.parse(raw);
  const available = new Set((payload.secrets || []).map((secret) => secret.name));
  const missing = requiredSecrets.filter((name) => !available.has(name));
  if (missing.length) {
    throw new Error(`Required Edge secrets are missing: ${missing.join(", ")}`);
  }
  console.log(`[release] Required Edge secrets: ${requiredSecrets.length}/${requiredSecrets.length}`);
}

function deployFunctions(projectRef) {
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
    run(npx, args);
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
  if (projectRef !== derivedProjectRef) {
    throw new Error("--project-ref does not match EXPO_PUBLIC_SUPABASE_URL.");
  }

  if (execute && argumentValue("confirm-project") !== projectRef) {
    throw new Error(
      `Execution requires --confirm-project=${projectRef} to prevent a wrong-project deployment.`
    );
  }

  console.log(`[release] Mode: ${execute ? "EXECUTE" : "DRY RUN"}`);
  console.log(`[release] Project: ${projectRef}`);
  assertCleanReleaseState();

  validateSecrets(projectRef);
  await probeSupabase(url, anonKey);
  run(npm, ["run", "preflight:production", "--", "--skip-export"]);

  if (!execute) {
    console.log("\n[release] DRY RUN PASSED");
    console.log("[release] Planned order: db push, then 5 Edge Function deployments.");
    console.log("[release] Cron activation remains a separate manual gate.");
    return;
  }

  run(npx, ["supabase", "db", "push", "--linked", "--yes"]);
  deployFunctions(projectRef);
  run(npm, ["run", "smoke:production"]);

  console.log("\n[release] DEPLOYMENT COMPLETED");
  console.log("[release] Cron was not activated. Run the manual end-to-end pilot test first.");
}

main().catch((error) => {
  console.error(`\n[release] BLOCKED: ${error.message}`);
  process.exit(1);
});
