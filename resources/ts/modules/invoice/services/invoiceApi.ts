import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type { Invoice, InvoiceDocumentType, InvoiceInput, InvoicePage, InvoiceStatus } from '../types/invoice.types';

type InvoiceRecord = {
    balance_due: string; business_context: string; customer_id?: number | null; document_type: InvoiceDocumentType; due_date?: string | null; grand_total: string; id: number; invoice_date: string; invoice_number: string; ledger_direction: 'receivable' | 'payable'; paid_total: string; status: InvoiceStatus; supplier_id?: number | null;
};

function mapInvoice(record: InvoiceRecord): Invoice {
    return { balanceDue: record.balance_due, businessContext: record.business_context, customerId: record.customer_id, documentType: record.document_type, dueDate: record.due_date, grandTotal: record.grand_total, id: record.id, invoiceDate: record.invoice_date, invoiceNumber: record.invoice_number, ledgerDirection: record.ledger_direction, paidTotal: record.paid_total, status: record.status, supplierId: record.supplier_id };
}

function payload(input: InvoiceInput) {
    return {
        balance_effect: input.balanceEffect,
        business_context: input.businessContext,
        customer_id: input.customerId || null,
        document_type: input.documentType,
        due_date: input.dueDate || null,
        invoice_date: input.invoiceDate,
        invoice_number: input.invoiceNumber || null,
        ledger_direction: input.ledgerDirection,
        notes: input.notes || null,
        original_invoice_id: input.originalInvoiceId || null,
        supplier_id: input.supplierId || null,
        lines: input.lines.map((line) => ({ description: line.description, item_id: line.itemId || null, quantity: line.quantity, tax_total: line.taxTotal || '0', unit_price: line.unitPrice })),
        adjustments: (input.adjustments || []).filter((item) => Number(item.amount) > 0).map((item) => ({ adjustment_type: item.adjustmentType, amount: item.amount, effect: item.effect, name: item.name || null })),
    };
}

export const invoiceApi = {
    async create(input: InvoiceInput): Promise<Invoice> {
        const response = await httpClient<ApiResponse<InvoiceRecord>>('/api/invoice/invoices', { body: payload(input), method: 'POST' });
        return mapInvoice(response.data);
    },
    async get(id: number): Promise<Invoice> {
        const response = await httpClient<ApiResponse<InvoiceRecord>>(`/api/invoice/invoices/${id}`);
        return mapInvoice(response.data);
    },
    async issue(id: number): Promise<Invoice> {
        const response = await httpClient<ApiResponse<InvoiceRecord>>(`/api/invoice/invoices/${id}/issue`, { method: 'POST' });
        return mapInvoice(response.data);
    },
    async list(query: { documentType?: InvoiceDocumentType; page: number; perPage: number; search?: string; status?: InvoiceStatus }): Promise<InvoicePage> {
        const response = await httpClient<ApiCollectionResponse<InvoiceRecord>>('/api/invoice/invoices', { query: { document_type: query.documentType, page: query.page, per_page: query.perPage, search: query.search, status: query.status } });
        return { invoices: response.data.map(mapInvoice), meta: { currentPage: response.meta?.current_page ?? query.page, lastPage: response.meta?.last_page ?? 1, perPage: response.meta?.per_page ?? query.perPage, total: response.meta?.total ?? response.data.length } };
    },
    async remove(id: number): Promise<void> {
        await httpClient<void>(`/api/invoice/invoices/${id}`, { method: 'DELETE' });
    },
    async update(id: number, input: InvoiceInput): Promise<Invoice> {
        const response = await httpClient<ApiResponse<InvoiceRecord>>(`/api/invoice/invoices/${id}`, { body: payload(input), method: 'PUT' });
        return mapInvoice(response.data);
    },
};
