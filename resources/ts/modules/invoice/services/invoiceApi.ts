import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type { Invoice, InvoiceDocumentType, InvoiceInput, InvoicePage, InvoiceStatus } from '../types/invoice.types';

type InvoiceRecord = {
    adjustment_total: string; adjustments?: Array<Record<string, any>>; balance_due: string; business_context: string; charge_total: string; credit_adjustment_total: string; customer_id?: number | null; debit_adjustment_total: string; document_type: InvoiceDocumentType; due_date?: string | null; grand_total: string; gross_total: string; header_discount_total: string; id: number; invoice_date: string; invoice_number: string; ledger_direction: 'receivable' | 'payable'; line_discount_total: string; lines?: Array<Record<string, any>>; paid_total: string; rounding_adjustment?: string; status: InvoiceStatus; supplier_id?: number | null; tax_total: string;
};

function mapInvoice(record: InvoiceRecord): Invoice {
    return { adjustmentTotal: record.adjustment_total, adjustments: record.adjustments, balanceDue: record.balance_due, businessContext: record.business_context, chargeTotal: record.charge_total, creditAdjustmentTotal: record.credit_adjustment_total, customerId: record.customer_id, debitAdjustmentTotal: record.debit_adjustment_total, documentType: record.document_type, dueDate: record.due_date, grandTotal: record.grand_total, grossTotal: record.gross_total, headerDiscountTotal: record.header_discount_total, id: record.id, invoiceDate: record.invoice_date, invoiceNumber: record.invoice_number, ledgerDirection: record.ledger_direction, lineDiscountTotal: record.line_discount_total, lines: record.lines, paidTotal: record.paid_total, roundingAdjustment: record.rounding_adjustment, status: record.status, supplierId: record.supplier_id, taxTotal: record.tax_total };
}

function payload(input: InvoiceInput) {
    const headerAdjustments = [
        { adjustmentType: 'discount', amount: input.headerDiscountTotal || '0', effect: 'deduct' as const, name: 'Header discount' },
        { adjustmentType: 'tax', amount: input.headerTaxTotal || '0', effect: 'add' as const, name: 'Header tax' },
        { adjustmentType: 'charge', amount: input.headerChargeTotal || '0', effect: 'add' as const, name: 'Header charge' },
        { adjustmentType: 'debit_adjustment', amount: input.headerDebitAdjustmentTotal || '0', effect: 'add' as const, name: 'Debit adjustment' },
        { adjustmentType: 'credit_adjustment', amount: input.headerCreditAdjustmentTotal || '0', effect: 'deduct' as const, name: 'Credit adjustment' },
    ];
    const adjustments = [...headerAdjustments, ...(input.adjustments || [])].filter((item) => Number(item.amount) > 0);

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
        rounding_adjustment: input.roundingAdjustment || '0',
        supplier_id: input.supplierId || null,
        lines: input.lines.map((line) => ({ charge_total: line.chargeTotal || '0', description: line.description, discount_total: line.discountTotal || '0', item_id: line.itemId || null, quantity: line.quantity, tax_total: line.taxTotal || '0', unit_price: line.unitPrice })),
        adjustments: adjustments.map((item) => ({ adjustment_type: item.adjustmentType, amount: item.amount, effect: item.effect, name: item.name || null })),
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
