import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Message } from '../../types';
import { Colors } from '../../constants/Colors';

interface MessageBubbleProps {
    message: Message;
    showTime?: boolean;
}

export const MessageBubble: React.FC<MessageBubbleProps> = ({ message, showTime = true }) => {
    const isOutbound = message.direction === 'outbound';

    const formatTime = (isoString: string) => {
        return new Date(isoString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    return (
        <View style={[
            s.container,
            isOutbound ? s.outboundContainer : s.inboundContainer
        ]}>
            <View style={[
                s.bubble,
                isOutbound ? s.outboundBubble : s.inboundBubble
            ]}>
                <Text style={[
                    s.text,
                    isOutbound ? s.outboundText : s.inboundText
                ]}>
                    {message.body}
                </Text>

                {showTime && (
                    <View style={s.metaRow}>
                        <Text style={[
                            s.time,
                            isOutbound ? s.outboundTime : s.inboundTime
                        ]}>
                            {formatTime(message.created_at)}
                        </Text>
                        {isOutbound && (
                            <Text style={[s.status, { color: Colors.textBubbleOutbound, opacity: 0.8 }]}>
                                {/* Simple status indicator */}
                                {message.status === 'sent' ? '✓' : message.status === 'delivered' ? '✓✓' : '•'}
                            </Text>
                        )}
                    </View>
                )}
            </View>
        </View>
    );
};

const s = StyleSheet.create({
    container: {
        width: '100%',
        paddingHorizontal: 16,
        marginVertical: 2,
    },
    outboundContainer: {
        alignItems: 'flex-end',
    },
    inboundContainer: {
        alignItems: 'flex-start',
    },
    bubble: {
        maxWidth: '80%',
        paddingHorizontal: 16,
        paddingVertical: 10,
        borderRadius: 18,
    },
    inboundBubble: {
        backgroundColor: Colors.bubbleInbound,
        borderBottomLeftRadius: 4,
    },
    outboundBubble: {
        backgroundColor: Colors.bubbleOutbound,
        borderBottomRightRadius: 4,
    },
    text: {
        fontSize: 16,
        lineHeight: 22,
    },
    inboundText: {
        color: Colors.textBubbleInbound,
    },
    outboundText: {
        color: Colors.textBubbleOutbound,
    },
    metaRow: {
        flexDirection: 'row',
        justifyContent: 'flex-end',
        alignItems: 'center',
        gap: 4,
        marginTop: 4,
    },
    time: {
        fontSize: 10,
    },
    inboundTime: {
        color: Colors.textSecondary,
    },
    outboundTime: {
        color: Colors.textBubbleOutbound,
        opacity: 0.8,
    },
    status: {
        fontSize: 10,
        fontWeight: 'bold',
    }
});
