import React, { useEffect } from "react";
import { Stack, useRouter } from "expo-router";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import Constants from 'expo-constants';
import { useAuthStore } from "../src/store/auth";
import { OfflineBanner } from "../src/components/OfflineBanner";

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
    // Initialize Auth Session
    useAuthStore.getState().hydrate();

    // SKIP listeners in Expo Go to prevent crash
    if (Constants.appOwnership === 'expo') return;

    let Notifications: any;
    try {
      Notifications = require('expo-notifications');
    } catch (e) {
      return;
    }

    // Listener 1: Handle notification when app is in foreground
    const subscription1 = Notifications.addNotificationReceivedListener((notification: any) => {
      console.log('Notification received in foreground:', notification);
    });

    // Listener 2: Handle user tapping the notification (Background/Killed)
    const subscription2 = Notifications.addNotificationResponseReceivedListener((response: any) => {
      const data = response.notification.request.content.data;
      console.log('Notification tapped:', data);

      if (data && data.message_id) {
        // Navigate to the message detail screen
        router.push(`/message/${data.message_id}`);
      }
    });

    return () => {
      subscription1.remove();
      subscription2.remove();
    };
  }, []);

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <QueryClientProvider client={queryClient}>
        <OfflineBanner />
        <Stack>
          <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
          <Stack.Screen name="chat/[phone]" />
          <Stack.Screen name="(auth)/phone" options={{ headerShown: false }} />
          <Stack.Screen name="new-chat" options={{ title: 'New conversation', presentation: 'card' }} />
          <Stack.Screen name="profile" options={{ headerShown: false, presentation: 'modal' }} />
          <Stack.Screen name="message/[id]" options={{ title: 'Message', presentation: 'card' }} />
        </Stack>
      </QueryClientProvider>
    </GestureHandlerRootView>
  );
}
