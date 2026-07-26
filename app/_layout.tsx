import React, { useEffect } from "react";
import { Stack, useRouter } from "expo-router";
import { AppState } from "react-native";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import Constants from 'expo-constants';
import { useAuthStore } from "../src/store/auth";
import { OfflineBanner } from "../src/components/OfflineBanner";
import { SchoolConfig } from "../src/config/schoolConfig";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      staleTime: 10_000,
      gcTime: 5 * 60_000,
    },
  },
});

import { GestureHandlerRootView } from 'react-native-gesture-handler';

export default function RootLayout() {
  const router = useRouter();

  useEffect(() => {
    const authStore = useAuthStore.getState();

    // Initialize Auth Session
    authStore.hydrate();

    const appStateSubscription = AppState.addEventListener("change", (state) => {
      if (state === "active") {
        useAuthStore.getState().revalidateTrust().catch((error) => {
          console.warn("Trust revalidation failed:", error?.message || error);
        });
      }
    });

    // SKIP notification listeners in Expo Go to prevent crash
    if (Constants.appOwnership === 'expo') {
      return () => {
        appStateSubscription.remove();
      };
    }

    let Notifications: any;
    try {
      Notifications = require('expo-notifications');
    } catch (e) {
      return;
    }

    const openNotification = (response: any) => {
      const data = response?.notification?.request?.content?.data;
      const messageId = Number(data?.message_id);
      const schoolId = Number(data?.school_id);

      if (
        Number.isSafeInteger(messageId) &&
        messageId > 0 &&
        schoolId === SchoolConfig.SCHOOL_ID
      ) {
        router.push(`/message/${messageId}`);
      }
    };

    Notifications.getLastNotificationResponseAsync()
      .then((response: any) => {
        if (response) openNotification(response);
      })
      .catch((error: any) => {
        console.warn("Initial notification response failed:", error?.message || error);
      });

    // Listener 1: Handle notification when app is in foreground
    const subscription1 = Notifications.addNotificationReceivedListener((notification: any) => {
      console.log('Notification received in foreground:', notification);
    });

    // Listener 2: Handle user tapping the notification (Background/Killed)
    const subscription2 = Notifications.addNotificationResponseReceivedListener((response: any) => {
      openNotification(response);
    });

    return () => {
      appStateSubscription.remove();
      subscription1.remove();
      subscription2.remove();
    };
  }, []);

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <QueryClientProvider client={queryClient}>
        <OfflineBanner />
        <Stack>
          <Stack.Screen name="(auth)" options={{ headerShown: false }} />
          <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
          <Stack.Screen name="profile" options={{ headerShown: false, presentation: 'modal' }} />
          <Stack.Screen name="message/[id]" options={{ title: 'Message', presentation: 'card' }} />
        </Stack>
      </QueryClientProvider>
    </GestureHandlerRootView>
  );
}
