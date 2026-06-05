export type InvoiceStatus = 'draft' | 'issued' | 'partially_paid' | 'paid' | 'cancelled' | 'credited';
export type InvoiceDocumentType = 'invoice' | 'debit_adjustment' | 'credit_adjustment' | 'refund' | 'reversal' | 'write_off';
export type LedgerDirection = 'receivable' | 'payable';

export type InvoiceLineInput = { description: string; itemId?: number; quantity: string; taxTotal: string; unitPrice: string };
export type InvoiceAdjustmentInput = { adjustmentType: string; amount: string; effect: 'add' | 'deduct'; name?: string };
export type InvoiceInput = {
    balanceEffect?: 'increase' | 'decrease' | 'none';
    businessContext: string;
    customerId?: number;
    documentType: InvoiceDocumentType;
    dueDate?: string;
    invoiceDate: string;
    invoiceNumber?: string;
    ledgerDirection: LedgerDirection;
    lines: InvoiceLineInput[];
    notes?: string;
    originalInvoiceId?: number;
    supplierId?: number;
    adjustments?: InvoiceAdjustmentInput[];
};

export type Invoice = {
    balanceDue: string;
    businessContext: string;
    customerId?: number | null;
    documentType: InvoiceDocumentType;
    dueDate?: string | null;
    grandTotal: string;
    id: number;
    invoiceDate: string;
    invoiceNumber: string;
    ledgerDirection: LedgerDirection;
    paidTotal: string;
    status: InvoiceStatus;
    supplierId?: number | null;
};

export type InvoicePage = { invoices: Invoice[]; meta: { currentPage: number; lastPage: number; perPage: number; total: number } };
