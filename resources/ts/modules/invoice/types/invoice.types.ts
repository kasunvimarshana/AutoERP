export type InvoiceStatus = 'draft' | 'issued' | 'partially_paid' | 'paid' | 'cancelled' | 'credited';
export type InvoiceDocumentType = 'invoice' | 'purchase_invoice' | 'debit_adjustment' | 'credit_adjustment' | 'refund' | 'reversal' | 'write_off';
export type LedgerDirection = 'receivable' | 'payable';

export type InvoiceLineInput = { chargeTotal?: string; description: string; discountTotal?: string; itemId?: number; quantity: string; taxTotal: string; unitPrice: string };
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
    roundingAdjustment?: string;
    supplierId?: number;
    headerChargeTotal?: string;
    headerCreditAdjustmentTotal?: string;
    headerDebitAdjustmentTotal?: string;
    headerDiscountTotal?: string;
    headerTaxTotal?: string;
    adjustments?: InvoiceAdjustmentInput[];
};

export type Invoice = {
    balanceDue: string;
    businessContext: string;
    customerId?: number | null;
    documentType: InvoiceDocumentType;
    dueDate?: string | null;
    grossTotal: string;
    lineDiscountTotal: string;
    headerDiscountTotal: string;
    taxTotal: string;
    chargeTotal: string;
    debitAdjustmentTotal: string;
    creditAdjustmentTotal: string;
    adjustmentTotal: string;
    grandTotal: string;
    id: number;
    invoiceDate: string;
    invoiceNumber: string;
    ledgerDirection: LedgerDirection;
    lines?: Array<Record<string, any>>;
    adjustments?: Array<Record<string, any>>;
    paidTotal: string;
    roundingAdjustment?: string;
    status: InvoiceStatus;
    supplierId?: number | null;
};

export type InvoicePage = { invoices: Invoice[]; meta: { currentPage: number; lastPage: number; perPage: number; total: number } };
