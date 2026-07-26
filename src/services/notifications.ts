import { Platform } from 'react-native';
import * as Device from 'expo-device';
import Constants from 'expo-constants';

type PushTokenRegistrationOptions = {
    requestPermission?: boolean;
};

const getNotifications = () => {
    try {
        return require('expo-notifications');
    } catch (e) {
        return null;
    }
};

export async function registerForPushNotificationsAsync(options: PushTokenRegistrationOptions = {}) {
    let token;
    const { requestPermission = true } = options;

    const isExpoGo = Constants.appOwnership === 'expo';
    if (isExpoGo) {
        console.log('[Notifications] Expo Go cannot register production push tokens.');
        return null;
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

        if (existingStatus !== 'granted' && requestPermission) {
            const { status } = await Notifications.requestPermissionsAsync();
            finalStatus = status;
        }

        if (finalStatus !== 'granted') {
            console.log('[Notifications] Permission not granted. Skipping push token sync.');
            return null;
        }

        try {
            token = (await Notifications.getExpoPushTokenAsync({
                projectId: Constants.expoConfig?.extra?.eas?.projectId,
            })).data;
            console.log('[Notifications] Push token registered.');
        } catch (e) {
            console.log('[Notifications] Failed to get a production push token.');
            token = null;
        }
    } else {
        console.log('[Notifications] Must use physical device for Push Notifications');
        token = null;
    }

    return token;
}

export type { PushTokenRegistrationOptions };
