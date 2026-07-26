// src/config/schoolConfig.ts
// ================================================================
// DYNAMIC SCHOOL CONFIG — reads from EAS build-time 'extra' values
// In development (no build), falls back to Sanabil defaults.
// ================================================================
import Constants from 'expo-constants';

const extra = Constants.expoConfig?.extra ?? {};
const schoolId = Number(extra.schoolId);

if (!Number.isSafeInteger(schoolId) || schoolId <= 0) {
    throw new Error('Build config is missing a valid schoolId.');
}

export const SchoolConfig = {
    // Set at build time via APP_VARIANT in eas.json → app.config.js
    SCHOOL_ID: schoolId,
    APP_NAME: (extra.appVariant ? extra.appVariant.charAt(0).toUpperCase() + extra.appVariant.slice(1) + ' Messages' : 'Sanabil Messages') as string,
    PRIMARY_COLOR: (extra.primaryColor ?? '#4CAF50') as string,
    SUPPORT_PHONE: (extra.supportPhone ?? '+25261xxxxxx') as string,
    WEBSITE: (extra.website ?? 'https://sanabil.so') as string,
    APP_VARIANT: (extra.appVariant ?? 'sanabil') as string,
};
