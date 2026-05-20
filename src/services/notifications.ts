import { Platform } from 'react-native';
import * as Device from 'expo-device';
import Constants from 'expo-constants';
import { supabase } from '../lib/supabase';
import { normalizeSomaliPhone } from '../utils/phone';

const getNotifications = () => {
    try {
        return require('expo-notifications');
    } catch (e) {
        return null;
    }
};

export async function registerForPushNotificationsAsync() {
    let token;

    const isExpoGo = Constants.appOwnership === 'expo';
    if (isExpoGo) {
        console.log('[Notifications] Expo Go detected. Using SIMULATED token to prevent crash.');
        return `ExponentPushToken[SIMULATED-${Device.modelName?.replace(/\s/g, '-') || 'DEVICE'}]`;
    }

    const Notifications = getNotifications();
    if (!Notifications) return null;

    if (Platform.OS === 'android') {
        await Notifications.setNotificationChannelAsync('default', {
            name: 'default',
            importance: Notifications.AndroidImportance.MAX,
            vibrationPattern: [0, 250, 250, 250],
            lightColor: '#FF231F7C',
        });
    }

    if (Device.isDevice) {
        const { status: existingStatus } = await Notifications.getPermissionsAsync();
        let finalStatus = existingStatus;
        if (existingStatus !== 'granted') {
            const { status } = await Notifications.requestPermissionsAsync();
            finalStatus = status;
        }
        if (finalStatus !== 'granted') {
            console.log('Failed to get push token for push notification!');
            return;
        }

        try {
            token = (await Notifications.getExpoPushTokenAsync({
                projectId: Constants.expoConfig?.extra?.eas?.projectId,
            })).data;
            console.log('[Notifications] Token:', token);
        } catch (e) {
            console.log('[Notifications] Failed to get real token (likely Expo Go limitation). Using SIMULATED token.');
            token = `ExponentPushToken[SIMULATED-${Device.modelName?.replace(/\s/g, '-') || 'DEVICE'}]`;
        }
    } else {
        console.log('[Notifications] Must use physical device for Push Notifications');
        token = 'ExponentPushToken[SIMULATOR]';
    }

    return token;
}

export async function syncDeviceToken(userPhone: string) {
    try {
        const token = await registerForPushNotificationsAsync();
        if (!token) return null;

        const normalizedPhone = normalizeSomaliPhone(userPhone);
        if (!normalizedPhone) return null;

        console.log(`[Notifications] Syncing token for ${normalizedPhone}...`);

        const { error } = await supabase
            .from('user_devices')
            .upsert({
                phone_number: normalizedPhone,
                fcm_token: token,
                platform: Platform.OS,
                is_active: true,
                last_seen_at: new Date().toISOString()
            }, { onConflict: 'fcm_token' });

        if (error) {
            console.error('[Notifications] Sync Error:', error);
            throw error;
        }

        console.log('[Notifications] Token synced successfully!');
        return token;
    } catch (error) {
        console.error('[Notifications] Failed to sync token:', error);
        return null;
    }
}
