export type WhatsAppVerificationStatus = 'unverified' | 'pending' | 'verified' | 'number_changed';

export interface WhatsAppVerificationRecipient {
    name: string;
    phone: string;
    source: string;
}

export interface WhatsAppVerificationState {
    available: boolean;
    status: WhatsAppVerificationStatus;
    recipient: WhatsAppVerificationRecipient | null;
    pending_expires_at?: string | null;
    verified_at?: string | null;
    verified_by?: string | null;
    verification_method?: 'manual_whatsapp_reply_code' | null;
}

export interface WhatsAppVerificationChallenge extends WhatsAppVerificationState {
    whatsapp_url: string;
}
