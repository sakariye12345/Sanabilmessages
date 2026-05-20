import { create } from "zustand";
import { User, Session } from "@supabase/supabase-js";
import { supabase } from "../lib/supabase";

type AuthState = {
  user: User | null;
  session: Session | null;
  hydrated: boolean;

  signOut: () => Promise<void>;
  hydrate: () => Promise<void>;
};

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  session: null,
  hydrated: false,

  signOut: async () => {
    await supabase.auth.signOut();
    set({ user: null, session: null });
  },

  hydrate: async () => {
    try {
      // 1. Get initial session
      const { data: { session }, error } = await supabase.auth.getSession();
      
      if (error) {
        console.warn("Hydration error:", error.message);
        // Clear local storage if refresh token is invalid
        if (error.message.includes("Refresh Token")) {
          await supabase.auth.signOut();
          set({ session: null, user: null, hydrated: true });
          return;
        }
      }

      set({ session, user: session?.user ?? null, hydrated: true });
    } catch (e: any) {
      console.error("Hydration failed:", e.message);
      set({ hydrated: true });
    }

    // 2. Listen for changes
    supabase.auth.onAuthStateChange((_event, session) => {
      set({ session, user: session?.user ?? null });
    });
  },
}));
