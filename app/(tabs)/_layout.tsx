import React from "react";
import { Tabs } from "expo-router";

export default function TabsLayout() {
  return (
    <Tabs screenOptions={{ headerShown: true }}>
      <Tabs.Screen name="inbox" options={{ title: "Fariimaha Dugsiga" }} />
    </Tabs>
  );
}
