import React, { useEffect, useState } from "react";
import { View, Text, StyleSheet, ScrollView, ActivityIndicator } from "react-native";
import { useLocalSearchParams, Stack } from "expo-router";
import { supabase } from "../../src/lib/supabase";
import dayjs from "dayjs";

export default function MessageDetailScreen() {
    const { id } = useLocalSearchParams();
    const [message, setMessage] = useState<any>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchMessage();
    }, [id]);

    const fetchMessage = async () => {
        if (!id) return;

        try {
            const { data, error } = await supabase.rpc("get_message_detail", {
                p_message_id: Number(id),
            }).maybeSingle();

            if (error) throw error;
            setMessage(data);
        } catch (err) {
            console.error("Error fetching message:", err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <View style={s.center}>
                <ActivityIndicator size="large" color="#0b57d0" />
            </View>
        );
    }

    if (!message) {
        return (
            <View style={s.center}>
                <Text>Message not found.</Text>
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
});
