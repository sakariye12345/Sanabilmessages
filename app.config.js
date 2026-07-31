const fs = require("fs");
const path = require("path");

const APP_VARIANT = process.env.APP_VARIANT ?? "sanabil";
const manifestPath = path.join(__dirname, "config", "schools.manifest.json");
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));

const defaultAssets = {
  icon: "./assets/icon.png",
  adaptiveIcon: "./assets/adaptive-icon.png",
  splashIcon: "./assets/splash-icon.png",
  favicon: "./assets/favicon.png",
};

function toExpoAsset(relativeAssetPath, fallbackAssetPath) {
  const candidate = relativeAssetPath || fallbackAssetPath;
  const normalized = candidate.startsWith("./") ? candidate.slice(2) : candidate;
  const absolute = path.join(__dirname, normalized);
  return fs.existsSync(absolute) ? candidate : fallbackAssetPath;
}

const configuredSchool = manifest.schools?.[APP_VARIANT];
const config = configuredSchool;

if (!config) {
  throw new Error(
    `Unknown APP_VARIANT '${APP_VARIANT}'. Add it to config/schools.manifest.json before building.`
  );
}

const icon = toExpoAsset(config.assets?.icon, defaultAssets.icon);
const adaptiveIcon = toExpoAsset(config.assets?.adaptiveIcon, defaultAssets.adaptiveIcon);
const splashIcon = toExpoAsset(config.assets?.splashIcon, defaultAssets.splashIcon);
const favicon = toExpoAsset(config.assets?.favicon, defaultAssets.favicon);

module.exports = {
  expo: {
    owner: "alsunna123",
    name: config.name,
    slug: config.slug,
    scheme: config.scheme,
    version: "1.0.0",
    orientation: "portrait",
    icon,
    userInterfaceStyle: "light",
    newArchEnabled: true,
    splash: {
      image: splashIcon,
      resizeMode: "contain",
      backgroundColor: "#ffffff",
    },
    ios: {
      supportsTablet: true,
      bundleIdentifier: config.package,
    },
    android: {
      package: config.package,
      adaptiveIcon: {
        foregroundImage: adaptiveIcon,
        backgroundColor: "#ffffff",
      },
      edgeToEdgeEnabled: true,
      predictiveBackGestureEnabled: false,
    },
    web: {
      favicon,
    },
    plugins: [
      "expo-router",
      "expo-secure-store",
      "expo-font",
      [
        "expo-notifications",
        {
          "defaultChannel": "default"
        }
      ]
    ],
    extra: {
      schoolId: config.schoolId,
      primaryColor: config.primaryColor,
      supportPhone: config.supportPhone,
      website: config.website,
      appVariant: APP_VARIANT,
      router: {},
      eas: {
        projectId: config.easProjectId,
      },
    },
  },
};
