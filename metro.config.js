// Learn more https://docs.expo.dev/guides/customizing-metro
const { getDefaultConfig } = require('expo/metro-config');

/** @type {import('expo/metro-config').MetroConfig} */
const config = getDefaultConfig(__dirname);

// Exclude whatsapp_session directory from Metro bundler
// We push to the array because getDefaultConfig(..) returns a blockList array by default.
config.resolver.blockList.push(/whatsapp_session\/.*/);

module.exports = config;
