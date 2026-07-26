const fs = require("fs");
const path = require("path");

const manifestPath = path.join(__dirname, "..", "config", "schools.manifest.json");
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));

const schools = manifest.schools || {};

for (const [variant, config] of Object.entries(schools)) {
  console.log(
    `${variant} | schoolId=${config.schoolId} | package=${config.package} | name=${config.name}`
  );
}
