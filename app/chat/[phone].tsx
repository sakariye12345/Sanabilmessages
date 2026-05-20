import React, { useMemo } from "react";
import { View, Text, StyleSheet, FlatList, TextInput, Pressable, KeyboardAvoidingView, Platform } from "react-native";
import { useLocalSearchParams, useRouter, Stack } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useAuthStore } from "../../src/store/auth";
import { api } from "../../src/api/client";
import { MessageBubble } from "../../components/Chat/MessageBubble";
import { DateSeparator } from "../../components/Chat/DateSeparator";
import { Colors } from "../../constants/Colors";
import { Message } from "../../types";

import { useMessageStore } from "../../src/store/messages";
import { parseMessageDate } from "../../src/utils/date";

export default function ChatScreen() {
  const { phone } = useLocalSearchParams<{ phone: string }>();
  const router = useRouter();
  const user = useAuthStore((s) => s.user);

  // Local Persistence
  const { markAsRead, isDeleted } = useMessageStore();

  // Reuse the same query as Inbox to ensure data consistency
  const { data: allMessages } = useQuery({
    queryKey: ["inbox_messages", user?.phone],
    queryFn: async () => {
      try {
        const res = await api.get("/messages/contacts");
        return res.data as any[];
      } catch (e) {
        console.log("Fetch error", e);
        return [];
      }
    },
    enabled: !!user?.phone,
  });

  const messages = useMemo(() => {
    if (!allMessages) return [];

    const userPhone = user?.phone || "";

    const myMessages = allMessages.filter((m: any) =>
      (m.phone.includes(userPhone) || (userPhone && userPhone.includes(m.phone))) &&
      !isDeleted(m.id)
    );

    // Map API data to Message type
    return myMessages.map((m: any, index: number) => {
      // Parse date from body
      const meaningfulDate = parseMessageDate(m.message || "", m.created_at || new Date().toISOString());

      return {
        id: m.id || `msg_${index}`,
        conversation_id: 'school_thread',
        body: m.message,
        sender: 'other',
        status: 'read',
        created_at: meaningfulDate, // Use parsed date
      };
    }).sort((a: any, b: any) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
    // Sort Newest First (b - a) because FlatList inverted renders data[0] at bottom? 
    // Wait. FlatList status: inverted.
    // Inverted means Bottom = data[0].
    // We want Newest at Bottom.
    // So data[0] should be Newest.
    // So Sort Descending (b - a) is correct.

  }, [allMessages, user, phone]);

  // Mark messages as read when they appear
  React.useEffect(() => {
    if (messages.length > 0) {
      messages.forEach((msg: any) => {
        // Mark in local store
        markAsRead(msg.id);
      });

      // Also fire and forget API call for good measure
      messages.forEach((msg: any) => {
        api.post('/messages/update_status', { message_id: msg.id, status: 'read' }).catch(() => { });
      });
    }
  }, [messages, markAsRead]);

  const renderItem = ({ item, index }: { item: Message; index: number }) => {
    const nextItem = messages[index + 1];
    let showDateSeparator = false;

    if (!nextItem) {
      showDateSeparator = true;
    } else {
      const currentDate = new Date(item.created_at);
      const prevDate = new Date(nextItem.created_at);
      if (currentDate.getDate() !== prevDate.getDate()) {
        showDateSeparator = true;
      }
    }

    return (
      <View>
        {showDateSeparator && <DateSeparator date={new Date(item.created_at)} />}
        <MessageBubble message={item} />
      </View>
    );
  };

  return (
    <View style={s.container}>
      <Stack.Screen
        options={{
          headerTitle: "Sanabil School", // Hardcode for now as per requirement
          headerTintColor: Colors.textPrimary,
          headerStyle: { backgroundColor: Colors.background },
          headerShadowVisible: false, // Android Messages flat header
        }}
      />

      <FlatList
        data={messages}
        keyExtractor={(item, index) => item.id ? item.id.toString() : `fallback_${index}`}
        renderItem={renderItem}
        inverted
        contentContainerStyle={{ paddingVertical: 16, paddingHorizontal: 16 }}
        ListEmptyComponent={
          <View style={{ padding: 20, alignItems: 'center' }}>
            <Text style={{ color: Colors.textSecondary, textAlign: 'center' }}>
              No messages found.{'\n'}
              User Phone: {user?.phone || "[NULL]"}{'\n'}
              Total loaded: {allMessages?.length || 0}
            </Text>
          </View>
        }
      />

      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={80}>
        <View style={s.inputContainer}>
          <View style={s.inputWrapper}>
            <TextInput
              style={s.input}
              placeholder="Text message"
              placeholderTextColor={Colors.textSecondary}
              multiline
            />
          </View>
          <Pressable style={s.sendBtn}>
            <Text style={s.sendIcon}>➤</Text>
          </Pressable>
        </View>
      </KeyboardAvoidingView>
    </View>
  );
}

const s = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.background },
  inputContainer: {
    flexDirection: 'row',
    padding: 8,
    alignItems: 'center',
    backgroundColor: Colors.background,
    // Google messages has no top border usually vs background
  },
  inputWrapper: {
    flex: 1,
    backgroundColor: Colors.surface, // Light gray pill
    borderRadius: 24,
    paddingHorizontal: 16,
    paddingVertical: 8,
    minHeight: 48,
    justifyContent: 'center',
  },
  input: {
    fontSize: 16,
    maxHeight: 100,
    color: Colors.textPrimary,
    padding: 0, // Reset default padding
  },
  sendBtn: {
    marginLeft: 12,
    padding: 10,
    backgroundColor: Colors.background, // Or primary if we want a circled button
    // Actually Google Messages has a dedicated send button icon
  },
  sendIcon: {
    fontSize: 24,
    color: Colors.primary,
    fontWeight: 'bold',
    transform: [{ rotate: '-90deg' }]
  }
});
