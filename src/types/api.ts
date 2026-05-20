export type VerifyOtpResponse = {
  data?: {
    token?: string;
    user?: { id: number; phone?: string };
  };
  token?: string;
  user?: { id: number; phone?: string };
};

export type Conversation = {
  id: string;
  phone: string;
  display_name?: string;
  last_message: string;
  last_message_at: string;
  unread_count: number;
};

export type MessageItem = {
  id: number | string;
  conversation_id: string;
  direction: "inbound" | "outbound";
  body: string;
  status?: "pending" | "sent" | "failed";
  created_at: string;
  read_at?: string | null;
};
