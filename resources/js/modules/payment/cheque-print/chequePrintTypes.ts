/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

export interface ChequeTemplateMetadata {
    date_format?: string;
    date_letter_spacing?: number | string | null;
    crossing_type?: 'none' | 'ac_payee' | 'not_negotiable';
    crossing_x_mm?: number | string | null;
    crossing_y_mm?: number | string | null;
    bearer_strike_through?: boolean;
    bearer_x_mm?: number | string | null;
    bearer_y_mm?: number | string | null;
    amount_words_line_2_x_mm?: number | string | null;
    amount_words_line_2_y_mm?: number | string | null;
    amount_prefix?: string;
    amount_suffix?: string;
    currency_code?: string;
    memo_x_mm?: number | string | null;
    memo_y_mm?: number | string | null;
    [key: string]: unknown;
}

export interface ChequeTemplate {
    id: number;
    row_version: number;
    tenant_id: number;
    organization_unit_id?: number | null;
    organization_unit?: {
        id: number;
        name: string;
        code?: string;
    } | null;
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
    can_delete?: boolean;
    metadata?: ChequeTemplateMetadata | null;
    created_at?: string;
    updated_at?: string;
}

export interface ChequeTemplatePayload {
    expected_version?: number;
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
    metadata?: ChequeTemplateMetadata | null;
}

export interface ChequePrintPayment {
    id: number;
    payment_number: string;
    payment_method: 'cheque';
    payee_name: string;
    amount: string;
    formatted_amount?: string;
    amount_in_words: string;
    amount_words_line_1?: string;
    amount_words_line_2?: string;
    cheque_number?: string | null;
    cheque_date?: string | null;
    formatted_cheque_date?: string | null;
    date_digits?: string[];
    currency_code?: string;
    crossing_type?: string;
    crossing_text?: string | null;
    show_bearer_strike?: boolean;
    memo_text?: string;
    status: string;
}

export interface ChequePrintPreview {
    payment: ChequePrintPayment;
    template: ChequeTemplate;
}

export interface ChequePrintLog {
    id: number;
    tenant_id: number;
    organization_unit_id?: number | null;
    payment_id: number;
    payment_number?: string | null;
    payment_line_id?: number | null;
    cheque_template_id: number;
    cheque_template_name?: string | null;
    printed_by?: number | null;
    printed_by_name?: string | null;
    printed_at: string;
    print_status: 'previewed' | 'printed' | 'cancelled';
    notes?: string | null;
    created_at?: string;
}

export const coordinateFields = [
    ['date_x_mm', 'date_y_mm', 'Date'],
    ['payee_x_mm', 'payee_y_mm', 'Payee'],
    ['amount_x_mm', 'amount_y_mm', 'Amount'],
    ['amount_words_x_mm', 'amount_words_y_mm', 'Amount in words'],
    ['cheque_number_x_mm', 'cheque_number_y_mm', 'Cheque number'],
] as const;

export type ChequeCoordinateKey = typeof coordinateFields[number][0] | typeof coordinateFields[number][1];
