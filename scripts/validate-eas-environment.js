const fs = require("fs");
const path = require("path");
const { runProcess } = require("./process-runner");
const { argumentValue } = require("./school-matrix-utils");

const root = path.resolve(__dirname, "..");
const npm = process.platform === "win32" ? "npm.cmd" : "npm";
const easCliVersion = "21.7.1";
const variant = argumentValue("variant") || "sanabil";
const requiredNames = [
  "EXPO_PUBLIC_SUPABASE_URL",
  "EXPO_PUBLIC_SUPABASE_ANON_KEY",
];
const targetEnvironments = ["preview", "production"];

function loadManifest() {
  const manifestPath = path.join(root, "config", "schools.manifest.json");
  return JSON.parse(fs.readFileSync(manifestPath, "utf8"));
}

async function runEas(args) {
  return runProcess(
    npm,
    [
      "exec",
      "--yes",
      `--package=eas-cli@${easCliVersion}`,
      "--",
      "eas",
      ...args,
    ],
    {
      cwd: root,
      capture: true,
      timeoutMs: 180_000,
      env: { ...process.env, APP_VARIANT: variant, CI: "1" },
    }
  );
}

async function main() {
  const manifest = loadManifest();
  const school = manifest.schools?.[variant];
  if (!school) {
    throw new Error(`Unknown app variant '${variant}'.`);
  }

  console.log(`[eas-env] Variant: ${variant}`);
  console.log(`[eas-env] CLI: eas-cli@${easCliVersion}`);

  const projectInfo = await runEas(["project:info"]);
  if (projectInfo.status !== 0) {
    throw new Error("EAS project inspection failed. Run the command after authenticating with EAS.");
  }

  if (!projectInfo.stdout.includes(school.easProjectId)) {
    throw new Error(
      `EAS project linkage does not match the manifest project ID for '${variant}'.`
    );
  }
  console.log("[eas-env] Project linkage: PASS");

  const missing = [];
  for (const environmentName of targetEnvironments) {
    const result = await runEas([
      "env:list",
      "--environment",
      environmentName,
      "--format",
      "long",
    ]);

    if (result.status !== 0) {
      throw new Error(
        `EAS environment inspection failed for '${environmentName}'. Values were not printed.`
      );
    }

    for (const variableName of requiredNames) {
      const present = result.stdout.includes(variableName);
      console.log(
        `[eas-env] ${environmentName}/${variableName}: ${present ? "PRESENT" : "MISSING"}`
      );
      if (!present) missing.push(`${environmentName}/${variableName}`);
    }
  }

  if (missing.length) {
    throw new Error(`Required EAS variables are missing: ${missing.join(", ")}`);
  }

  console.log("[eas-env] EAS cloud environment contract: PASS");
}

main().catch((error) => {
  console.error(`[eas-env] BLOCKED: ${error.message}`);
  process.exit(1);
});
