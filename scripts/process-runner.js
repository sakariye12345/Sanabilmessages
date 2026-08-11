const { spawn, spawnSync } = require("child_process");

function terminateProcessTree(child) {
  if (!child?.pid) return;

  if (process.platform === "win32") {
    spawnSync("taskkill", ["/pid", String(child.pid), "/T", "/F"], {
      stdio: "ignore",
      windowsHide: true,
    });
    return;
  }

  try {
    process.kill(-child.pid, "SIGKILL");
  } catch {
    child.kill("SIGKILL");
  }
}

function runProcess(command, args, options = {}) {
  const {
    cwd,
    env = process.env,
    capture = false,
    timeoutMs = 120_000,
  } = options;

  return new Promise((resolve, reject) => {
    let stdout = "";
    let stderr = "";
    let timedOut = false;
    let settled = false;

    const child = spawn(command, args, {
      cwd,
      env,
      shell: process.platform === "win32" && /\.(cmd|bat)$/i.test(command),
      stdio: capture ? ["ignore", "pipe", "pipe"] : "inherit",
      detached: process.platform !== "win32",
      windowsHide: true,
    });

    if (capture) {
      child.stdout.on("data", (chunk) => {
        stdout += chunk.toString();
      });
      child.stderr.on("data", (chunk) => {
        stderr += chunk.toString();
      });
    }

    const timer = setTimeout(() => {
      timedOut = true;
      terminateProcessTree(child);
    }, timeoutMs);

    child.once("error", (error) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      reject(error);
    });

    child.once("close", (status, signal) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      resolve({
        status: Number.isInteger(status) ? status : 1,
        signal,
        stdout: stdout.trim(),
        stderr: stderr.trim(),
        timedOut,
        timeoutMs,
      });
    });
  });
}

function formatCommandFailure(command, args, result) {
  if (result.timedOut) {
    return `Command timed out after ${Math.round(result.timeoutMs / 1000)}s: ${command} ${args.join(" ")}`;
  }

  const detail = [result.stdout, result.stderr].filter(Boolean).join("\n");
  return `Command failed: ${command} ${args.join(" ")}${detail ? `\n${detail}` : ""}`;
}

module.exports = { formatCommandFailure, runProcess };
