import { Conversation, Message } from '../types';

export const MOCK_CONVERSATIONS: Conversation[] = [
    {
        id: 'conv_1',
        phone: '252634458114',
        display_name: 'School Admin',
        last_message: '[Staff Attendance] System checked out early...',
        last_message_at: new Date(Date.now() - 1000 * 60 * 5).toISOString(), // 5 mins ago
        unread_count: 2,
    },
    {
        id: 'conv_2',
        phone: '252634458999',
        display_name: null,
        last_message: 'Please update your contact info.',
        last_message_at: new Date(Date.now() - 1000 * 60 * 60 * 24).toISOString(), // 1 day ago
        unread_count: 0,
    },
    {
        id: 'conv_3',
        phone: '252615550123',
        display_name: 'Transportation',
        last_message: 'The bus will be late by 10 mins.',
        last_message_at: new Date(Date.now() - 1000 * 60 * 60 * 48).toISOString(), // 2 days ago
        unread_count: 1,
    },
];

export const MOCK_MESSAGES: Record<string, Message[]> = {
    'conv_1': [
        {
            id: 1,
            conversation_id: 'conv_1',
            direction: 'inbound',
            body: '[Staff Attendance] System checked out early at 12:06.',
            status: 'delivered',
            created_at: new Date(Date.now() - 1000 * 60 * 5).toISOString(),
        },
        {
            id: 2,
            conversation_id: 'conv_1',
            direction: 'outbound',
            body: 'Thanks for letting me know.',
            status: 'sent',
            created_at: new Date(Date.now() - 1000 * 60 * 10).toISOString(),
        },
        {
            id: 3,
            conversation_id: 'conv_1',
            direction: 'inbound',
            body: 'Please check the logs.',
            status: 'delivered',
            created_at: new Date(Date.now() - 1000 * 60 * 15).toISOString(),
        },
        {
            id: 4,
            conversation_id: 'conv_1',
            direction: 'inbound',
            body: 'Another older message to test scrolling.',
            status: 'delivered',
            created_at: new Date(Date.now() - 1000 * 60 * 60).toISOString(),
        },
    ],
    'conv_2': [
        {
            id: 101,
            conversation_id: 'conv_2',
            direction: 'inbound',
            body: 'Please update your contact info.',
            status: 'delivered',
            created_at: new Date(Date.now() - 1000 * 60 * 60 * 24).toISOString(),
        },
    ]
};
