// src/config/schoolConfig.ts
// ================================================================
// DYNAMIC SCHOOL CONFIG — reads from EAS build-time 'extra' values
// In development (no build), falls back to Sanabil defaults.
// ================================================================
import Constants from 'expo-constants';

const extra = Constants.expoConfig?.extra ?? {};
const schoolId = Number(extra.schoolId);
const appName = typeof extra.appName === 'string' ? extra.appName.trim() : '';
const primaryColor = typeof extra.primaryColor === 'string' ? extra.primaryColor.trim() : '';

if (!Number.isSafeInteger(schoolId) || schoolId <= 0) {
    throw new Error('Build config is missing a valid schoolId.');
}

if (!appName) {
    throw new Error('Build config is missing appName.');
}

if (!/^#[0-9a-f]{6}$/i.test(primaryColor)) {
    throw new Error('Build config is missing a valid primaryColor.');
}

export const SchoolConfig = {
    // Set at build time via APP_VARIANT in eas.json → app.config.js
    SCHOOL_ID: schoolId,
    APP_NAME: appName,
    PRIMARY_COLOR: primaryColor,
    SUPPORT_PHONE: (extra.supportPhone ?? '+25261xxxxxx') as string,
    WEBSITE: (extra.website ?? 'https://sanabil.so') as string,
    APP_VARIANT: (extra.appVariant ?? 'sanabil') as string,
};
