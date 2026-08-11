import 'react-native-url-polyfill/auto';
import { createClient } from '@supabase/supabase-js';
import { secureSessionStorage } from './secureSessionStorage';

const supabaseUrl = process.env.EXPO_PUBLIC_SUPABASE_URL?.trim() ?? '';
const supabaseAnonKey = process.env.EXPO_PUBLIC_SUPABASE_ANON_KEY?.trim() ?? '';

function assertPublicSupabaseConfig() {
    if (!supabaseUrl || !supabaseAnonKey) {
        throw new Error('Supabase public URL/key are missing from the build environment.');
    }

    let parsedUrl: URL;
    try {
        parsedUrl = new URL(supabaseUrl);
    } catch {
        throw new Error('EXPO_PUBLIC_SUPABASE_URL is not a valid URL.');
    }

    const localDevelopment = ['localhost', '127.0.0.1'].includes(parsedUrl.hostname);
    if (parsedUrl.protocol !== 'https:' && !(localDevelopment && parsedUrl.protocol === 'http:')) {
        throw new Error('EXPO_PUBLIC_SUPABASE_URL must use HTTPS outside local development.');
    }

    if (supabaseAnonKey.length < 20 || /replace|placeholder|example/i.test(supabaseAnonKey)) {
        throw new Error('EXPO_PUBLIC_SUPABASE_ANON_KEY is invalid or still a placeholder.');
    }
}

assertPublicSupabaseConfig();

export const supabase = createClient(supabaseUrl, supabaseAnonKey, {
    auth: {
        storage: secureSessionStorage,
        autoRefreshToken: true,
        persistSession: true,
        detectSessionInUrl: false,
    },
});
