export interface Conversation {
  id: string;
  phone_number: string;
  display_name?: string | null;
  last_message?: string | null;
  last_message_at: string; // ISO 8601
  unread_count: number;
}

export type MessageDirection = 'inbound' | 'outbound';
export type MessageStatus = 'pending' | 'sent' | 'delivered' | 'failed';

export interface Message {
  id: number | string;
  conversation_id: string;
  direction: MessageDirection;
  body: string;
  status: MessageStatus;
  created_at: string; // ISO 8601
  read_at?: string | null;
}

export interface User {
  id: number;
  phone_number: string;
  parent_name?: string;
}
