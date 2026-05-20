import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '../../constants/Colors';

interface AvatarProps {
    name: string;
    size?: number;
    color?: string;
}

export const Avatar: React.FC<AvatarProps> = ({ name, size = 40, color = Colors.primary }) => {
    const initials = name
        .split(' ')
        .map(n => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();

    return (
        <View style={[s.container, { width: size, height: size, borderRadius: size / 2, backgroundColor: color }]}>
            <Text style={[s.text, { fontSize: size * 0.4 }]}>{initials}</Text>
        </View>
    );
};

const s = StyleSheet.create({
    container: {
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 16,
    },
    text: {
        color: '#fff',
        fontWeight: '600',
    },
});
