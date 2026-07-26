import React, { useEffect, useMemo, useState } from "react";
import { View, Text, TextInput, Pressable, StyleSheet, Alert } from "react-native";
import { useLocalSearchParams, useRouter } from "expo-router";
import { supabase } from "../../src/lib/supabase";
import { normalizeSomaliPhone } from "../../src/utils/phone";
import { registerCurrentDeviceAfterLogin } from "../../src/services/deviceTrust";
import { useAuthStore } from "../../src/store/auth";
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

type VerifyOtpResponse = {
  success?: boolean;
  status?: "verified" | "expired" | "invalid_code" | "missing_otp" | "max_attempts" | "already_consumed";
  phone?: string;
  school_id?: number;
  attempts_remaining?: number;
  session?: {
    access_token?: string;
    refresh_token?: string;
  };
  message?: string;
  error?: string;
};

export default function VerifyScreen() {
  const { phone, code: autoCode, cooldown, statusMessage: initialStatusMessage } = useLocalSearchParams<{
    phone: string;
    code?: string;
    cooldown?: string;
    statusMessage?: string;
  }>();
  const [code, setCode] = useState(autoCode || "");
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [statusMessage, setStatusMessage] = useState(initialStatusMessage || "OTP WhatsApp ayaa laguu soo dirayaa.");
  const [cooldownLeft, setCooldownLeft] = useState(Number(cooldown || 30));
  const router = useRouter();

  useEffect(() => {
    if (!cooldownLeft || cooldownLeft <= 0) return;

    const timer = setInterval(() => {
      setCooldownLeft((current) => {
        if (current <= 1) {
          clearInterval(timer);
          return 0;
        }
        return current - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [cooldownLeft]);

  useEffect(() => {
    if (autoCode && phone) {
      verify(autoCode);
    }
  }, [autoCode, phone]);

  const resendLabel = useMemo(() => {
    if (resending) return "Fadlan sug...";
    if (cooldownLeft > 0) return `Resend (${cooldownLeft}s)`;
    return "Resend OTP";
  }, [cooldownLeft, resending]);

  const verify = async (cInput?: string | any) => {
    const c = (typeof cInput === "string" ? cInput : code).trim();
    if (!c) return Alert.alert("OTP required", "Geli OTP code-ka.");

    setLoading(true);
    try {
      const requestPhone = normalizeSomaliPhone(phone || "");
      if (!requestPhone) throw new Error("Lambarka telefoonka sax ma aha.");

      const { data: verifyData, error: verifyError } = await supabase.functions.invoke<VerifyOtpResponse>("verify-otp", {
        body: {
          phone: requestPhone,
          code: c,
          school_id: SchoolConfig.SCHOOL_ID,
        },
      });

      if (verifyError) throw verifyError;
      if (
        !verifyData?.success ||
        !verifyData.phone ||
        !verifyData.session?.access_token ||
        !verifyData.session.refresh_token
      ) {
        throw new Error(verifyData?.message || verifyData?.error || "OTP verification failed.");
      }

      const { error, data } = await supabase.auth.setSession({
        access_token: verifyData.session.access_token,
        refresh_token: verifyData.session.refresh_token,
      });

      if (error) throw error;
      if (!data.session) throw new Error("No session created.");

      try {
        await registerCurrentDeviceAfterLogin(verifyData.phone);
      } catch (registrationError: any) {
        console.warn("Trusted device registration failed:", registrationError?.message || registrationError);
      }

      const synced = await useAuthStore.getState().syncActiveSession();
      if (!synced) {
        throw new Error("Session-ka lama xasillin karin. Fadlan mar kale isku day.");
      }

      router.replace("/(tabs)/inbox");
    } catch (e: any) {
      console.error(e);
      Alert.alert("Verification failed", e.message || "OTP khaldan.");
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    if (cooldownLeft > 0 || resending) return;

    setResending(true);
    try {
      const requestPhone = normalizeSomaliPhone(phone || "");
      if (!requestPhone) throw new Error("Lambarka telefoonka sax ma aha.");

      const { data, error } = await supabase.functions.invoke<RequestOtpResponse>("request-otp", {
        body: {
          phone: requestPhone,
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

      const nextCooldown = Number(data?.cooldown_seconds ?? 30);
      setCooldownLeft(nextCooldown);
      setStatusMessage(data?.message || "OTP cusub ayaa la codsaday.");

      if (data?.status === "queued") {
        Alert.alert("Waayahay", "OTP cusub ayaa WhatsApp laguugu soo dirayaa.");
      }
    } catch (error: any) {
      Alert.alert("Cilad", error?.message || "OTP lama codsan karin.");
    } finally {
      setResending(false);
    }
  };

  return (
    <View style={s.container}>
      <Text style={s.title}>Verify OTP</Text>
      <Text style={s.sub}>OTP waxaa loo dirayaa WhatsApp-ka lambarkan: {phone}</Text>

      <View style={s.infoCard}>
        <Text style={s.infoTitle}>Xaaladda codsiga</Text>
        <Text style={s.infoText}>{statusMessage}</Text>
        {cooldownLeft > 0 && (
          <Text style={s.cooldownText}>Waxaad mar kale codsan kartaa {cooldownLeft} ilbiriqsi kadib.</Text>
        )}
      </View>

      <TextInput
        value={code}
        onChangeText={setCode}
        placeholder="123456"
        keyboardType="number-pad"
        style={s.input}
      />

      <Pressable onPress={() => verify()} style={[s.btn, loading && { opacity: 0.6 }]} disabled={loading}>
        <Text style={s.btnText}>{loading ? "Fadlan sug..." : "Verify"}</Text>
      </Pressable>

      <Pressable
        onPress={handleResend}
        style={[s.secondaryBtn, (cooldownLeft > 0 || resending) && { opacity: 0.55 }]}
        disabled={cooldownLeft > 0 || resending}
      >
        <Text style={s.secondaryBtnText}>{resendLabel}</Text>
      </Pressable>
    </View>
  );
}

const s = StyleSheet.create({
  container: { flex: 1, padding: 20, justifyContent: "center", backgroundColor: "#f7f8fb" },
  title: { fontSize: 26, fontWeight: "700", marginBottom: 8, color: "#111827" },
  sub: { fontSize: 14, color: "#4b5563", marginBottom: 16, lineHeight: 20 },
  infoCard: {
    backgroundColor: "#eef4ff",
    borderRadius: 14,
    padding: 14,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: "#d6e4ff",
  },
  infoTitle: { fontSize: 13, fontWeight: "700", color: "#174ea6", marginBottom: 6 },
  infoText: { fontSize: 14, color: "#1f2937", lineHeight: 20 },
  cooldownText: { fontSize: 13, color: "#4b5563", marginTop: 8 },
  input: {
    borderWidth: 1,
    borderColor: "#d1d5db",
    borderRadius: 12,
    padding: 12,
    marginBottom: 12,
    backgroundColor: "#fff",
  },
  btn: { backgroundColor: "#0b57d0", padding: 14, borderRadius: 12, alignItems: "center", marginBottom: 10 },
  btnText: { color: "#fff", fontWeight: "700" },
  secondaryBtn: {
    padding: 14,
    borderRadius: 12,
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#c7d2fe",
    backgroundColor: "#fff",
  },
  secondaryBtnText: { color: "#1d4ed8", fontWeight: "700" },
});
