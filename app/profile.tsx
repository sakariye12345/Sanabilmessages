import React from 'react';
import { View, Text, StyleSheet, Pressable, Alert, ActivityIndicator } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '../src/store/auth';
import { Colors } from '../constants/Colors';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { supabase } from '../src/lib/supabase';
import {
    DeviceTrustRow,
    getCurrentDeviceId,
    listMyDevices,
    revokeDeviceTrust,
} from '../src/services/deviceTrust';
import { SchoolConfig } from '../src/config/schoolConfig';

function formatDateTime(value?: string | null) {
    if (!value) return 'Wali lama diiwaan gelin';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Waqti aan sax ahayn';

    return date.toLocaleString();
}

function platformLabel(platform?: string | null) {
    if (!platform) return 'Qalab';
    if (platform === 'ios') return 'iPhone';
    if (platform === 'android') return 'Android';
    return platform;
}

export default function ProfileScreen() {
    const router = useRouter();
    const queryClient = useQueryClient();
    const { user, signOut } = useAuthStore();

    const { data: parentProfile } = useQuery({
        queryKey: ['parent_profile_full', SchoolConfig.SCHOOL_ID, user?.phone],
        queryFn: async (): Promise<any> => {
            if (!user?.phone) return null;
            const { data, error } = await supabase.rpc('get_my_profile', {
                p_school_id: SchoolConfig.SCHOOL_ID,
            }).maybeSingle();
            if (error) throw error;
            return data;
        },
        enabled: !!user?.phone,
    });

    const { data: currentDeviceId, isLoading: currentDeviceLoading } = useQuery({
        queryKey: ['current_device_id'],
        queryFn: getCurrentDeviceId,
    });

    const { data: devices, isLoading: devicesLoading } = useQuery({
        queryKey: ['my_devices', SchoolConfig.SCHOOL_ID],
        queryFn: listMyDevices,
        enabled: !!user?.phone,
    });

    const revokeMutation = useMutation({
        mutationFn: async (device: DeviceTrustRow) => revokeDeviceTrust(device.device_id),
        onSuccess: async (_data, device) => {
            await queryClient.invalidateQueries({ queryKey: ['my_devices', SchoolConfig.SCHOOL_ID] });

            if (device.device_id === currentDeviceId) {
                await signOut();
                router.replace('/(auth)/phone');
                return;
            }

            Alert.alert('Waayahay', 'Qalabka waa laga saaray trusted devices.');
        },
        onError: (error: any) => {
            Alert.alert('Cilad', error?.message || 'Qalabka lama saari karin.');
        },
    });

    const handleLogout = async () => {
        Alert.alert(
            'Logout',
            'Are you sure you want to log out?',
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: 'Logout',
                    style: 'destructive',
                    onPress: async () => {
                        await signOut();
                        router.replace('/(auth)/phone');
                    },
                },
            ]
        );
    };

    const handleRevokeDevice = (device: DeviceTrustRow) => {
        const isCurrent = device.device_id === currentDeviceId;

        Alert.alert(
            isCurrent ? 'Ka saar qalabkan' : 'Ka saar qalabka',
            isCurrent
                ? 'Haddii aad qalabkan ka saarto trusted devices, isla markiiba waad ka bixi doontaa app-ka.'
                : 'Qalabkan waxaa laga saari doonaa trusted devices.',
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: isCurrent ? 'Ka saar & Logout' : 'Ka saar',
                    style: 'destructive',
                    onPress: () => revokeMutation.mutate(device),
                },
            ]
        );
    };

    return (
        <View style={s.container}>
            <View style={s.header}>
                <Pressable onPress={() => router.back()} style={s.backBtn}>
                    <Text style={s.closeText}>x</Text>
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
                            <Text style={s.logoutGlyph}>-&gt;</Text>
                            <Text style={[s.rowText, { color: Colors.error }]}>Log Out</Text>
                        </View>
                    </Pressable>
                </View>

                <View style={s.section}>
                    <Text style={s.sectionTitle}>Trusted Devices</Text>

                    {(devicesLoading || currentDeviceLoading) && (
                        <View style={s.loadingRow}>
                            <ActivityIndicator color={Colors.primary} />
                            <Text style={s.loadingText}>Waxaan soo qaadeynaa qalabka...</Text>
                        </View>
                    )}

                    {!devicesLoading && !(devices || []).length && (
                        <View style={s.deviceCard}>
                            <Text style={s.deviceTitle}>Qalab lama helin</Text>
                            <Text style={s.deviceMeta}>Marka login cusub dhaco, trusted device halkan ayuu kasoo muuqanayaa.</Text>
                        </View>
                    )}

                    {(devices || []).map((device) => {
                        const isCurrent = device.device_id === currentDeviceId;
                        const isRevoked = !device.is_active || !!device.revoked_at;

                        return (
                            <View key={device.id} style={s.deviceCard}>
                                <View style={s.deviceTopRow}>
                                    <View style={s.deviceTextWrap}>
                                        <Text style={s.deviceTitle}>
                                            {device.device_name || platformLabel(device.platform)}
                                        </Text>
                                        <Text style={s.deviceMeta}>
                                            {platformLabel(device.platform)}
                                            {device.app_variant ? ` - ${device.app_variant}` : ''}
                                        </Text>
                                    </View>

                                    <View style={[s.badge, isRevoked ? s.badgeMuted : isCurrent ? s.badgeCurrent : s.badgeTrusted]}>
                                        <Text style={[s.badgeText, isRevoked && s.badgeTextMuted]}>
                                            {isRevoked ? 'Revoked' : isCurrent ? 'Qalabkan' : 'Trusted'}
                                        </Text>
                                    </View>
                                </View>

                                <Text style={s.deviceMeta}>Last seen: {formatDateTime(device.last_seen_at)}</Text>
                                <Text style={s.deviceMeta}>Last login: {formatDateTime(device.last_login_at)}</Text>

                                {!isRevoked && (
                                    <Pressable
                                        style={[s.revokeBtn, revokeMutation.isPending && { opacity: 0.6 }]}
                                        onPress={() => handleRevokeDevice(device)}
                                        disabled={revokeMutation.isPending}
                                    >
                                        <Text style={s.revokeBtnText}>
                                            {isCurrent ? 'Ka saar qalabkan' : 'Ka saar qalabkan kale'}
                                        </Text>
                                    </Pressable>
                                )}
                            </View>
                        );
                    })}
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
    closeText: { fontSize: 28, lineHeight: 28, color: '#000' },
    headerTitle: { fontSize: 24, fontWeight: 'bold' },
    content: { padding: 20 },
    avatarContainer: { alignItems: 'center', marginBottom: 40 },
    avatar: {
        width: 80,
        height: 80,
        borderRadius: 40,
        backgroundColor: Colors.primary,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 12,
    },
    avatarText: { color: '#fff', fontSize: 32, fontWeight: 'bold' },
    name: { fontSize: 20, fontWeight: '600' },
    phone: { fontSize: 16, color: '#666', marginTop: 4 },
    school: { fontSize: 14, color: '#999', marginTop: 2 },
    section: {
        backgroundColor: '#fff',
        borderRadius: 12,
        overflow: 'hidden',
        marginBottom: 18,
        paddingBottom: 16,
    },
    sectionTitle: {
        fontSize: 13,
        color: '#666',
        textTransform: 'uppercase',
        marginBottom: 8,
        marginLeft: 16,
        marginTop: 16,
    },
    row: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 16,
        borderTopWidth: 1,
        borderTopColor: '#f0f0f0',
    },
    rowText: { fontSize: 16, marginLeft: 12, fontWeight: '500' },
    logoutGlyph: { color: Colors.error, fontSize: 20, lineHeight: 20, marginRight: 12, fontWeight: '700' },
    loadingRow: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 16,
        paddingBottom: 16,
    },
    loadingText: { marginLeft: 10, color: '#666' },
    deviceCard: {
        marginHorizontal: 16,
        marginBottom: 12,
        borderWidth: 1,
        borderColor: '#e7e7e7',
        borderRadius: 12,
        padding: 14,
        backgroundColor: '#fff',
    },
    deviceTopRow: { flexDirection: 'row', alignItems: 'flex-start', marginBottom: 8 },
    deviceTextWrap: { flex: 1 },
    deviceTitle: { fontSize: 16, fontWeight: '600', color: '#111' },
    deviceMeta: { fontSize: 13, color: '#666', marginTop: 2 },
    badge: {
        paddingHorizontal: 10,
        paddingVertical: 5,
        borderRadius: 999,
        marginLeft: 10,
    },
    badgeCurrent: { backgroundColor: '#dce8ff' },
    badgeTrusted: { backgroundColor: '#e7f7ed' },
    badgeMuted: { backgroundColor: '#f1f1f1' },
    badgeText: { fontSize: 12, fontWeight: '700', color: '#1240a5' },
    badgeTextMuted: { color: '#666' },
    revokeBtn: {
        marginTop: 12,
        alignSelf: 'flex-start',
        paddingHorizontal: 12,
        paddingVertical: 10,
        borderRadius: 10,
        backgroundColor: '#fff1f0',
    },
    revokeBtnText: { color: Colors.error, fontWeight: '600' },
});
