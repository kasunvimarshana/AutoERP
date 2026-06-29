import type { NamedResource } from '@/shared/types/common';
import type { FastSalesPayload } from './fastSalesPayloadTypes';

export interface FastSalesDocumentReference {
    id: number;
    number: string;
    status?: string;
    posting_status?: string;
    url: string;
}

export interface FastSalesLinePreview {
    line_number: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    description?: string;
    is_stock: boolean;
    quantity: string;
    base_quantity?: string;
    available_quantity?: string | null;
    available_base_quantity?: string | null;
    unit_price: string;
    discount_amount: string;
    tax_amount: string;
    withholding_amount: string;
    line_total: string;
}

export interface FastSalesResult {
    customer_reference?: string;
    mode: string;
    options: FastSalesPayload['options'];
    customer?: NamedResource | null;
    summary: {
        subtotal: string;
        discount_total: string;
        tax_total: string;
        withholding_total: string;
        grand_total: string;
        received_total: string;
        balance_due: string;
    };
    lines: FastSalesLinePreview[];
    documents: {
        sales_order?: FastSalesDocumentReference | null;
        goods_delivery?: FastSalesDocumentReference | null;
        inventory_transaction?: FastSalesDocumentReference | null;
        inventory_transactions?: FastSalesDocumentReference[];
        customer_invoice?: FastSalesDocumentReference | null;
        customer_receipt?: FastSalesDocumentReference | null;
    };
}
