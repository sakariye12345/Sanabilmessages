import React from 'react';
import { View, Text, StyleSheet, Pressable, Alert } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '../src/store/auth';
import { Colors } from '../constants/Colors';
import { Ionicons } from '@expo/vector-icons';
import { useQuery } from '@tanstack/react-query';
import { supabase } from '../src/lib/supabase';

export default function ProfileScreen() {
    const router = useRouter();
    const { user, signOut } = useAuthStore();

    const { data: parentProfile } = useQuery({
        queryKey: ["parent_profile_full", user?.phone],
        queryFn: async () => {
            if (!user?.phone) return null;
            const { data, error } = await supabase.rpc('get_my_profile').maybeSingle();
            if (error) throw error;
            return data;
        },
        enabled: !!user?.phone,
    });

    const handleLogout = async () => {
        Alert.alert(
            "Logout",
            "Are you sure you want to log out?",
            [
                { text: "Cancel", style: "cancel" },
                {
                    text: "Logout", style: "destructive", onPress: async () => {
                        await signOut();
                        router.replace("/(auth)/phone");
                    }
                }
            ]
        );
    };

    return (
        <View style={s.container}>
            <View style={s.header}>
                <Pressable onPress={() => router.back()} style={s.backBtn}>
                    <Ionicons name="close" size={28} color="#000" />
                </Pressable>
                <Text style={s.headerTitle}>Profile</Text>
            </View>

            <View style={s.content}>
                <View style={s.avatarContainer}>
                    <View style={s.avatar}>
                        <Text style={s.avatarText}>
                            {parentProfile?.parent_name ? parentProfile.parent_name.charAt(0).toUpperCase() : (user?.phone?.[0] || '?')}
                        </Text>
                    </View>
                    <Text style={s.name}>{parentProfile?.parent_name || 'Parent'}</Text>
                    <Text style={s.phone}>{user?.phone}</Text>
                    {parentProfile?.school_id && <Text style={s.school}>School ID: {parentProfile.school_id}</Text>}
                </View>

                <View style={s.section}>
                    <Text style={s.sectionTitle}>Account</Text>
                    <Pressable style={s.row} onPress={handleLogout}>
                        <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                            <Ionicons name="log-out-outline" size={24} color={Colors.error} />
                            <Text style={[s.rowText, { color: Colors.error }]}>Log Out</Text>
                        </View>
                    </Pressable>
                </View>
            </View>
        </View>
    );
}

const s = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#f2f2f7' },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 16,
        paddingTop: 60,
        paddingBottom: 20,
        backgroundColor: '#fff',
    },
    backBtn: { marginRight: 16 },
    headerTitle: { fontSize: 24, fontWeight: 'bold' },
    content: { padding: 20 },
    avatarContainer: { alignItems: 'center', marginBottom: 40 },
    avatar: {
        width: 80, height: 80, borderRadius: 40,
        backgroundColor: Colors.primary,
        justifyContent: 'center', alignItems: 'center', marginBottom: 12
    },
    avatarText: { color: '#fff', fontSize: 32, fontWeight: 'bold' },
    name: { fontSize: 20, fontWeight: '600' },
    phone: { fontSize: 16, color: '#666', marginTop: 4 },
    school: { fontSize: 14, color: '#999', marginTop: 2 },
    section: { backgroundColor: '#fff', borderRadius: 12, overflow: 'hidden' },
    sectionTitle: { fontSize: 13, color: '#666', textTransform: 'uppercase', marginBottom: 8, marginLeft: 16, marginTop: 16 },
    row: {
        flexDirection: 'row', alignItems: 'center',
        padding: 16, borderTopWidth: 1, borderTopColor: '#f0f0f0'
    },
    rowText: { fontSize: 16, marginLeft: 12, fontWeight: '500' },
});
