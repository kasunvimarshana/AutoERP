import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable, type DataTableColumn } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import type {
    Account,
    ApTransaction,
    ArTransaction,
    BankAccount,
    BankReconciliation,
    BankTransaction,
    Budget,
    BudgetLine,
    BudgetUsage,
    CostCenter,
    FinanceAuditEntry,
    FinancePostingPreview,
    FiscalPeriod,
    FiscalYear,
    JournalEntry,
    JournalEntryLine,
    PaymentTerm,
    TaxGroup,
    TaxPreviewResult,
    TaxRate,
    TaxRule,
} from '../types/finance.types';

export function FinanceDashboardCards({ metrics }: { metrics: Array<{ label: string; status: string; value: string }> }) {
    return (
        <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
            {metrics.map((metric) => (
                <Card className="p-4" key={metric.label}>
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{metric.label}</p>
                            <p className="mt-3 text-2xl font-bold text-slate-950">{metric.value}</p>
                        </div>
                        <StatusBadge status={metric.status} />
                    </div>
                </Card>
            ))}
        </div>
    );
}

export function AccountForm({ mode = 'create' }: { mode?: 'create' | 'edit' }) {
    return (
        <form className="space-y-5">
            <FormSection description="Chart of accounts setup only. Backend validates hierarchy and posting rules." title="Account Setup">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Account code"><Input placeholder="1000" /></Field>
                    <Field label="Account name"><Input placeholder="Cash and Bank" /></Field>
                    <Field label="Account type"><Select><option>Asset</option><option>Liability</option><option>Equity</option><option>Income</option><option>Expense</option></Select></Field>
                    <Field label="Account group"><Input placeholder="cash_bank, receivables, payables..." /></Field>
                    <Field label="Normal balance"><Select><option>Debit</option><option>Credit</option></Select></Field>
                    <Field label="Parent account"><Input placeholder="Optional parent" /></Field>
                    <Field label="Status"><Select><option>Active</option><option>Inactive</option></Select></Field>
                </div>
            </FormSection>
            <FormSection description="Opening balances are submitted for backend validation and posting. Frontend does not calculate account balances." title="Opening Balance / Mapping">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Opening balance"><Input placeholder="Backend validated amount" /></Field>
                    <Field label="Currency"><Input placeholder="LKR" /></Field>
                    <Field label="Usage mapping"><Input placeholder="AR, AP, inventory, bank..." /></Field>
                </div>
                <div className="mt-4 flex justify-end gap-3"><Button variant="secondary">Cancel</Button><Button variant="blue">{mode === 'edit' ? 'Update Account' : 'Create Account'}</Button></div>
            </FormSection>
        </form>
    );
}

export function AccountSummaryCard({ account }: { account: Account }) {
    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{account.accountType}</p>
                    <h2 className="mt-1 text-2xl font-bold text-slate-950">{account.accountCode} - {account.accountName}</h2>
                    <p className="mt-2 text-sm text-slate-500">Normal balance: {account.normalBalance}. Current balance is backend-owned.</p>
                </div>
                <StatusBadge status={account.status} />
            </div>
        </Card>
    );
}

export function AccountTreeView({ rows }: { rows: Account[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Account', key: 'accountName', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/finance/accounts/${row.id}`}>{row.accountCode} - {row.accountName}</Link> },
                { header: 'Type', key: 'accountType' },
                { header: 'Normal Balance', key: 'normalBalance' },
                { header: 'Parent', key: 'parentAccount' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Updated', key: 'updatedAt' },
                { header: 'Actions', key: 'actions', render: (row) => <div className="flex gap-2"><Link to={`/finance/accounts/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link><Button variant="ghost">Ledger</Button></div> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function AccountLedgerTable({ rows }: { rows: JournalEntry[] }) {
    return <SimpleTable rows={rows} columns={[['journalNumber', 'Journal #'], ['journalDate', 'Date'], ['description', 'Description'], ['sourceReference', 'Source'], ['status', 'Status']]} />;
}

export function FiscalYearForm() {
    return <CompactForm title="Fiscal Year" fields={['Name', 'Start date', 'End date', 'Status']} />;
}

export function FiscalPeriodForm() {
    return <CompactForm title="Fiscal Period" fields={['Fiscal year', 'Period name', 'Start date', 'End date', 'Status']} />;
}

export function JournalEntryForm() {
    return (
        <form className="space-y-5">
            <FormSection description="Manual journal input. Backend validates fiscal period, balance, tax, and posting eligibility." title="Journal Header">
                <div className="grid gap-4 md:grid-cols-4">
                    <Field label="Journal date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Reference number"><Input placeholder="Reference" /></Field>
                    <Field label="Currency"><Input placeholder="LKR" /></Field>
                    <Field label="Source module optional"><Input placeholder="payment, inventory, voucher..." /></Field>
                    <Field label="Description"><Textarea placeholder="Journal description" /></Field>
                    <Field label="Source reference optional"><Input placeholder="Document or event reference" /></Field>
                </div>
            </FormSection>
            <FormSection description="Debit/credit totals and balance decision come from backend preview." title="Journal Lines">
                <JournalEntryLineTable rows={[{ account: 'Select account', credit: 'Input only', debit: 'Input only', description: 'Line description', id: 'draft-line' }]} />
            </FormSection>
            <JournalPostingPreviewPanel preview={{
                breakdown: [],
                calculated: { balanced: 'Backend decision', eligibility: 'Backend decision', totalCredit: 'Backend calculated', totalDebit: 'Backend calculated' },
                errors: [],
                input: {},
                journalLines: [],
                warnings: ['Click preview to request backend posting validation.'],
            }} />
            <div className="flex justify-end gap-3"><Button variant="secondary">Save Draft</Button><Button variant="blue">Preview Posting</Button></div>
        </form>
    );
}

export function JournalEntryLineTable({ rows }: { rows: JournalEntryLine[] }) {
    return <SimpleTable rows={rows} columns={[['account', 'Account'], ['debit', 'Debit'], ['credit', 'Credit'], ['costCenter', 'Cost Center'], ['taxRate', 'Tax'], ['party', 'Party'], ['description', 'Description']]} />;
}

export function JournalPostingPreviewPanel({ preview }: { preview: FinancePostingPreview }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Total debit', value: preview.calculated.totalDebit },
                { label: 'Total credit', value: preview.calculated.totalCredit },
                { label: 'Balanced?', value: preview.calculated.balanced },
                { label: 'Posting eligibility', value: preview.calculated.eligibility },
            ]}
            status="Backend Preview"
            subtitle="Readonly posting preview. Frontend does not calculate debit totals, credit totals, balance, or posting impact."
            title="Journal Posting Preview"
        />
    );
}

export function JournalStatusActionPanel({ journalId, status }: { journalId: string; status: string }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Journal', value: journalId },
                { label: 'Current status', value: status },
                { label: 'Allowed actions', value: 'Backend permission/status result' },
            ]}
            status="Workflow"
            title="Journal Status Actions"
        >
            <div className="flex flex-wrap gap-2">
                <Button variant="blue">Preview Posting</Button>
                <Button variant="secondary">Post</Button>
                <Button variant="ghost">Reverse</Button>
            </div>
        </PreviewPanel>
    );
}

export function ApTransactionTable({ rows }: { rows: ApTransaction[] }) {
    return <SimpleTable rows={rows} columns={[['party', 'Supplier / Party'], ['sourceDocument', 'Document'], ['originalAmount', 'Original'], ['paidAmount', 'Paid'], ['outstandingAmount', 'Outstanding'], ['dueDate', 'Due'], ['agingBucket', 'Aging'], ['status', 'Status']]} />;
}

export function ArTransactionTable({ rows }: { rows: ArTransaction[] }) {
    return <SimpleTable rows={rows} columns={[['party', 'Customer / Party'], ['sourceDocument', 'Document'], ['originalAmount', 'Original'], ['paidAmount', 'Paid'], ['outstandingAmount', 'Outstanding'], ['dueDate', 'Due'], ['agingBucket', 'Aging'], ['status', 'Status']]} />;
}

export function TaxGroupForm() {
    return <CompactForm title="Tax Group" fields={['Code', 'Name', 'Status']} />;
}

export function TaxRateForm() {
    return <CompactForm title="Tax Rate" fields={['Code', 'Name', 'Rate', 'Effective from', 'Status']} />;
}

export function TaxRuleForm() {
    return <CompactForm title="Tax Rule" fields={['Name', 'Tax group', 'Tax rate', 'Applies to', 'Priority', 'Status']} />;
}

export function TaxPreviewPanel({ preview }: { preview: TaxPreviewResult }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Taxable amount', value: preview.calculated.taxableAmount },
                { label: 'Applied rule', value: preview.calculated.appliedRule },
                { label: 'Tax amount', value: preview.calculated.taxAmount },
            ]}
            status="Backend Preview"
            subtitle="Readonly tax preview. Frontend does not calculate tax amounts or rule priority."
            title="Tax Preview"
        />
    );
}

export function PaymentTermForm() {
    return <CompactForm title="Payment Term" fields={['Code', 'Name', 'Due days', 'Status']} />;
}

export function CostCenterForm() {
    return <CompactForm title="Cost Center" fields={['Code', 'Name', 'Status']} />;
}

export function BankAccountForm() {
    return <CompactForm title="Bank Account" fields={['Bank name', 'Account name', 'Account number', 'Currency', 'Status']} />;
}

export function BankTransactionTable({ rows }: { rows: BankTransaction[] }) {
    return <SimpleTable rows={rows} columns={[['date', 'Date'], ['bankAccount', 'Bank Account'], ['reference', 'Reference'], ['type', 'Type'], ['amount', 'Amount'], ['reconciliationStatus', 'Reconciliation']]} />;
}

export function BankReconciliationPanel({ rows }: { rows: BankReconciliation[] }) {
    return <SimpleTable rows={rows} columns={[['bankAccount', 'Bank Account'], ['period', 'Period'], ['variance', 'Backend Variance'], ['status', 'Status']]} />;
}

export function BudgetForm() {
    return <CompactForm title="Budget" fields={['Budget name', 'Fiscal year', 'Status']} />;
}

export function BudgetLineTable({ rows }: { rows: BudgetLine[] }) {
    return <SimpleTable rows={rows} columns={[['account', 'Account'], ['budgetAmount', 'Budget'], ['usage', 'Backend Usage'], ['variance', 'Backend Variance']]} />;
}

export function BudgetUsagePanel({ rows }: { rows: BudgetUsage[] }) {
    return (
        <PreviewPanel status="Backend Read Model" title="Budget Usage / Variance">
            <SimpleTable rows={rows} columns={[['budgetAmount', 'Budget'], ['usedAmount', 'Backend Used'], ['varianceAmount', 'Backend Variance']]} />
        </PreviewPanel>
    );
}

export function FinanceSourceReferencePanel({ sourceModule, sourceReference }: { sourceModule?: string; sourceReference?: string }) {
    return <PreviewPanel rows={[{ label: 'Source module', value: sourceModule ?? 'Manual / none' }, { label: 'Source reference', value: sourceReference ?? 'Not linked' }]} status="Readonly" title="Source Reference" />;
}

export function FinanceActivityTimeline({ rows }: { rows: FinanceAuditEntry[] }) {
    return (
        <div className="space-y-3">
            {rows.map((entry) => (
                <div className="rounded-lg border border-slate-200 bg-white p-4" key={entry.id}>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold text-slate-950">{entry.description}</p>
                            <p className="mt-1 text-xs font-bold uppercase tracking-wide text-slate-400">{entry.actor} - {entry.type}</p>
                        </div>
                        <span className="text-xs text-slate-400">{entry.time}</span>
                    </div>
                </div>
            ))}
        </div>
    );
}

export function TaxRuleTable({ rows }: { rows: TaxRule[] }) {
    return <SimpleTable rows={rows} columns={[['name', 'Rule'], ['taxGroup', 'Group'], ['taxRate', 'Rate'], ['appliesTo', 'Applies To'], ['priority', 'Priority'], ['status', 'Status']]} />;
}

export function TaxGroupTable({ rows }: { rows: TaxGroup[] }) {
    return <SimpleTable rows={rows} columns={[['code', 'Code'], ['name', 'Name'], ['status', 'Status']]} />;
}

export function TaxRateTable({ rows }: { rows: TaxRate[] }) {
    return <SimpleTable rows={rows} columns={[['code', 'Code'], ['name', 'Name'], ['rate', 'Backend Rate'], ['effectiveFrom', 'Effective From'], ['status', 'Status']]} />;
}

export function PaymentTermTable({ rows }: { rows: PaymentTerm[] }) {
    return <SimpleTable rows={rows} columns={[['code', 'Code'], ['name', 'Name'], ['dueDays', 'Due Days'], ['status', 'Status']]} />;
}

export function CostCenterTable({ rows }: { rows: CostCenter[] }) {
    return <SimpleTable rows={rows} columns={[['code', 'Code'], ['name', 'Name'], ['status', 'Status']]} />;
}

export function BankAccountTable({ rows }: { rows: BankAccount[] }) {
    return <SimpleTable rows={rows} columns={[['bankName', 'Bank'], ['accountName', 'Account'], ['accountNumber', 'Number'], ['currency', 'Currency'], ['status', 'Status']]} />;
}

export function BudgetTable({ rows }: { rows: Budget[] }) {
    return <SimpleTable rows={rows} columns={[['name', 'Budget'], ['fiscalYear', 'Fiscal Year'], ['variance', 'Backend Variance'], ['status', 'Status']]} />;
}

export function FiscalYearTable({ rows }: { rows: FiscalYear[] }) {
    return <SimpleTable rows={rows} columns={[['name', 'Year'], ['startDate', 'Start'], ['endDate', 'End'], ['status', 'Status']]} />;
}

export function FiscalPeriodTable({ rows }: { rows: FiscalPeriod[] }) {
    return <SimpleTable rows={rows} columns={[['name', 'Period'], ['fiscalYear', 'Fiscal Year'], ['startDate', 'Start'], ['endDate', 'End'], ['status', 'Status']]} />;
}

function CompactForm({ fields, title }: { fields: string[]; title: string }) {
    return (
        <FormSection description="Captured values are submitted to backend validation. Calculated balances and effects stay backend-owned." title={title}>
            <div className="grid gap-4 md:grid-cols-3">
                {fields.map((field) => <Field key={field} label={field}><Input placeholder={field} /></Field>)}
            </div>
            <div className="mt-4 flex justify-end"><Button variant="blue">Save {title}</Button></div>
        </FormSection>
    );
}

function SimpleTable<T extends { id: string }>({ columns, rows }: { columns: Array<[keyof T & string, string]>; rows: T[] }) {
    const tableColumns: Array<DataTableColumn<T>> = columns.map(([key, header]) => ({
        header,
        key,
        render: (row) => key.toLowerCase().includes('status') ? <StatusBadge status={String(row[key] ?? '')} /> : String(row[key] ?? ''),
    }));

    return <DataTable columns={tableColumns} getRowKey={(row) => row.id} rows={rows} />;
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return (
        <label className="space-y-2 text-sm">
            <span className="font-semibold text-slate-700">{label}</span>
            {children}
        </label>
    );
}
