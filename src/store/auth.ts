import { create } from "zustand";
import { User, Session, type AuthChangeEvent } from "@supabase/supabase-js";
import { supabase } from "../lib/supabase";
import {
  ensureCurrentDeviceAccess,
  revokeCurrentDeviceTrust,
  syncCurrentDevicePushToken,
} from "../services/deviceTrust";
import { normalizeSomaliPhone } from "../utils/phone";

let hydratePromise: Promise<void> | null = null;
let authListenerAttached = false;

type AuthState = {
  user: User | null;
  session: Session | null;
  hydrated: boolean;

  signOut: () => Promise<void>;
  hydrate: () => Promise<void>;
  revalidateTrust: () => Promise<boolean>;
  syncActiveSession: () => Promise<boolean>;
};

async function syncSessionTrust(session: Session | null, set: (partial: Partial<AuthState>) => void) {
  if (!session?.user?.phone) {
    set({ session: null, user: null });
    return false;
  }

  const normalizedPhone = normalizeSomaliPhone(session.user.phone);
  if (!normalizedPhone) {
    await supabase.auth.signOut();
    set({ session: null, user: null });
    return false;
  }

  const trust = await ensureCurrentDeviceAccess(normalizedPhone);

  if (!trust.allowed) {
    await supabase.auth.signOut();
    set({ session: null, user: null });
    return false;
  }

  set({ session, user: session.user });

  try {
    await syncCurrentDevicePushToken(normalizedPhone, { requestPermission: false });
  } catch (error: any) {
    console.warn("Push token sync skipped:", error?.message || error);
  }

  return true;
}

function attachAuthListenerOnce(set: (partial: Partial<AuthState>) => void) {
  if (authListenerAttached) return;

  authListenerAttached = true;
  supabase.auth.onAuthStateChange((_event: AuthChangeEvent, session) => {
    setTimeout(() => {
      if (!session) {
        set({ session: null, user: null });
        return;
      }

      syncSessionTrust(session, set).catch((error: any) => {
        console.warn("Device trust sync failed:", error?.message || error);
      });
    }, 0);
  });
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  session: null,
  hydrated: false,

  signOut: async () => {
    try {
      await revokeCurrentDeviceTrust();
    } catch (error: any) {
      console.warn("Current device revoke failed:", error?.message || error);
    }
    await supabase.auth.signOut();
    set({ user: null, session: null });
  },

  revalidateTrust: async () => {
    const {
      data: { session },
    } = await supabase.auth.getSession();

    if (!session) {
      set({ session: null, user: null });
      return false;
    }

    return syncSessionTrust(session, set);
  },

  syncActiveSession: async () => {
    const {
      data: { session },
    } = await supabase.auth.getSession();

    if (!session) {
      set({ session: null, user: null });
      return false;
    }

    return syncSessionTrust(session, set);
  },

  hydrate: async () => {
    if (hydratePromise) {
      return hydratePromise;
    }

    hydratePromise = (async () => {
      try {
        const { data: { session }, error } = await supabase.auth.getSession();

        if (error) {
          console.warn("Hydration error:", error.message);
          if (error.message.includes("Refresh Token")) {
            await supabase.auth.signOut();
            set({ session: null, user: null, hydrated: true });
            attachAuthListenerOnce(set);
            return;
          }
        }

        if (session) {
          const allowed = await syncSessionTrust(session, set);
          if (!allowed) {
            set({ hydrated: true });
            attachAuthListenerOnce(set);
            return;
          }
        } else {
          set({ session: null, user: null });
        }

        set({ hydrated: true });
      } catch (e: any) {
        console.error("Hydration failed:", e.message);
        set({ hydrated: true });
      } finally {
        attachAuthListenerOnce(set);
      }
    })();

    await hydratePromise;
    hydratePromise = null;
  },
}));
