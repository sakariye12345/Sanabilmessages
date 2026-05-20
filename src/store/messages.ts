import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';

// AsyncStorage adapter for Zustand persist
// AsyncStorage has no strict size limit (up to 6MB usually)
const storage = {
    getItem: async (name: string) => {
        return (await AsyncStorage.getItem(name)) || null;
    },
    setItem: async (name: string, value: string) => {
        await AsyncStorage.setItem(name, value);
    },
    removeItem: async (name: string) => {
        await AsyncStorage.removeItem(name);
    },
};

interface MessageState {
    readMessageIds: string[];
    deletedMessageIds: string[];
    markAsRead: (id: string | number) => void;
    isRead: (id: string | number) => boolean;
    clearAllRead: () => void;
    deleteMessage: (id: string | number) => void;
    bulkDeleteMessages: (ids: (string | number)[]) => void;
    isDeleted: (id: string | number) => boolean;
}

export const useMessageStore = create<MessageState>()(
    persist(
        (set, get) => ({
            readMessageIds: [],
            deletedMessageIds: [],

            markAsRead: (id) => {
                const strId = String(id);
                const { readMessageIds } = get();
                if (!readMessageIds.includes(strId)) {
                    set({ readMessageIds: [...readMessageIds, strId] });
                }
            },

            isRead: (id) => {
                const strId = String(id);
                return get().readMessageIds.includes(strId);
            },

            clearAllRead: () => set({ readMessageIds: [] }),

            deleteMessage: (id) => {
                const strId = String(id);
                const { deletedMessageIds } = get();
                if (!deletedMessageIds.includes(strId)) {
                    set({ deletedMessageIds: [...deletedMessageIds, strId] });
                }
            },

            bulkDeleteMessages: (ids) => {
                const { deletedMessageIds } = get();
                const newIds = ids.map(String).filter(id => !deletedMessageIds.includes(id));
                if (newIds.length > 0) {
                    set({ deletedMessageIds: [...deletedMessageIds, ...newIds] });
                }
            },

            isDeleted: (id) => {
                const strId = String(id);
                return get().deletedMessageIds.includes(strId);
            },
        }),
        {
            name: 'sanabil-message-storage',
            storage: createJSONStorage(() => storage),
        }
    )
);
