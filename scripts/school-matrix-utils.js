const fs = require("fs");
const path = require("path");

function parseCsv(content) {
  const rows = [];
  let current = "";
  let record = [];
  let inQuotes = false;

  for (let i = 0; i < content.length; i += 1) {
    const char = content[i];
    const next = content[i + 1];

    if (char === '"') {
      if (inQuotes && next === '"') {
        current += '"';
        i += 1;
      } else {
        inQuotes = !inQuotes;
      }
      continue;
    }

    if (char === "," && !inQuotes) {
      record.push(current);
      current = "";
      continue;
    }

    if ((char === "\n" || char === "\r") && !inQuotes) {
      if (char === "\r" && next === "\n") i += 1;
      record.push(current);
      current = "";
      if (record.some((value) => value !== "")) {
        rows.push(record);
      }
      record = [];
      continue;
    }

    current += char;
  }

  if (current.length || record.length) {
    record.push(current);
    if (record.some((value) => value !== "")) {
      rows.push(record);
    }
  }

  return rows;
}

function readSchoolMatrix(csvPath) {
  const absolute = path.resolve(csvPath);
  const content = fs.readFileSync(absolute, "utf8");
  const rows = parseCsv(content);
  const [headerRow, ...dataRows] = rows;

  if (!headerRow) {
    throw new Error("school_matrix_template.csv is empty.");
  }

  return dataRows.map((row) => {
    const record = {};
    headerRow.forEach((key, index) => {
      record[key] = (row[index] || "").trim();
    });
    return record;
  });
}

function isPlaceholder(value) {
  if (!value) return true;
  const normalized = String(value).trim().toLowerCase();
  return (
    normalized.includes("<") ||
    normalized.includes("xxxx") ||
    normalized.includes("your_") ||
    normalized.includes("your-") ||
    normalized.includes("replace_me") ||
    normalized.includes("placeholder") ||
    normalized.includes("todo") ||
    normalized.includes(".example") ||
    normalized.includes("example.com") ||
    normalized.includes(".invalid") ||
    /^school\s+[b-z0-9]+$/i.test(normalized) ||
    /\d0{4,}\d/.test(normalized)
  );
}

function isValidHttpsUrl(value) {
  try {
    const parsed = new URL(value);
    return parsed.protocol === "https:" && !isPlaceholder(parsed.hostname);
  } catch {
    return false;
  }
}

function sqlString(value) {
  if (value === null || value === undefined || value === "") return "NULL";
  return `'${String(value).replace(/'/g, "''")}'`;
}

module.exports = {
  readSchoolMatrix,
  isPlaceholder,
  isValidHttpsUrl,
  sqlString,
};
