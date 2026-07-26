import React from "react";
import { ActivityIndicator, View } from "react-native";
import { Redirect, Stack } from "expo-router";
import { useAuthStore } from "../../src/store/auth";

export default function AuthLayout() {
  const { session, hydrated } = useAuthStore();

  if (!hydrated) {
    return (
      <View style={{ flex: 1, alignItems: "center", justifyContent: "center" }}>
        <ActivityIndicator />
      </View>
    );
  }

  if (session) {
    return <Redirect href="/(tabs)/inbox" />;
  }

  return <Stack screenOptions={{ headerShown: false }} />;
}
