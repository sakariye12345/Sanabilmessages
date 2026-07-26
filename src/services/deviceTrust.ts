import { Platform } from "react-native";
import * as Device from "expo-device";
import * as SecureStore from "expo-secure-store";
import Constants from "expo-constants";
import { supabase } from "../lib/supabase";
import { normalizeSomaliPhone } from "../utils/phone";
import { SchoolConfig } from "../config/schoolConfig";
import {
  registerForPushNotificationsAsync,
  type PushTokenRegistrationOptions,
} from "./notifications";

const DEVICE_ID_KEY = "sanabil.device_id";

type DeviceTrustRow = {
  id: number;
  school_id: number;
  phone_number: string;
  device_id: string;
  device_name?: string | null;
  platform?: string | null;
  app_variant?: string | null;
  is_active: boolean;
  trusted_at: string | null;
  revoked_at: string | null;
  last_seen_at: string | null;
  last_login_at: string | null;
  created_at?: string | null;
};

function generateDeviceId() {
  return [
    "sanabil",
    Date.now().toString(36),
    Math.random().toString(36).slice(2, 10),
    Math.random().toString(36).slice(2, 10),
  ].join("-");
}

export async function getOrCreateDeviceId() {
  const existing = await SecureStore.getItemAsync(DEVICE_ID_KEY);
  if (existing) return existing;

  const nextId = generateDeviceId();
  await SecureStore.setItemAsync(DEVICE_ID_KEY, nextId);
  return nextId;
}

export async function getCurrentDeviceId() {
  return (await SecureStore.getItemAsync(DEVICE_ID_KEY)) ?? null;
}

function getDeviceName() {
  const model = Device.modelName?.trim();
  if (model) return model;

  const fallbackOs = Platform.OS === "ios" ? "iPhone" : "Android";
  return `${fallbackOs} Device`;
}

function getAppVariant() {
  const variant = Constants.expoConfig?.extra?.appVariant;
  return typeof variant === "string" && variant.trim() ? variant.trim() : "sanabil";
}

async function runDeviceRegistration(
  markLogin: boolean,
  includePushToken: boolean,
  pushOptions: PushTokenRegistrationOptions = {}
) {
  const deviceId = await getOrCreateDeviceId();
  const pushToken = includePushToken
    ? await registerForPushNotificationsAsync(pushOptions)
    : null;

  const { data, error } = await supabase.rpc("register_my_device", {
    p_school_id: SchoolConfig.SCHOOL_ID,
    p_device_id: deviceId,
    p_device_name: getDeviceName(),
    p_platform: Platform.OS,
    p_fcm_token: pushToken ?? null,
    p_app_variant: getAppVariant(),
    p_mark_login: markLogin,
  });

  if (error) throw error;

  return {
    deviceId,
    pushToken,
    row: Array.isArray(data) ? (data[0] as DeviceTrustRow | undefined) : (data as DeviceTrustRow | null),
  };
}

export async function registerCurrentDeviceAfterLogin(userPhone: string) {
  const normalizedPhone = normalizeSomaliPhone(userPhone);
  if (!normalizedPhone) throw new Error("Lambarka telefoonka sax ma aha.");

  return runDeviceRegistration(true, true, { requestPermission: true });
}

export async function ensureCurrentDeviceAccess(userPhone: string) {
  const normalizedPhone = normalizeSomaliPhone(userPhone);
  if (!normalizedPhone) {
    return { allowed: false as const, reason: "invalid_phone" };
  }

  const deviceId = await getOrCreateDeviceId();
  const { data, error } = await supabase.rpc("get_my_device_trust", {
    p_school_id: SchoolConfig.SCHOOL_ID,
    p_device_id: deviceId,
  });

  if (error) throw error;

  const device = Array.isArray(data) ? (data[0] as DeviceTrustRow | undefined) : (data as DeviceTrustRow | null);

  if (device && (!device.is_active || device.revoked_at)) {
    return { allowed: false as const, reason: "revoked", deviceId };
  }

  // Bootstrap legacy sessions and keep active devices warm without asking for OTP again.
  await runDeviceRegistration(false, false);

  return {
    allowed: true as const,
    reason: device ? "trusted" : "bootstrapped",
    deviceId,
  };
}

export async function syncCurrentDevicePushToken(
  userPhone: string,
  options: PushTokenRegistrationOptions = {}
) {
  const normalizedPhone = normalizeSomaliPhone(userPhone);
  if (!normalizedPhone) {
    throw new Error("Lambarka telefoonka sax ma aha.");
  }

  return runDeviceRegistration(false, true, options);
}

export async function revokeCurrentDeviceTrust() {
  const deviceId = await getCurrentDeviceId();
  if (!deviceId) return null;

  return revokeDeviceTrust(deviceId);
}

export async function listMyDevices() {
  const { data, error } = await supabase.rpc("list_my_devices", {
    p_school_id: SchoolConfig.SCHOOL_ID,
  });
  if (error) throw error;
  return (Array.isArray(data) ? data : []) as DeviceTrustRow[];
}

export async function revokeDeviceTrust(deviceId: string) {
  const { data, error } = await supabase.rpc("revoke_my_device", {
    p_school_id: SchoolConfig.SCHOOL_ID,
    p_device_id: deviceId,
  });

  if (error) throw error;

  return Array.isArray(data) ? data[0] ?? null : data ?? null;
}

export type { DeviceTrustRow };
