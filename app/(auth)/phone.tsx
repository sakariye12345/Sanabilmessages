import React, { useState } from "react";
import { View, Text, TextInput, Pressable, StyleSheet, Alert } from "react-native";
import { useRouter } from "expo-router";
import { supabase } from "../../src/lib/supabase";
import { normalizeSomaliPhone, toE164SomaliPhone } from "../../src/utils/phone";
import { SchoolConfig } from "../../src/config/schoolConfig";

type RequestOtpResponse = {
  success?: boolean;
  status?: "queued" | "existing_active" | "paused";
  queued?: boolean;
  reused?: boolean;
  provider?: string;
  cooldown_seconds?: number;
  message?: string;
  paused?: boolean;
  pause_until?: string | null;
  error?: string;
};

export default function PhoneScreen() {
  const [phone, setPhone] = useState("");
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  const verifyPhone = async () => {
    const inputPhone = phone.trim();
    if (!inputPhone) return Alert.alert("Phone required", "Geli lambarka telefoonka.");

    setLoading(true);
    try {
      await supabase.auth.signOut();

      const normalizedPhone = normalizeSomaliPhone(inputPhone);
      const e164Phone = toE164SomaliPhone(inputPhone);

      if (!normalizedPhone || !e164Phone) {
        throw new Error("Lambarka telefoonka sax ma aha.");
      }

      const { data, error } = await supabase.functions.invoke<RequestOtpResponse>("request-otp", {
        body: {
          phone: normalizedPhone,
          school_id: SchoolConfig.SCHOOL_ID,
        },
      });

      if (error) throw error;
      if (!data?.success && data?.paused) {
        throw new Error(data.message || "OTP service-ku si ku meel gaar ah ayuu u hakad galay.");
      }
      if (!data?.success && data?.error) {
        throw new Error(data.error);
      }

      console.log("OTP Requested:", data);

      router.push({
        pathname: "/(auth)/verify",
        params: {
          phone: e164Phone,
          cooldown: String(data?.cooldown_seconds ?? 30),
          statusMessage: data?.message ?? "OTP waa la diyaariyay.",
        },
      });
    } catch (e: any) {
      console.log("[Auth Error]", e.message);
      Alert.alert(
        "Cald Dhacay",
        e.message || "Fadlan hubi internetkaaga."
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={s.container}>
      <Text style={s.title}>Sanabil Messages</Text>
      <Text style={s.sub}>Geli lambarka telefoonka si OTP WhatsApp laguugu soo diro.</Text>

      <TextInput
        value={phone}
        onChangeText={setPhone}
        placeholder="25263xxxxxxx"
        keyboardType="phone-pad"
        style={s.input}
      />

      <Pressable onPress={verifyPhone} style={[s.btn, loading && { opacity: 0.6 }]} disabled={loading}>
        <Text style={s.btnText}>{loading ? "Fadlan sug..." : "Sii wad"}</Text>
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
