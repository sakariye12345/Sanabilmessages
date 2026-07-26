const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");

const root = path.resolve(__dirname, "..");
const allowedExtensions = new Set([
  ".js",
  ".jsx",
  ".ts",
  ".tsx",
  ".py",
  ".sql",
  ".json",
  ".toml",
  ".md",
  ".yml",
  ".yaml",
  ".php",
  ".env",
]);
const skippedFragments = [
  "node_modules/",
  "dist/",
  ".git/",
  ".expo-production-audit/",
  "ci3-demo/demo/assets/",
  "ci3-demo/demo/system/",
];
const ignoredValues = [
  "YOUR_",
  "YOUR-",
  "REPLACE",
  "PLACEHOLDER",
  "EXAMPLE",
  "PROCESS.ENV",
  "DENO.ENV",
  "OS.ENVIRON",
  "GETENV",
  "${",
  "...",
];

const patterns = [
  {
    name: "GitHub personal access token",
    regex: /\b(?:ghp_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{20,})\b/g,
  },
  {
    name: "Supabase secret key",
    regex: /\bsb_secret_[A-Za-z0-9_-]{20,}\b/g,
  },
  {
    name: "JWT-like credential",
    regex: /\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\b/g,
  },
  {
    name: "hard-coded token assignment",
    regex:
      /\b(?:CI3_TOKEN|CI3_API_TOKEN|API_TOKEN|SERVICE_KEY|SERVICE_ROLE_KEY|SUPABASE_SERVICE_KEY)\s*[:=]\s*["']([^"']{12,})["']/gi,
    valueGroup: 1,
  },
  {
    name: "hard-coded SQL API token",
    regex:
      /\b(?:ci3_token|messages_api_token|parents_api_token)\s*=\s*'([^']{12,})'/gi,
    valueGroup: 1,
  },
];

function shouldIgnore(value) {
  const upper = value.toUpperCase();
  return ignoredValues.some((fragment) => upper.includes(fragment));
}

function lineNumber(content, index) {
  return content.slice(0, index).split(/\r?\n/).length;
}

function candidateFiles() {
  const output = execFileSync(
    "git",
    ["ls-files", "-z", "--cached", "--others", "--exclude-standard"],
    { cwd: root, encoding: "buffer" },
  );
  return output
    .toString("utf8")
    .split("\0")
    .filter(Boolean)
    .filter((relative) => {
      const normalized = relative.replace(/\\/g, "/");
      if (skippedFragments.some((fragment) => normalized.includes(fragment))) {
        return false;
      }
      if (/\.min\.(js|css)$/i.test(normalized) || normalized.endsWith("package-lock.json")) {
        return false;
      }
      return allowedExtensions.has(path.extname(normalized).toLowerCase());
    });
}

const findings = [];

for (const relative of candidateFiles()) {
  const absolute = path.join(root, relative);
  let content;
  try {
    content = fs.readFileSync(absolute, "utf8");
  } catch {
    continue;
  }

  for (const pattern of patterns) {
    pattern.regex.lastIndex = 0;
    for (const match of content.matchAll(pattern.regex)) {
      const value = pattern.valueGroup ? match[pattern.valueGroup] : match[0];
      if (shouldIgnore(value)) continue;
      findings.push({
        file: relative,
        line: lineNumber(content, match.index),
        type: pattern.name,
      });
    }
  }
}

if (findings.length) {
  console.error("SECRET SCAN FAILED");
  for (const finding of findings) {
    console.error(`${finding.file}:${finding.line} ${finding.type}`);
  }
  process.exit(1);
}

console.log("Secret scan passed. No high-risk credential patterns found.");
