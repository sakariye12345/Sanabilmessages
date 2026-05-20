import React, { useState } from "react";
import { View, Text, TextInput, Pressable, StyleSheet, Alert } from "react-native";
import { useLocalSearchParams, useRouter } from "expo-router";
import { supabase } from "../../src/lib/supabase";
import { toE164SomaliPhone } from "../../src/utils/phone";

export default function VerifyScreen() {
  const { phone, code: autoCode } = useLocalSearchParams<{ phone: string, code?: string }>();
  const [code, setCode] = useState(autoCode || "");
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  React.useEffect(() => {
    if (autoCode && phone) {
      verify(autoCode);
    }
  }, [autoCode, phone]);

  const verify = async (cInput?: string | any) => {
    const c = (typeof cInput === "string" ? cInput : code).trim();
    if (!c) return Alert.alert("OTP required", "Geli OTP code-ka.");

    setLoading(true);
    try {
      const authPhone = toE164SomaliPhone(phone || "");
      if (!authPhone) throw new Error("Lambarka telefoonka sax ma aha.");

      const { error, data } = await supabase.auth.signInWithPassword({
        phone: authPhone,
        password: c,
      });

      if (error) throw error;
      if (!data.session) throw new Error("No session created.");

      const { syncDeviceToken } = require("../../src/services/notifications");
      syncDeviceToken(authPhone).catch((err: any) => console.log("Token sync failed (non-blocking):", err));

      router.replace("/(tabs)/inbox");
    } catch (e: any) {
      console.error(e);
      Alert.alert("Verification failed", e.message || "OTP khaldan.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={s.container}>
      <Text style={s.title}>Verify OTP</Text>
      <Text style={s.sub}>OTP waxaa loo diray: {phone}</Text>

      <TextInput
        value={code}
        onChangeText={setCode}
        placeholder="123456"
        keyboardType="number-pad"
        style={s.input}
      />

      <Pressable onPress={() => verify()} style={[s.btn, loading && { opacity: 0.6 }]} disabled={loading}>
        <Text style={s.btnText}>{loading ? "Verifying..." : "Verify"}</Text>
      </Pressable>
    </View>
  );
}

const s = StyleSheet.create({
  container: { flex: 1, padding: 20, justifyContent: "center" },
  title: { fontSize: 26, fontWeight: "700", marginBottom: 8 },
  sub: { fontSize: 14, opacity: 0.7, marginBottom: 16 },
  input: { borderWidth: 1, borderColor: "#ddd", borderRadius: 12, padding: 12, marginBottom: 12 },
  btn: { backgroundColor: "#0b57d0", padding: 14, borderRadius: 12, alignItems: "center" },
  btnText: { color: "#fff", fontWeight: "700" },
});
