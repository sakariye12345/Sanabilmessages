import React, { useState } from 'react';
import { View, Text, StyleSheet, TextInput, FlatList, Pressable } from 'react-native';
import { useRouter } from 'expo-router';
import { Colors } from '../constants/Colors';
import { Avatar } from '../components/ui/Avatar';
import { Ionicons } from '@expo/vector-icons';

// Mock contacts for the UI
const MOCK_CONTACTS = [
    { id: '1', name: 'Sanabil School', phone: 'Admin' },
    { id: '2', name: 'Transportation', phone: 'Service' },
    { id: '3', name: 'Emergency', phone: 'Hotline' },
];

export default function NewChatScreen() {
    const router = useRouter();
    const [search, setSearch] = useState('');

    const renderItem = ({ item }: { item: any }) => (
        <Pressable
            style={s.contactRow}
            onPress={() => router.push(`/chat/${item.phone}`)}
            android_ripple={{ color: Colors.surfaceVariant }}
        >
            <Avatar name={item.name} size={40} />
            <View style={s.contactInfo}>
                <Text style={s.contactName}>{item.name}</Text>
                <Text style={s.contactPhone}>{item.phone}</Text>
            </View>
        </Pressable>
    );

    return (
        <View style={s.container}>
            {/* Header */}
            <View style={s.header}>
                <View style={s.searchContainer}>
                    <Text style={s.toText}>To</Text>
                    <TextInput
                        style={s.input}
                        placeholder="Type a name, phone number, or email"
                        placeholderTextColor={Colors.textSecondary}
                        value={search}
                        onChangeText={setSearch}
                        autoFocus
                    />
                </View>
            </View>

            <View style={s.listContainer}>
                <Text style={s.sectionTitle}>Top contacts</Text>
                <FlatList
                    data={MOCK_CONTACTS}
                    keyExtractor={item => item.id}
                    renderItem={renderItem}
                />
            </View>
        </View>
    );
}

const s = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: Colors.background,
    },
    header: {
        paddingTop: 16,
        paddingHorizontal: 16,
        paddingBottom: 8,
        borderBottomWidth: 1,
        borderBottomColor: Colors.border,
    },
    searchContainer: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    toText: {
        fontSize: 16,
        color: Colors.textSecondary,
        marginRight: 12,
    },
    input: {
        flex: 1,
        fontSize: 16,
        color: Colors.textPrimary,
        height: 48,
    },
    listContainer: {
        flex: 1,
        paddingTop: 16,
    },
    sectionTitle: {
        fontSize: 14,
        color: Colors.textSecondary,
        marginLeft: 16,
        marginBottom: 12,
        fontWeight: '500',
    },
    contactRow: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: 12,
        paddingHorizontal: 16,
    },
    contactInfo: {
        marginLeft: 16,
    },
    contactName: {
        fontSize: 16,
        color: Colors.textPrimary,
        marginBottom: 2,
    },
    contactPhone: {
        fontSize: 14,
        color: Colors.textSecondary,
    },
});
