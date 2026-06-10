export interface ChequeTemplate {
    id: number;
    tenant_id: number;
    organization_unit_id?: number | null;
    bank_name?: string | null;
    template_name: string;
    page_width_mm: string;
    page_height_mm: string;
    date_x_mm: string;
    date_y_mm: string;
    payee_x_mm: string;
    payee_y_mm: string;
    amount_x_mm: string;
    amount_y_mm: string;
    amount_words_x_mm: string;
    amount_words_y_mm: string;
    cheque_number_x_mm?: string | null;
    cheque_number_y_mm?: string | null;
    font_size: string;
    font_family?: string | null;
    is_default: boolean;
    is_active: boolean;
    metadata?: {
        date_format?: string;
        [key: string]: unknown;
    } | null;
}

export interface ChequeTemplatePayload {
    bank_name?: string | null;
    template_name: string;
    page_width_mm: string;
    page_height_mm: string;
    date_x_mm: string;
    date_y_mm: string;
    payee_x_mm: string;
    payee_y_mm: string;
    amount_x_mm: string;
    amount_y_mm: string;
    amount_words_x_mm: string;
    amount_words_y_mm: string;
    cheque_number_x_mm?: string | null;
    cheque_number_y_mm?: string | null;
    font_size: string;
    font_family?: string | null;
    is_default: boolean;
    is_active: boolean;
    metadata?: {
        date_format?: string;
    } | null;
}

export interface ChequePrintPayment {
    id: number;
    payment_number: string;
    payment_method: 'cheque';
    payee_name: string;
    amount: string;
    amount_in_words: string;
    cheque_number?: string | null;
    cheque_date?: string | null;
    formatted_cheque_date?: string | null;
    status: string;
}

export interface ChequePrintPreview {
    payment: ChequePrintPayment;
    template: ChequeTemplate;
}

export interface ChequePrintLog {
    id: number;
    payment_id: number;
    cheque_template_id: number;
    printed_by?: number | null;
    printed_at: string;
    print_status: 'previewed' | 'printed' | 'cancelled';
    notes?: string | null;
}

export const coordinateFields = [
    ['date_x_mm', 'date_y_mm', 'Date'],
    ['payee_x_mm', 'payee_y_mm', 'Payee'],
    ['amount_x_mm', 'amount_y_mm', 'Amount'],
    ['amount_words_x_mm', 'amount_words_y_mm', 'Amount in words'],
    ['cheque_number_x_mm', 'cheque_number_y_mm', 'Cheque number'],
] as const;

export type ChequeCoordinateKey = typeof coordinateFields[number][0] | typeof coordinateFields[number][1];
