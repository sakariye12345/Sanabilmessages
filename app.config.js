// app.config.js
// ============================================================
// MULTI-SCHOOL BUILD CONFIGURATION
// Usage:
//   APP_VARIANT=sanabil   eas build --platform android
//   APP_VARIANT=alsunna   eas build --platform android
//   APP_VARIANT=alxikma   eas build --platform android
// ============================================================

const APP_VARIANT = process.env.APP_VARIANT ?? 'sanabil';

const schools = {
    sanabil: {
        name: 'Sanabil Messages',
        slug: 'SanabilMessages',
        scheme: 'sanabilmessages',
        package: 'com.sanabil.messages',
        icon: './assets/icon.png',
        adaptiveIcon: './assets/adaptive-icon.png',
        splashIcon: './assets/splash-icon.png',
        primaryColor: '#4CAF50',
        schoolId: 1,
        supportPhone: '+25261xxxxxx',
        website: 'https://sanabil.so',
    },
    alsunna: {
        name: 'Alsunna Messages',
        slug: 'AlsunnaMessages',
        scheme: 'alsunnamessages',
        package: 'com.alsunna.messages',
        icon: './assets/alsunna-icon.png',
        adaptiveIcon: './assets/alsunna-adaptive-icon.png',
        splashIcon: './assets/alsunna-splash-icon.png',
        primaryColor: '#1565C0',
        schoolId: 2,
        supportPhone: '+25262xxxxxx',
        website: 'https://alsunna.so',
    },
    alxikma: {
        name: 'Al-Xikma Messages',
        slug: 'AlxikmaMessages',
        scheme: 'alxikmamessages',
        package: 'com.alxikma.messages',
        icon: './assets/alxikma-icon.png',
        adaptiveIcon: './assets/alxikma-adaptive-icon.png',
        splashIcon: './assets/alxikma-splash-icon.png',
        primaryColor: '#6A1B9A',
        schoolId: 3,
        supportPhone: '+25263xxxxxx',
        website: 'https://alxikma.so',
    },
};

const config = schools[APP_VARIANT] ?? schools.sanabil;

export default {
    expo: {
        name: config.name,
        slug: config.slug,
        scheme: config.scheme,
        version: '1.0.0',
        orientation: 'portrait',
        icon: config.icon,
        userInterfaceStyle: 'light',
        newArchEnabled: true,
        splash: {
            image: config.splashIcon,
            resizeMode: 'contain',
            backgroundColor: '#ffffff',
        },
        ios: {
            supportsTablet: true,
            bundleIdentifier: config.package,
        },
        android: {
            package: config.package,
            adaptiveIcon: {
                foregroundImage: config.adaptiveIcon,
                backgroundColor: '#ffffff',
            },
            edgeToEdgeEnabled: true,
            predictiveBackGestureEnabled: false,
        },
        web: {
            favicon: './assets/favicon.png',
        },
        plugins: [
            'expo-router',
            'expo-secure-store',
        ],
        extra: {
            // Available at runtime via expo-constants:
            // import Constants from 'expo-constants';
            // const schoolId = Constants.expoConfig?.extra?.schoolId;
            schoolId: config.schoolId,
            primaryColor: config.primaryColor,
            supportPhone: config.supportPhone,
            website: config.website,
            appVariant: APP_VARIANT,
            router: {},
            eas: {
                projectId: '6cc824df-679c-4e92-8a30-39e84af42abe',
            },
        },
    },
};
