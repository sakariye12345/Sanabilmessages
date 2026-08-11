import React, { useEffect, useState, useCallback, useRef } from "react";
import { View, Text, StyleSheet, FlatList, ActivityIndicator, Pressable, Alert, Linking } from "react-native";
import { useLocalSearchParams, Stack, useRouter, useFocusEffect } from "expo-router";
import { supabase } from "../../src/lib/supabase";
import dayjs from "dayjs";
import { useAuthStore } from "../../src/store/auth";
import { Colors } from "../../constants/Colors";
import { PinchGestureHandler, State } from 'react-native-gesture-handler';
import * as Clipboard from 'expo-clipboard';
import { SchoolConfig } from '../../src/config/schoolConfig';
import { normalizeSomaliPhone } from '../../src/utils/phone';

const titleMap: Record<string, string> = {
    absence: 'Fariimaha Maqnaanshaha',
    finance: 'Fariimaha Lacagta',
    exam: 'Fariimaha Imtixaanaadka',
    notice: 'Fariimaha Guud',
    homework: 'Fariimaha Homework'
};

export default function ThreadScreen() {
    const { type } = useLocalSearchParams<{ type: string }>();
    const user = useAuthStore((s) => s.user);
    const router = useRouter();
    const [messages, setMessages] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState<string | null>(null);
    const listRef = useRef<FlatList>(null);
    const [fontScale, setFontScale] = useState(1);
    const savedScale = useRef(1);

    const onPinchEvent = (event: any) => {
        let newScale = savedScale.current * event.nativeEvent.scale;
        newScale = Math.max(0.7, Math.min(newScale, 2.5)); // Min 70%, Max 250%
        setFontScale(newScale);
    };

    const onPinchStateChange = (event: any) => {
        if (event.nativeEvent.oldState === State.ACTIVE) {
            savedScale.current *= event.nativeEvent.scale;
            savedScale.current = Math.max(0.7, Math.min(savedScale.current, 2.5));
            setFontScale(savedScale.current);
        }
    };

    const handleCopyFullText = async (text: string) => {
        await Clipboard.setStringAsync(text);
        Alert.alert("Fariinta waa la guuriyay", "Waa la 'Copy' gareeyay dhammaantii.");
    };

    useFocusEffect(
        useCallback(() => {
            fetchMessages();
        }, [user, type])
    );

    // 🟢 REALTIME SUBSCRIPTION
    useEffect(() => {
        if (!user?.phone) return;
        const phone = normalizeSomaliPhone(user.phone);
        if (!phone) return;

        const channelId = `thread-${SchoolConfig.SCHOOL_ID}-${type}-${phone}-${Date.now()}`;
        console.log("Subscribing to thread channel:", channelId);

        const channel = supabase
            .channel(channelId)
            .on(
                'postgres_changes',
                {
                    event: 'INSERT',
                    schema: 'public',
                    table: 'message_recipients',
                    filter: `school_id=eq.${SchoolConfig.SCHOOL_ID}`
                },
                (payload) => {
                    console.log('🔔 New Message in Thread!', payload);
                    // Payload only has message_recipients row, not the joined message details.
                    // Safest way is to just refetch the list to get full data + sort.
                    fetchMessages();
                }
            )
            .subscribe((status) => {
                console.log(`Thread Status (${channelId}):`, status);
            });

        return () => {
            supabase.removeChannel(channel);
        };
    }, [user, type]); // Depend on user and type to re-subscribe if they change

    const fetchMessages = async () => {
        if (!user?.phone) {
            setLoading(false);
            return;
        }

        try {
            setLoadError(null);
            // Ensure phone has + prefix for DB matching if needed, 
            // but usually auth store has it correct.
            // Ensure phone matches Auth format (252...)
            // Since we standardized DB to match Auth, we use user.phone directly.
            const phone = normalizeSomaliPhone(user.phone);
            if (!phone) return;

            // 🔒 SECURE FETCH: Use RPC instead of .from()
            const { data, error } = await supabase.rpc('get_thread_messages', {
                p_school_id: SchoolConfig.SCHOOL_ID,
                p_type: type,
            });

            if (error) throw error;

            // Map flat RPC result back to structured object style for UI
            const formattedData = (data || []).map((row: any) => ({
                id: row.id,
                created_at: row.created_at,
                status: row.status,
                phone_number: row.phone_number,
                messages: {
                    id: row.message_id,
                    title: row.title,
                    body: row.body,
                    type: row.type,
                    school_id: row.school_id
                }
            }));

            // Backend RPC already scopes results to the authenticated parent.
            // Keep the client display neutral so pilot variants don't hide valid rows
            // when build-time school config and backend school mapping differ.
            const filtered = formattedData;

            // DEDUPLICATION LOGIC (UX Fix for CI3 Duplicates)
            // We keep 'filtered' intact for marking-as-read (so we clear duplicate badges),
            // but we create a new list for UI rendering.
            const uniqueMessages = filtered.filter((msg: any, index: number, self: any[]) =>
                index === self.findIndex((t: any) => (
                    t.messages.body === msg.messages.body &&
                    Math.abs(dayjs(t.created_at).diff(dayjs(msg.created_at), 'minute')) < 10
                ))
            );

            setMessages(uniqueMessages);

            // AUTO-MARK AS READ
            // Identify unread IDs in this specific thread (Mark ALL duplicates as read)
            const unreadIds = filtered
                .filter((r: any) => r.status !== 'seen')
                .map((r: any) => r.id);

            console.log(`[Thread Debug] Found ${filtered.length} raw msgs. Displaying ${uniqueMessages.length}. Unread IDs:`, unreadIds);

            if (unreadIds.length > 0) {
                const { error: markError } = await supabase.rpc('mark_my_recipients', {
                    p_school_id: SchoolConfig.SCHOOL_ID,
                    p_recipient_ids: unreadIds,
                    p_status: 'seen',
                });

                if (markError) {
                    console.error("[Thread Debug] Mark Read Error:", markError);
                } else {
                    console.log(`[Thread] Successfully marked IDs as read:`, unreadIds);
                }
            }
        } catch (err) {
            console.error(err);
            setLoadError(err instanceof Error ? err.message : 'Fariimaha lama soo qaadi karin.');
        } finally {
            setLoading(false);
        }
    };

    const renderFormattedText = (text: string) => {
        if (!text) return null;
        // Split by **text** or *text* (handles multiline with [\s\S])
        const parts = text.split(/(\*\*[\s\S]*?\*\*|\*[\s\S]*?\*)/g);

        const baseSize = 20 * fontScale;

        return parts.map((part, index) => {
            if (part.startsWith('**') && part.endsWith('**') && part.length >= 4) {
                return <Text key={`bold-${index}`} selectable={true} style={{ fontWeight: 'bold', fontSize: baseSize, color: '#000' }}>{part.slice(2, -2)}</Text>;
            } else if (part.startsWith('*') && part.endsWith('*') && part.length >= 2) {
                return <Text key={`bold-${index}`} selectable={true} style={{ fontWeight: 'bold', fontSize: baseSize, color: '#000' }}>{part.slice(1, -1)}</Text>;
            }

            // Further process regular text for phone numbers
            const phoneParts = part.split(/(\+?[0-9]{9,13})/g);
            return phoneParts.map((tPart, tIndex) => {
                const isPhone = /^\+?[0-9]{9,13}$/.test(tPart);
                if (isPhone) {
                    return (
                        <Text
                            key={`phone-${index}-${tIndex}`}
                            selectable={true}
                            style={{ color: Colors.primary, fontSize: baseSize, textDecorationLine: 'underline', fontWeight: '500' }}
                            onPress={() => Linking.openURL(`tel:${tPart}`)}
                        >
                            {tPart}
                        </Text>
                    );
                }
                return <Text key={`text-${index}-${tIndex}`} selectable={true} style={{ fontSize: baseSize, color: '#222' }}>{tPart}</Text>;
            });
        });
    };

    const renderItem = ({ item }: { item: any }) => (
        <View style={s.card}>
            {/* Header is actionable (Navigate to detail or Long Press to Copy All) */}
            <Pressable
                style={s.cardHeader}
                onPress={() => router.push(`/message/${item.messages.id}`)}
                onLongPress={() => handleCopyFullText(item.messages.body)}
            >

                <Text style={s.date}>{dayjs(item.created_at).format('DD MMM, h:mm A')}</Text>
            </Pressable>

            {/* Body is completely independent so selectable={true} works natively */}
            <View>
                <Text selectable={true} style={[s.body, { fontSize: 20 * fontScale }]}>
                    {renderFormattedText(item.messages.body)}
                </Text>
            </View>
        </View>
    );

    return (
        <PinchGestureHandler onGestureEvent={onPinchEvent} onHandlerStateChange={onPinchStateChange}>
            <View style={s.container}>
                <Stack.Screen options={{ title: titleMap[type || ''] || 'Messages' }} />
                {loading ? (
                    <ActivityIndicator size="large" color={Colors.primary} style={{ marginTop: 50 }} />
                ) : loadError && messages.length === 0 ? (
                    <View style={s.errorCard}>
                        <Text style={s.errorTitle}>Fariimaha lama soo qaadi karin</Text>
                        <Text style={s.errorText}>{loadError}</Text>
                        <Pressable style={s.retryButton} onPress={() => {
                            setLoading(true);
                            void fetchMessages();
                        }}>
                            <Text style={s.retryText}>Mar kale isku day</Text>
                        </Pressable>
                    </View>
                ) : (
                    <>
                        {loadError && (
                            <Pressable style={s.inlineError} onPress={() => void fetchMessages()}>
                                <Text style={s.inlineErrorText}>Connection error. Taabo si aad dib ugu tijaabiso.</Text>
                            </Pressable>
                        )}
                        <FlatList
                            ref={listRef}
                            inverted
                            data={messages}
                            renderItem={renderItem}
                            keyExtractor={item => item.id.toString()}
                            // For an inverted list, paddingTop is physical bottom padding, paddingBottom is physical top padding.
                            contentContainerStyle={[s.list, { paddingBottom: 20, paddingTop: 23 }]}
                            ListEmptyComponent={
                                <View style={{ transform: [{ scaleY: -1 }], alignItems: 'center', marginTop: 50 }}>
                                    <Text style={s.empty}>Wali fariimo ma jiraan.</Text>
                                </View>
                            }
                        />
                    </>
                )}
            </View>
        </PinchGestureHandler>
    );
}

const s = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#f2f2f2' },
    list: { padding: 16 },
    card: {
        backgroundColor: '#fff',
        borderRadius: 12,
        padding: 16,
        marginBottom: 24,
        elevation: 1,
        shadowColor: '#000',
        shadowOpacity: 0.05,
        shadowRadius: 2,
        shadowOffset: { width: 0, height: 1 }
    },
    cardHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: 6,
        alignItems: 'center'
    },
    title: {
        fontSize: 16,
        fontWeight: '600',
        color: '#000',
        flex: 1,
        marginRight: 8
    },
    date: {
        fontSize: 15,
        color: '#000',
        fontWeight: '700',

    },
    body: {
        fontSize: 20,
        color: '#222',
        lineHeight: 28
    },
    empty: {
        textAlign: 'center',
        color: '#999',
        marginTop: 50
    },
    errorCard: {
        margin: 24,
        marginTop: 60,
        padding: 18,
        borderRadius: 14,
        backgroundColor: '#fff3f2',
        alignItems: 'center'
    },
    errorTitle: { color: Colors.error, fontSize: 17, fontWeight: '700', textAlign: 'center' },
    errorText: { color: Colors.textSecondary, marginTop: 8, textAlign: 'center', lineHeight: 20 },
    retryButton: { backgroundColor: Colors.primary, marginTop: 14, paddingHorizontal: 18, paddingVertical: 11, borderRadius: 10 },
    retryText: { color: Colors.onPrimary, fontWeight: '700' },
    inlineError: { backgroundColor: '#fff3f2', padding: 10, alignItems: 'center' },
    inlineErrorText: { color: Colors.error, fontWeight: '600' }
});
