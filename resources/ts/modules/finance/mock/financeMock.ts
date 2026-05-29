import type { Account, ApTransaction, ArTransaction, BankAccount, BankReconciliation, BankTransaction, Budget, CostCenter, FinanceAuditEntry, FinancePostingPreview, FiscalPeriod, FiscalYear, JournalEntry, PaymentTerm, TaxGroup, TaxPreviewResult, TaxRate, TaxRule } from '../types/finance.types';

export const financeDashboardMetrics = [
    { label: 'Draft journals', value: '12', status: 'draft' },
    { label: 'Posted journals', value: '248', status: 'posted' },
    { label: 'AP outstanding', value: 'Backend provided', status: 'warning' },
    { label: 'AR outstanding', value: 'Backend provided', status: 'info' },
    { label: 'Unreconciled bank txns', value: '17', status: 'pending' },
    { label: 'Budgets summary', value: 'Backend provided', status: 'active' },
];

export const accounts: Account[] = [
    { accountCode: '1000', accountName: 'Cash and Bank', accountType: 'asset', id: 'acc-001', normalBalance: 'debit', status: 'active', updatedAt: '2026-05-29' },
    { accountCode: '1100', accountName: 'Accounts Receivable', accountType: 'asset', id: 'acc-002', normalBalance: 'debit', parentAccount: 'Cash and Bank', status: 'active', updatedAt: '2026-05-29' },
    { accountCode: '2000', accountName: 'Accounts Payable', accountType: 'liability', id: 'acc-003', normalBalance: 'credit', status: 'active', updatedAt: '2026-05-28' },
    { accountCode: '4000', accountName: 'Sales Income', accountType: 'income', id: 'acc-004', normalBalance: 'credit', status: 'active', updatedAt: '2026-05-27' },
    { accountCode: '5000', accountName: 'Cost of Goods Sold', accountType: 'expense', id: 'acc-005', normalBalance: 'debit', status: 'active', updatedAt: '2026-05-27' },
    { accountCode: '1300', accountName: 'Inventory Asset', accountType: 'asset', id: 'acc-006', normalBalance: 'debit', status: 'active', updatedAt: '2026-05-27' },
];

export const fiscalYears: FiscalYear[] = [
    { endDate: '2026-12-31', id: 'fy-2026', name: 'FY 2026', startDate: '2026-01-01', status: 'open' },
];

export const fiscalPeriods: FiscalPeriod[] = [
    { endDate: '2026-05-31', fiscalYear: 'FY 2026', id: 'fp-2026-05', name: 'May 2026', startDate: '2026-05-01', status: 'open' },
    { endDate: '2026-04-30', fiscalYear: 'FY 2026', id: 'fp-2026-04', name: 'April 2026', startDate: '2026-04-01', status: 'closed' },
];

export const journalEntries: JournalEntry[] = [
    { currency: 'LKR', description: 'Payment receipt posting draft', id: 'je-001', journalDate: '2026-05-29', journalNumber: 'JE-2026-00421', lines: [{ account: 'Cash and Bank', credit: 'Backend preview', debit: 'Backend preview', description: 'Cash impact', id: 'jel-001' }, { account: 'Accounts Receivable', credit: 'Backend preview', debit: 'Backend preview', description: 'AR impact', id: 'jel-002' }], reference: 'RCPT-2026-00041', sourceModule: 'payment', sourceReference: 'RCPT-2026-00041', status: 'draft' },
    { currency: 'LKR', description: 'Inventory valuation posting', id: 'je-002', journalDate: '2026-05-28', journalNumber: 'JE-2026-00420', lines: [{ account: 'Inventory Asset', credit: 'Backend posted', debit: 'Backend posted', description: 'Inventory impact', id: 'jel-003' }], reference: 'MOV-2026-00101', sourceModule: 'inventory', sourceReference: 'MOV-2026-00101', status: 'posted' },
    { currency: 'LKR', description: 'Reversal journal', id: 'je-003', journalDate: '2026-05-27', journalNumber: 'JE-2026-00419', lines: [], reference: 'REV-001', status: 'reversed' },
];

export const apTransactions: ApTransaction[] = [
    { agingBucket: 'Backend aging', dueDate: '2026-06-10', id: 'ap-001', originalAmount: 'Backend amount', outstandingAmount: 'Backend balance', paidAmount: 'Backend paid', party: 'Prime Auto Parts', sourceDocument: 'PINV-2026-00072', status: 'open' },
];
export const arTransactions: ArTransaction[] = [
    { agingBucket: 'Backend aging', dueDate: '2026-06-05', id: 'ar-001', originalAmount: 'Backend amount', outstandingAmount: 'Backend balance', paidAmount: 'Backend paid', party: 'Kavinda Motors', sourceDocument: 'SINV-2026-00119', status: 'open' },
];

export const taxGroups: TaxGroup[] = [{ code: 'VAT', id: 'taxg-001', name: 'VAT Standard', status: 'active' }];
export const taxRates: TaxRate[] = [{ code: 'VAT18', effectiveFrom: '2026-01-01', id: 'taxr-001', name: 'VAT 18%', rate: 'Backend rate', status: 'active' }];
export const taxRules: TaxRule[] = [{ appliesTo: 'Sales, service, rental, purchase where configured', id: 'taxrule-001', name: 'Default VAT rule', priority: 'Backend priority', status: 'active', taxGroup: 'VAT Standard', taxRate: 'VAT 18%' }];

export const taxPreview: TaxPreviewResult = {
    breakdown: [{ label: 'Taxable amount', value: 'Backend preview' }, { label: 'Tax rule', value: 'Backend preview' }],
    calculated: { appliedRule: 'Backend selected rule', taxAmount: 'Backend calculated', taxableAmount: 'Backend input' },
    errors: [],
    input: {},
    warnings: ['Tax preview is backend/mock only.'],
};

export const paymentTerms: PaymentTerm[] = [{ code: 'NET30', dueDays: '30', id: 'pt-001', name: 'Net 30', status: 'active' }];
export const costCenters: CostCenter[] = [{ code: 'SVC', id: 'cc-001', name: 'Vehicle Service', status: 'active' }, { code: 'RNT', id: 'cc-002', name: 'Vehicle Rental', status: 'active' }];
export const bankAccounts: BankAccount[] = [{ accountName: 'Main Current Account', accountNumber: '****4421', bankName: 'Commercial Bank', currency: 'LKR', id: 'bank-001', status: 'active' }];
export const bankTransactions: BankTransaction[] = [{ amount: 'Backend amount', bankAccount: 'Main Current Account', date: '2026-05-29', id: 'btx-001', reference: 'BANK/TRN/8891', reconciliationStatus: 'unreconciled', type: 'credit' }];
export const reconciliations: BankReconciliation[] = [{ bankAccount: 'Main Current Account', id: 'rec-001', period: 'May 2026', status: 'draft', variance: 'Backend variance' }];
export const budgets: Budget[] = [{ fiscalYear: 'FY 2026', id: 'bud-001', lines: [{ account: 'Service Expense', budgetAmount: 'Backend budget', id: 'budl-001', usage: 'Backend usage', variance: 'Backend variance' }], name: 'Operating Budget', status: 'active', variance: 'Backend variance' }];

export const postingPreview: FinancePostingPreview = {
    breakdown: [{ label: 'Total debit', value: 'Backend calculated' }, { label: 'Total credit', value: 'Backend calculated' }, { label: 'Balanced', value: 'Backend decision' }],
    calculated: { balanced: 'Backend decision', eligibility: 'Backend decision', totalCredit: 'Backend calculated', totalDebit: 'Backend calculated' },
    errors: [],
    input: {},
    journalLines: [
        { account: 'Cash and Bank', credit: 'Backend calculated', debit: 'Backend calculated', description: 'Payment side' },
        { account: 'Accounts Receivable / Payable', credit: 'Backend calculated', debit: 'Backend calculated', description: 'Settlement side' },
    ],
    warnings: ['Posting preview is backend/mock only.'],
};

export const financeActivity: FinanceAuditEntry[] = [
    { actor: 'System', description: 'Posting preview requested.', id: 'act-001', time: '2026-05-29 09:10', type: 'preview' },
    { actor: 'Finance Manager', description: 'Journal posted through backend engine.', id: 'act-002', time: '2026-05-29 09:30', type: 'posted' },
];

export function getAccountById(id: string) {
    return accounts.find((account) => account.id === id) ?? accounts[0];
}

export function getJournalById(id: string) {
    return journalEntries.find((journal) => journal.id === id) ?? journalEntries[0];
}
