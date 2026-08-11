import React, { useEffect, useState } from "react";
import { View, Text, StyleSheet, ScrollView, ActivityIndicator, Pressable } from "react-native";
import { useLocalSearchParams, Stack } from "expo-router";
import { supabase } from "../../src/lib/supabase";
import dayjs from "dayjs";
import { SchoolConfig } from "../../src/config/schoolConfig";
import { Colors } from "../../constants/Colors";

export default function MessageDetailScreen() {
    const { id } = useLocalSearchParams<{ id: string }>();
    const [message, setMessage] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState<string | null>(null);

    useEffect(() => {
        fetchMessage();
    }, [id]);

    const fetchMessage = async () => {
        const messageId = Number(id);
        if (!Number.isSafeInteger(messageId) || messageId <= 0) {
            setLoadError("Message ID-ga sax ma aha.");
            setLoading(false);
            return;
        }

        try {
            setLoading(true);
            setLoadError(null);
            setMessage(null);
            const { data, error } = await supabase.rpc("get_message_detail", {
                p_school_id: SchoolConfig.SCHOOL_ID,
                p_message_id: messageId,
            }).maybeSingle();

            if (error) throw error;
            setMessage(data);
        } catch (err) {
            console.error("Error fetching message:", err);
            setLoadError(err instanceof Error ? err.message : "Fariinta lama soo qaadi karin.");
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <View style={s.center}>
                <ActivityIndicator size="large" color={Colors.primary} />
            </View>
        );
    }

    if (!message) {
        return (
            <View style={s.center}>
                <Text style={loadError ? s.errorText : undefined}>
                    {loadError || "Fariintan lama helin."}
                </Text>
                {loadError && (
                    <Pressable style={s.retryButton} onPress={() => void fetchMessage()}>
                        <Text style={s.retryText}>Mar kale isku day</Text>
                    </Pressable>
                )}
            </View>
        );
    }

    return (
        <ScrollView style={s.container}>
            <Stack.Screen options={{ title: message.type?.toUpperCase() || "MESSAGE" }} />

            <View style={s.header}>
                <Text style={s.date}>{dayjs(message.created_at).format("DD MMM YYYY, h:mm A")}</Text>
                <Text style={s.title}>{message.title}</Text>
                <ParamsTag type={message.type} />
            </View>

            <View style={s.body}>
                <Text style={s.bodyText}>{message.body}</Text>
            </View>
        </ScrollView>
    );
}

function ParamsTag({ type }: { type: string }) {
    const color = type === "absence" ? "#d32f2f" : type === "notice" ? "#ed6c02" : "#0288d1";
    return (
        <View style={[s.tag, { backgroundColor: color }]}>
            <Text style={s.tagText}>{type?.toUpperCase()}</Text>
        </View>
    );
}

const s = StyleSheet.create({
    container: { flex: 1, backgroundColor: "#fff" },
    center: { flex: 1, justifyContent: "center", alignItems: "center" },
    header: { padding: 20, borderBottomWidth: 1, borderBottomColor: "#eee" },
    date: { color: "#666", fontSize: 13, marginBottom: 4 },
    title: { fontSize: 22, fontWeight: "700", color: "#000", marginBottom: 10 },
    body: { padding: 20 },
    bodyText: { fontSize: 16, lineHeight: 24, color: "#333" },
    tag: { alignSelf: "flex-start", paddingHorizontal: 8, paddingVertical: 4, borderRadius: 4 },
    tagText: { color: "#fff", fontSize: 10, fontWeight: "700" },
    errorText: { color: Colors.error, textAlign: "center", marginHorizontal: 24 },
    retryButton: { backgroundColor: Colors.primary, marginTop: 14, paddingHorizontal: 18, paddingVertical: 11, borderRadius: 10 },
    retryText: { color: Colors.onPrimary, fontWeight: "700" },
});
