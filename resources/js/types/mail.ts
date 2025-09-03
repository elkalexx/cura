export interface Message {
    id: number;
    attachments: string;
    conversation_id: number;
    body: string;
    outlook_message_id: number;
    outlook_conversation_id: number;
    created_at: string;
    conversation: Conversation;
    sender_participant: SenderParticipant;
    body_summary: string;
}

export interface Conversation {
    id: number;
    subject: string;
}

export interface SenderParticipant {
    id: number;
    kind: string;
    position: string;
    contact: Contact;
}

export interface Contact {
    id: number;
    email: string;
    display_name: string;
}
