import React from 'react';
import { View, Text, StyleSheet, Pressable } from 'react-native';
import { Conversation } from '../../types';
import { Colors } from '../../constants/Colors';
import { Avatar } from '../ui/Avatar';

interface ConversationRowProps {
    conversation: Conversation;
    onPress: () => void;
}

export const ConversationRow: React.FC<ConversationRowProps> = ({ conversation, onPress }) => {
    // Helper to format date
    const formatDate = (isoString: string) => {
        const date = new Date(isoString);
        const now = new Date();
        const diff = now.getTime() - date.getTime();

        // If < 24 hours, show time
        if (diff < 1000 * 60 * 60 * 24 && date.getDate() === now.getDate()) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        // Else show Date
        return date.toLocaleDateString();
    };

    const isUnread = conversation.unread_count > 0;

    return (
        <Pressable
            style={s.container}
            onPress={onPress}
            android_ripple={{ color: Colors.surface }}
        >
            <Avatar name={conversation.display_name || conversation.phone_number} />

            <View style={s.content}>
                <View style={s.header}>
                    <Text style={[s.title, isUnread && s.bold]}>
                        {conversation.display_name || conversation.phone_number}
                    </Text>
                    <Text style={[s.time, isUnread && { color: Colors.textPrimary }]}>
                        {formatDate(conversation.last_message_at)}
                    </Text>
                </View>

                <View style={s.footer}>
                    <Text
                        style={[s.message, isUnread && s.bold]}
                        numberOfLines={1}
                        ellipsizeMode="tail"
                    >
                        {conversation.last_message}
                    </Text>

                    {isUnread && (
                        <View style={s.badge}>
                            <Text style={s.badgeText}>{conversation.unread_count}</Text>
                        </View>
                    )}
                </View>
            </View>
        </Pressable>
    );
};

const s = StyleSheet.create({
    container: {
        flexDirection: 'row',
        paddingHorizontal: 16,
        paddingVertical: 12,
        alignItems: 'center',
        backgroundColor: Colors.background,
    },
    content: {
        flex: 1,
        marginLeft: 16,
        justifyContent: 'center',
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'baseline',
        marginBottom: 2,
    },
    footer: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    title: {
        fontSize: 16,
        fontWeight: '500',
        color: Colors.textPrimary,
        flex: 1,
    },
    message: {
        fontSize: 15,
        color: Colors.textSecondary,
        flex: 1,
        marginRight: 8,
        lineHeight: 20,
    },
    time: {
        fontSize: 12,
        color: Colors.textTimestamp,
        marginLeft: 8,
    },
    bold: {
        fontWeight: '800',
        color: Colors.textPrimary,
    },
    badge: {
        backgroundColor: Colors.unread, // Google Blue
        borderRadius: 10,
        minWidth: 20,
        height: 20,
        justifyContent: 'center',
        alignItems: 'center',
        paddingHorizontal: 6,
    },
    badgeText: {
        color: '#fff',
        fontSize: 12,
        fontWeight: 'bold',
    },
});
