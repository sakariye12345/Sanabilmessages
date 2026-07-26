import React, { useState, useMemo, useCallback } from "react";
import { View, Text, StyleSheet, FlatList, Pressable, TextInput, RefreshControl } from "react-native";
import { useRouter, useFocusEffect } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { useAuthStore } from "../../src/store/auth";
import { supabase } from "../../src/lib/supabase";
import { ConversationRow } from "../../components/ConversationList/ConversationRow";
import { Colors } from "../../constants/Colors";
import { Ionicons } from "@expo/vector-icons";
import { SchoolConfig } from "../../src/config/schoolConfig";
import { normalizeSomaliPhone } from "../../src/utils/phone";

type ParentProfile = {
  parent_name: string | null;
};

export default function InboxScreen() {
  const user = useAuthStore((s) => s.user);
  const router = useRouter();
  const [searchQuery, setSearchQuery] = useState("");

  useFocusEffect(
    useCallback(() => {
      refetch();
    }, [])
  );

  const { data: messages, isLoading, refetch } = useQuery({
    queryKey: ["inbox_broadcasts", SchoolConfig.SCHOOL_ID, user?.phone],
    queryFn: async (): Promise<any[]> => {
      try {
        if (!user?.phone) {
          console.log("Inbox: No user phone found");
          return [];
        }

        const { data: rawData, error: rawError } = await supabase.rpc("get_my_inbox", {
          p_school_id: SchoolConfig.SCHOOL_ID,
        });

        if (rawError) {
          console.log("Inbox raw RPC Error:", rawError);
        } else if (rawData && rawData.length > 0) {
          const pendingIds = rawData
            .filter((m: any) => m.status === "pending")
            .map((m: any) => m.id);

          if (pendingIds.length > 0) {
            console.log("Marking as SENT (Delivered to App):", pendingIds);
            supabase
              .rpc("mark_my_recipients", {
                p_school_id: SchoolConfig.SCHOOL_ID,
                p_recipient_ids: pendingIds,
                p_status: "sent",
              })
              .then(({ error }) => {
                if (error) console.error("Failed to mark sent:", error);
              });
          }
        }

        const { data: summaryData, error: summaryError } = await supabase.rpc("get_inbox_summary", {
          p_school_id: SchoolConfig.SCHOOL_ID,
        });

        if (summaryError) {
          console.log("Summary RPC Error:", summaryError);
          throw summaryError;
        }

        return (summaryData || []).map((row: any) => ({
          id: row.group_type,
          phone: row.school_name || SchoolConfig.APP_NAME,
          display_name: row.group_type,
          last_message: row.last_message,
          last_message_at: row.last_at,
          unread_count: Number(row.unread_count),
          is_broadcast: true,
          row_type: row.group_type,
        }));
      } catch (e) {
        console.log("Fetch error details:", e);
        return [];
      }
    },
  });

  React.useEffect(() => {
    if (!user?.phone) return;

    const normalizedPhone = normalizeSomaliPhone(user.phone);
    if (!normalizedPhone) return;

    const channelId = `inbox-${SchoolConfig.SCHOOL_ID}-${normalizedPhone}-${Date.now()}`;

    const channel = supabase
      .channel(channelId)
      .on(
        "postgres_changes",
        {
          event: "INSERT",
          schema: "public",
          table: "message_recipients",
          filter: `school_id=eq.${SchoolConfig.SCHOOL_ID}`,
        },
        () => {
          refetch();
        }
      )
      .subscribe((status) => {
        console.log(`Realtime Status (${channelId}):`, status);
      });

    return () => {
      supabase.removeChannel(channel);
    };
  }, [user?.phone, refetch]);

  const getCategoryTitle = (type: string) => {
    switch (type) {
      case "absence":
        return "Fariimaha Maqnaanshaha";
      case "exam":
        return "Natiijada Imtixaanka ";
      case "finance":
        return "Fariimaha Lacagta/Fees";
      case "receipt":
        return "Mahadcelin / Receipt";
      case "notice":
        return "Ogeysiis / General Notice";
      default:
        return "School Admin";
    }
  };

  const { data: parentProfile } = useQuery({
    queryKey: ["parent_profile", SchoolConfig.SCHOOL_ID, user?.phone],
    queryFn: async (): Promise<ParentProfile | null> => {
      if (!user?.phone) return null;
      const { data, error } = await supabase.rpc("get_my_profile", {
        p_school_id: SchoolConfig.SCHOOL_ID,
      }).maybeSingle();
      if (error) throw error;
      return data as ParentProfile | null;
    },
    enabled: !!user?.phone,
  });

  const conversations = useMemo(() => {
    if (!messages || messages.length === 0) return [];

    return messages
      .map((m: any) => ({
        ...m,
        display_name: getCategoryTitle(m.row_type),
      }))
      .sort((a: any, b: any) => new Date(b.last_message_at).getTime() - new Date(a.last_message_at).getTime());
  }, [messages]);

  const filteredConversations = useMemo(() => {
    if (!searchQuery) return conversations;
    return conversations.filter((c: any) =>
      c.display_name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      c.last_message?.toLowerCase().includes(searchQuery.toLowerCase())
    );
  }, [conversations, searchQuery]);

  return (
    <View style={s.container}>
      <View style={s.headerContainer}>
        <View style={s.searchBar}>
          <Ionicons name="search" size={20} color={Colors.textSecondary} style={s.searchIcon} />
          <TextInput
            style={s.searchInput}
            placeholder="Search messages..."
            placeholderTextColor="#999"
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
        </View>

        <Pressable onPress={() => router.push("/profile")}>
          <View style={{ alignItems: "center", marginLeft: 12 }}>
            <View style={s.avatar}>
              <Text style={s.avatarText}>
                {parentProfile?.parent_name ? parentProfile.parent_name.charAt(0).toUpperCase() : "P"}
              </Text>
            </View>
            {parentProfile?.parent_name && (
              <Text style={{ fontSize: 10, color: "#555", marginTop: 2, maxWidth: 80 }} numberOfLines={1}>
                {parentProfile.parent_name.split(" ")[0]}
              </Text>
            )}
          </View>
        </Pressable>
      </View>

      <FlatList
        data={filteredConversations}
        keyExtractor={(item: any) => item.id}
        refreshControl={
          <RefreshControl refreshing={isLoading} onRefresh={refetch} colors={[Colors.primary]} />
        }
        renderItem={({ item }) => (
          <ConversationRow
            conversation={item}
            onPress={() => router.push(`/thread/${item.row_type}`)}
          />
        )}
        contentContainerStyle={{ paddingBottom: 100, paddingTop: 8 }}
        ListEmptyComponent={
          <View style={s.center}>
            <Text style={{ opacity: 0.5 }}>No conversations</Text>
          </View>
        }
      />
    </View>
  );
}

const s = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  center: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    marginTop: 50,
  },
  headerContainer: {
    paddingTop: 12,
    paddingHorizontal: 16,
    paddingBottom: 8,
    backgroundColor: Colors.background,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  searchBar: {
    backgroundColor: Colors.searchBarBackground,
    borderRadius: 24,
    height: 48,
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 12,
    flex: 1,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 16,
    color: Colors.textPrimary,
  },
  avatar: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: Colors.primary,
    justifyContent: "center",
    alignItems: "center",
  },
  avatarText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "bold",
  },
});
