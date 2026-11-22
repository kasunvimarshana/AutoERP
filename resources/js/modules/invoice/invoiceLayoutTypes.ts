export type InvoicePaperSize = 'a4' | 'a5' | 'thermal_80mm' | 'continuous_8_5_by_5_5' | 'letter';

export type InvoiceOrientation = 'portrait' | 'landscape';

export interface InvoiceLayoutSettings {
    show_logo?: boolean;
    show_tax_breakdown?: boolean;
    show_payment_stub?: boolean;
    show_signatures?: boolean;
    show_qr_code?: boolean;
    notes?: string | null;
    terms?: string | null;
}

export interface InvoiceLayout {
    id: number;
    code: string;
    name: string;
    description: string | null;
    paper_size: InvoicePaperSize;
    paper_size_label: string;
    orientation: InvoiceOrientation;
    view_template: string;
    is_system: boolean;
    is_active: boolean;
    custom_css: string | null;
    header_content: string | null;
    footer_content: string | null;
    settings: InvoiceLayoutSettings;
    row_version: number;
    created_at: string;
    updated_at: string;
}

export interface CreateInvoiceLayoutPayload {
    code?: string;
    name: string;
    description?: string | null;
    paper_size: InvoicePaperSize;
    orientation?: InvoiceOrientation;
    view_template: string;
    is_active?: boolean;
    custom_css?: string | null;
    header_content?: string | null;
    footer_content?: string | null;
    settings?: InvoiceLayoutSettings;
}

export interface UpdateInvoiceLayoutPayload {
    expected_version: number;
    name?: string;
    description?: string | null;
    paper_size?: InvoicePaperSize;
    orientation?: InvoiceOrientation;
    view_template?: string;
    is_active?: boolean;
    custom_css?: string | null;
    header_content?: string | null;
    footer_content?: string | null;
    settings?: InvoiceLayoutSettings;
}

export interface SetDefaultInvoiceLayoutPayload {
    code: string;
    organization_unit_id?: number | null;
    scope?: 'tenant' | 'organization_unit';
}

export interface InvoiceLayoutFilterParams {
    search?: string;
    paper_size?: string;
    is_active?: boolean;
}
