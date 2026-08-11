const fs = require("fs");
const path = require("path");
const { formatCommandFailure, runProcess } = require("./process-runner");

const root = path.resolve(__dirname, "..");
const npx = process.platform === "win32" ? "npx.cmd" : "npx";
const failures = [];

const expectedFunctions = new Map([
  ["request-otp", true],
  ["verify-otp", true],
  ["bridge-sync", false],
  ["sync-parents", false],
  ["notify-parents", false],
]);

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

async function capture(command, args) {
  const result = await runProcess(command, args, {
    cwd: root,
    env: { ...process.env, CI: "1" },
    capture: true,
    timeoutMs: 120_000,
  });
  if (result.status !== 0) {
    throw new Error(formatCommandFailure(command, args, result));
  }
  return result.stdout;
}

function pass(label, detail = "") {
  console.log(`[smoke] PASS: ${label}${detail ? ` (${detail})` : ""}`);
}

function fail(label, detail) {
  failures.push(label);
  console.error(`[smoke] FAIL: ${label}: ${detail}`);
}

async function expectHttp(label, endpoint, options, allowedStatuses) {
  try {
    const response = await fetch(endpoint, {
      ...options,
      signal: AbortSignal.timeout(15_000),
    });
    const body = await response.text();
    if (!allowedStatuses.includes(response.status)) {
      fail(label, `HTTP ${response.status} ${body.slice(0, 300)}`);
      return { response, body };
    }
    pass(label, `HTTP ${response.status}`);
    return { response, body };
  } catch (error) {
    fail(label, error.message);
    return null;
  }
}

async function validateMigrations() {
  try {
    const raw = await capture(npx, [
      "supabase",
      "migration",
      "list",
      "--linked",
      "--output-format",
      "json",
    ]);
    const payload = JSON.parse(raw);
    const migrations = payload.migrations || [];
    const pending = migrations.filter(
      (migration) => !migration.local || !migration.remote || migration.local !== migration.remote
    );
    if (!migrations.length) {
      fail("Migration history", "No migration records returned.");
    } else if (pending.length) {
      fail(
        "Migration history",
        `Unaligned migrations: ${pending.map((item) => item.local || item.remote).join(", ")}`
      );
    } else {
      pass("Migration history", `${migrations.length} aligned`);
    }
  } catch (error) {
    fail("Migration history", error.message);
  }
}

async function validateFunctions(projectRef) {
  try {
    const raw = await capture(npx, [
      "supabase",
      "functions",
      "list",
      "--project-ref",
      projectRef,
      "--output-format",
      "json",
    ]);
    const payload = JSON.parse(raw);
    const functions = payload.functions || [];

    for (const [name, expectedVerifyJwt] of expectedFunctions) {
      const deployed = functions.find((item) => item.slug === name);
      if (!deployed) {
        fail(`Function ${name}`, "Not deployed.");
        continue;
      }
      if (deployed.status !== "ACTIVE") {
        fail(`Function ${name}`, `Status is ${deployed.status}.`);
        continue;
      }
      if (Boolean(deployed.verify_jwt) !== expectedVerifyJwt) {
        fail(
          `Function ${name}`,
          `verify_jwt is ${deployed.verify_jwt}; expected ${expectedVerifyJwt}.`
        );
        continue;
      }
      pass(`Function ${name}`, `v${deployed.version}, verify_jwt=${expectedVerifyJwt}`);
    }
  } catch (error) {
    fail("Edge Function inventory", error.message);
  }
}

async function validateRestSecurity(url, headers) {
  await expectHttp(
    "REST availability",
    `${url}/rest/v1/allowed_parents?select=id&limit=0`,
    { headers },
    [200]
  );

  const sensitiveProbes = [
    ["School credentials", "schools?select=id,ci3_token,parents_api_token,messages_api_token&limit=1"],
    ["OTP queue codes", "otp_queue?select=id,code&limit=1"],
    ["OTP operational logs", "otp_logs?select=id,message&limit=1"],
    ["Device push tokens", "user_devices?select=id,fcm_token&limit=1"],
  ];

  for (const [label, query] of sensitiveProbes) {
    const result = await expectHttp(
      `Anon isolation: ${label}`,
      `${url}/rest/v1/${query}`,
      { headers },
      [200, 401, 403]
    );
    if (!result || result.response.status !== 200) continue;

    try {
      const rows = JSON.parse(result.body);
      if (!Array.isArray(rows) || rows.length !== 0) {
        fail(`Anon isolation: ${label}`, "Sensitive rows are visible to anon.");
      }
    } catch {
      fail(`Anon isolation: ${label}`, "Unexpected non-JSON response.");
    }
  }
}

async function validateFunctionContracts(url, headers) {
  const functionBase = `${url}/functions/v1`;
  const postHeaders = { ...headers, "Content-Type": "application/json" };

  for (const name of ["bridge-sync", "sync-parents", "notify-parents"]) {
    await expectHttp(
      `Internal auth: ${name}`,
      `${functionBase}/${name}`,
      { method: "POST", headers: postHeaders, body: "{}" },
      [401]
    );
  }

  await expectHttp(
    "OTP request validation",
    `${functionBase}/request-otp`,
    {
      method: "POST",
      headers: postHeaders,
      body: JSON.stringify({ phone: "", school_id: 0 }),
    },
    [400]
  );
  await expectHttp(
    "OTP verify validation",
    `${functionBase}/verify-otp`,
    {
      method: "POST",
      headers: postHeaders,
      body: JSON.stringify({ phone: "", code: "", school_id: 0, device_id: "" }),
    },
    [400]
  );
}

async function main() {
  const localEnv = loadEnv();
  const url = process.env.EXPO_PUBLIC_SUPABASE_URL || localEnv.EXPO_PUBLIC_SUPABASE_URL;
  const anonKey =
    process.env.EXPO_PUBLIC_SUPABASE_ANON_KEY || localEnv.EXPO_PUBLIC_SUPABASE_ANON_KEY;
  if (!url || !anonKey) {
    throw new Error("EXPO_PUBLIC_SUPABASE_URL and EXPO_PUBLIC_SUPABASE_ANON_KEY are required.");
  }

  const projectRef = new URL(url).hostname.split(".")[0];
  const headers = { apikey: anonKey, Authorization: `Bearer ${anonKey}` };
  console.log(`[smoke] Project: ${projectRef}`);

  const authHealth = await expectHttp(
    "Auth health",
    `${url}/auth/v1/health`,
    { headers },
    [200]
  );
  if (!authHealth || authHealth.response.status !== 200) {
    console.error("\n[smoke] BLOCKED: Supabase Auth is not healthy; downstream probes were skipped.");
    process.exit(1);
  }

  await validateMigrations();
  await validateFunctions(projectRef);
  await validateRestSecurity(url, headers);
  await validateFunctionContracts(url, headers);

  if (failures.length) {
    console.error(`\n[smoke] FAILED (${failures.length})`);
    for (const failure of failures) console.error(`- ${failure}`);
    process.exit(1);
  }

  console.log("\n[smoke] PRODUCTION SMOKE PASSED");
}

main().catch((error) => {
  console.error(`\n[smoke] BLOCKED: ${error.message}`);
  process.exit(1);
});
