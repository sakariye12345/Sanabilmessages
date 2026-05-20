import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '../../constants/Colors';

interface DateSeparatorProps {
    date: Date;
}

export const DateSeparator: React.FC<DateSeparatorProps> = ({ date }) => {
    const formatDate = (d: Date) => {
        const now = new Date();
        const isToday = d.getDate() === now.getDate() && d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
        if (isToday) return 'Today';

        // Check yesterday
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        const isYesterday = d.getDate() === yesterday.getDate() && d.getMonth() === yesterday.getMonth() && d.getFullYear() === yesterday.getFullYear();
        if (isYesterday) return 'Yesterday';

        // Else full date
        return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
    };

    return (
        <View style={s.container}>
            <Text style={s.text}>{formatDate(date)}</Text>
        </View>
    );
};

const s = StyleSheet.create({
    container: {
        alignItems: 'center',
        marginVertical: 12,
    },
    text: {
        fontSize: 12,
        fontWeight: '500',
        color: Colors.textTimestamp,
    },
});
