import type { FormEvent, ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable, type DataTableColumn } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { ApiError } from '../../../services/api/apiErrors';
import type {
    Account,
    AccountFormValues,
    ApTransaction,
    ArTransaction,
    BankAccount,
    BankReconciliation,
    BankTransaction,
    Budget,
    BudgetLine,
    BudgetUsage,
    CostCenter,
    FinanceDashboardMetric,
    FinancePostingPreview,
    FiscalPeriod,
    FiscalYear,
    JournalEntry,
    JournalEntryFormValues,
    JournalEntryLine,
    PaymentTerm,
    TaxGroup,
    TaxPreviewResult,
    TaxRate,
    TaxRule,
} from '../types/finance.types';

export function FinanceDashboardCards({ metrics }: { metrics: FinanceDashboardMetric[] }) {
    return (
        <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
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

export function AccountForm({
    errors,
    initialValues,
    isSaving,
    mode = 'create',
    onSubmit,
}: {
    errors?: Record<string, string[]>;
    initialValues?: Partial<AccountFormValues>;
    isSaving?: boolean;
    mode?: 'create' | 'edit';
    onSubmit: (values: AccountFormValues) => void;
}) {
    const defaults: AccountFormValues = {
        accountCode: initialValues?.accountCode ?? '',
        accountGroup: initialValues?.accountGroup ?? '',
        accountName: initialValues?.accountName ?? '',
        accountType: initialValues?.accountType ?? 'asset',
        allowsManualPosting: initialValues?.allowsManualPosting ?? true,
        description: initialValues?.description ?? '',
        isBankAccount: initialValues?.isBankAccount ?? false,
        isCashAccount: initialValues?.isCashAccount ?? false,
        isControlAccount: initialValues?.isControlAccount ?? false,
        normalBalance: initialValues?.normalBalance ?? 'debit',
        parentId: initialValues?.parentId ?? '',
        status: initialValues?.status ?? 'active',
    };

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        onSubmit({
            accountCode: String(form.get('accountCode') ?? ''),
            accountGroup: String(form.get('accountGroup') ?? '') as AccountFormValues['accountGroup'],
            accountName: String(form.get('accountName') ?? ''),
            accountType: String(form.get('accountType') ?? 'asset') as AccountFormValues['accountType'],
            allowsManualPosting: form.get('allowsManualPosting') === 'on',
            description: String(form.get('description') ?? ''),
            isBankAccount: form.get('isBankAccount') === 'on',
            isCashAccount: form.get('isCashAccount') === 'on',
            isControlAccount: form.get('isControlAccount') === 'on',
            normalBalance: String(form.get('normalBalance') ?? 'debit') as AccountFormValues['normalBalance'],
            parentId: String(form.get('parentId') ?? ''),
            status: String(form.get('status') ?? 'active') as AccountFormValues['status'],
        });
    }

    return (
        <form className="space-y-5" onSubmit={submit}>
            <FormSection description="Chart of accounts setup only. Backend validates hierarchy, tenant scope, and posting rules." title="Account Setup">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={fieldError(errors, 'code')} label="Account code"><Input defaultValue={defaults.accountCode} name="accountCode" placeholder="1000" required /></Field>
                    <Field error={fieldError(errors, 'name')} label="Account name"><Input defaultValue={defaults.accountName} name="accountName" placeholder="Cash and Bank" required /></Field>
                    <Field error={fieldError(errors, 'type')} label="Account type">
                        <Select defaultValue={defaults.accountType} name="accountType">
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </Select>
                    </Field>
                    <Field error={fieldError(errors, 'account_group')} label="Account group"><Input defaultValue={defaults.accountGroup} name="accountGroup" placeholder="cash_bank" /></Field>
                    <Field error={fieldError(errors, 'normal_balance')} label="Normal balance">
                        <Select defaultValue={defaults.normalBalance} name="normalBalance"><option value="debit">Debit</option><option value="credit">Credit</option></Select>
                    </Field>
                    <Field error={fieldError(errors, 'parent_id')} label="Parent account id"><Input defaultValue={defaults.parentId} inputMode="numeric" name="parentId" placeholder="Optional parent id" /></Field>
                    <Field label="Status"><Select defaultValue={defaults.status} name="status"><option value="active">Active</option><option value="inactive">Inactive</option></Select></Field>
                    <BooleanField defaultChecked={defaults.isControlAccount} label="Control account" name="isControlAccount" />
                    <BooleanField defaultChecked={defaults.allowsManualPosting} label="Allows manual posting" name="allowsManualPosting" />
                    <BooleanField defaultChecked={defaults.isBankAccount} label="Bank account" name="isBankAccount" />
                    <BooleanField defaultChecked={defaults.isCashAccount} label="Cash account" name="isCashAccount" />
                    <Field className="md:col-span-3" error={fieldError(errors, 'description')} label="Description"><Textarea defaultValue={defaults.description} name="description" /></Field>
                </div>
                <FormActions isSaving={isSaving} label={mode === 'edit' ? 'Update Account' : 'Create Account'} />
            </FormSection>
        </form>
    );
}

export function JournalEntryForm({
    accounts,
    errors,
    initialValues,
    isSaving,
    mode = 'create',
    onSubmit,
}: {
    accounts: Account[];
    errors?: Record<string, string[]>;
    initialValues?: Partial<JournalEntryFormValues>;
    isSaving?: boolean;
    mode?: 'create' | 'edit';
    onSubmit: (values: JournalEntryFormValues) => void;
}) {
    const defaults: JournalEntryFormValues = {
        description: initialValues?.description ?? '',
        entryType: initialValues?.entryType ?? 'MANUAL',
        journalDate: initialValues?.journalDate ?? new Date().toISOString().slice(0, 10),
        journalNumber: initialValues?.journalNumber ?? '',
        lines: initialValues?.lines?.length ? initialValues.lines : [
            { accountId: '', credit: '', debit: '', description: '' },
            { accountId: '', credit: '', debit: '', description: '' },
        ],
        sourceModule: initialValues?.sourceModule ?? '',
        sourceReference: initialValues?.sourceReference ?? '',
        sourceType: initialValues?.sourceType ?? '',
        status: initialValues?.status ?? 'draft',
    };

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const lineIndexes = defaults.lines.map((_, index) => index);

        onSubmit({
            description: String(form.get('description') ?? ''),
            entryType: String(form.get('entryType') ?? 'MANUAL'),
            journalDate: String(form.get('journalDate') ?? ''),
            journalNumber: String(form.get('journalNumber') ?? ''),
            lines: lineIndexes.map((index) => ({
                accountId: String(form.get(`lines.${index}.accountId`) ?? ''),
                credit: String(form.get(`lines.${index}.credit`) ?? ''),
                debit: String(form.get(`lines.${index}.debit`) ?? ''),
                description: String(form.get(`lines.${index}.description`) ?? ''),
            })),
            sourceModule: String(form.get('sourceModule') ?? ''),
            sourceReference: String(form.get('sourceReference') ?? ''),
            sourceType: String(form.get('sourceType') ?? ''),
            status: String(form.get('status') ?? 'draft') as JournalEntryFormValues['status'],
        });
    }

    return (
        <form className="space-y-5" onSubmit={submit}>
            <FormSection description="Manual journal input. Backend validates period, line rules, totals, and posting eligibility." title="Journal Header">
                <div className="grid gap-4 md:grid-cols-4">
                    <Field error={fieldError(errors, 'entry_number')} label="Journal number"><Input defaultValue={defaults.journalNumber} name="journalNumber" required /></Field>
                    <Field error={fieldError(errors, 'entry_date')} label="Journal date"><Input defaultValue={defaults.journalDate} name="journalDate" required type="date" /></Field>
                    <Field label="Entry type"><Select defaultValue={defaults.entryType} name="entryType"><option value="MANUAL">Manual</option><option value="ADJUSTMENT">Adjustment</option><option value="OPENING">Opening</option></Select></Field>
                    <Field label="Status"><Select defaultValue={defaults.status} name="status"><option value="draft">Draft</option></Select></Field>
                    <Field className="md:col-span-2" label="Source module"><Input defaultValue={defaults.sourceModule} name="sourceModule" placeholder="Optional source module" /></Field>
                    <Field label="Source type"><Input defaultValue={defaults.sourceType} name="sourceType" placeholder="Optional source type" /></Field>
                    <Field label="Source reference"><Input defaultValue={defaults.sourceReference} name="sourceReference" placeholder="Optional source reference" /></Field>
                    <Field className="md:col-span-4" error={fieldError(errors, 'description')} label="Description"><Textarea defaultValue={defaults.description} name="description" /></Field>
                </div>
            </FormSection>
            <FormSection description="Enter debit or credit per line. Backend returns the authoritative balance and posting preview." title="Journal Lines">
                <div className="space-y-4">
                    {defaults.lines.map((line, index) => (
                        <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-3 md:grid-cols-5" key={`journal-line-${index}-${line.accountId}-${line.debit}-${line.credit}`}>
                            <Field error={fieldError(errors, `lines.${index}.account_id`)} label={`Line ${index + 1} account`}>
                                <Select defaultValue={line.accountId} name={`lines.${index}.accountId`} required>
                                    <option value="">Select account</option>
                                    {accounts.map((account) => <option key={account.id} value={account.id}>{account.accountCode} - {account.accountName}</option>)}
                                </Select>
                            </Field>
                            <Field error={fieldError(errors, `lines.${index}.debit_amount`)} label="Debit"><Input defaultValue={line.debit} min="0" name={`lines.${index}.debit`} step="0.0001" type="number" /></Field>
                            <Field error={fieldError(errors, `lines.${index}.credit_amount`)} label="Credit"><Input defaultValue={line.credit} min="0" name={`lines.${index}.credit`} step="0.0001" type="number" /></Field>
                            <Field className="md:col-span-2" label="Description"><Input defaultValue={line.description} name={`lines.${index}.description`} /></Field>
                        </div>
                    ))}
                </div>
                <FormActions isSaving={isSaving} label={mode === 'edit' ? 'Update Draft Journal' : 'Save Draft Journal'} />
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
                    <p className="mt-2 text-sm text-slate-500">Normal balance: {account.normalBalance}. Current balances are returned by backend read models.</p>
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
                { header: 'Parent', key: 'parentAccount', render: (row) => row.parentAccount || 'Root account' },
                { header: 'Manual Posting', key: 'allowsManualPosting', render: (row) => row.allowsManualPosting ? 'Allowed' : 'Blocked' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <Link to={`/finance/accounts/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function AccountLedgerTable({ rows }: { rows: JournalEntry[] }) {
    if (!rows.length) {
        return <EmptyState description="No journal entries reference this account in the current backend response." title="No ledger rows" />;
    }

    return <SimpleTable rows={rows} columns={[['journalNumber', 'Journal #'], ['journalDate', 'Date'], ['description', 'Description'], ['sourceReference', 'Source'], ['status', 'Status']]} />;
}

export function JournalEntryLineTable({ rows }: { rows: JournalEntryLine[] }) {
    return <SimpleTable rows={rows} columns={[['account', 'Account'], ['debit', 'Debit'], ['credit', 'Credit'], ['costCenter', 'Cost Center'], ['taxRate', 'Tax'], ['party', 'Party'], ['description', 'Description']]} />;
}

export function JournalPostingPreviewPanel({ preview }: { preview?: FinancePostingPreview }) {
    if (!preview) {
        return <EmptyState description="Run backend posting preview to see totals, balance status, warnings, and eligibility." title="No posting preview yet" />;
    }

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

export function JournalStatusActionPanel({
    journal,
    onPost,
    onPreview,
    onReverse,
}: {
    journal: JournalEntry;
    onPost: () => void;
    onPreview: () => void;
    onReverse: () => void;
}) {
    const isDraft = journal.status === 'draft';
    const isPosted = journal.status === 'posted';

    return (
        <PreviewPanel
            rows={[
                { label: 'Journal', value: journal.journalNumber },
                { label: 'Current status', value: journal.status },
                { label: 'Allowed actions', value: isDraft ? 'Preview / Post' : isPosted ? 'Reverse' : 'No actions currently available' },
            ]}
            status="Workflow"
            title="Journal Status Actions"
        >
            <div className="flex flex-wrap gap-2">
                <Button disabled={!isDraft} onClick={onPreview} title={isDraft ? undefined : 'Only draft journals can be previewed for posting.'} variant="blue">Preview Posting</Button>
                <Button disabled={!isDraft} onClick={onPost} title={isDraft ? undefined : 'Only draft journals can be posted.'} variant="secondary">Post</Button>
                <Button disabled={!isPosted} onClick={onReverse} title={isPosted ? undefined : 'Only posted journals can be reversed.'} variant="ghost">Reverse</Button>
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

export function TaxPreviewPanel({ preview }: { preview?: TaxPreviewResult }) {
    if (!preview) {
        return <EmptyState description="Submit a tax preview request to receive the backend calculated tax result." title="No tax preview yet" />;
    }

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

export function BankTransactionTable({ rows }: { rows: BankTransaction[] }) {
    return <SimpleTable rows={rows} columns={[['date', 'Date'], ['bankAccount', 'Bank Account'], ['reference', 'Reference'], ['type', 'Type'], ['amount', 'Amount'], ['reconciliationStatus', 'Reconciliation']]} />;
}

export function BankReconciliationPanel({ rows }: { rows: BankReconciliation[] }) {
    return <SimpleTable rows={rows} columns={[['bankAccount', 'Bank Account'], ['period', 'Period'], ['variance', 'Backend Variance'], ['status', 'Status']]} />;
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

export function FinanceSourceReferencePanel({ sourceModule, sourceReference, sourceType }: { sourceModule?: string; sourceReference?: string; sourceType?: string }) {
    return <PreviewPanel rows={[{ label: 'Source module', value: sourceModule || 'Manual / none' }, { label: 'Source type', value: sourceType || 'Not linked' }, { label: 'Source reference', value: sourceReference || 'Not linked' }]} status="Readonly" title="Source Reference" />;
}

export function ReferenceTable<T extends { id: string }>({ columns, rows }: { columns: Array<[keyof T & string, string]>; rows: T[] }) {
    return <SimpleTable rows={rows} columns={columns} />;
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

export function ApiErrorBanner({ error }: { error?: Error | string | null }) {
    if (!error) {
        return null;
    }

    const message = typeof error === 'string' ? error : error.message;

    return <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{message}</div>;
}

export function apiFieldErrors(error: unknown): Record<string, string[]> {
    return error instanceof ApiError ? error.errors : {};
}

function SimpleTable<T extends { id: string }>({ columns, rows }: { columns: Array<[keyof T & string, string]>; rows: T[] }) {
    if (!rows.length) {
        return <EmptyState description="No records returned by the backend for the current filters." title="No records" />;
    }

    const tableColumns: Array<DataTableColumn<T>> = columns.map(([key, header]) => ({
        header,
        key,
        render: (row) => key.toLowerCase().includes('status') ? <StatusBadge status={String(row[key] ?? '')} /> : readableValue(row[key]),
    }));

    return <DataTable columns={tableColumns} getRowKey={(row) => row.id} rows={rows} />;
}

function readableValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return 'Not set';
    }

    return String(value);
}

function Field({ children, className = '', error, label }: { children: ReactNode; className?: string; error?: string; label: string }) {
    return (
        <label className={`space-y-2 text-sm ${className}`}>
            <span className="font-semibold text-slate-700">{label}</span>
            {children}
            {error ? <span className="block text-xs font-semibold text-red-600">{error}</span> : null}
        </label>
    );
}

function BooleanField({ defaultChecked, label, name }: { defaultChecked: boolean; label: string; name: string }) {
    return (
        <label className="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50/60 px-3 text-sm font-semibold text-slate-700">
            <Checkbox defaultChecked={defaultChecked} name={name} />
            {label}
        </label>
    );
}

function FormActions({ isSaving, label }: { isSaving?: boolean; label: string }) {
    return (
        <div className="mt-4 flex justify-end gap-3">
            <Link to=".."><Button variant="secondary">Cancel</Button></Link>
            <Button disabled={isSaving} type="submit" variant="blue">{isSaving ? 'Saving...' : label}</Button>
        </div>
    );
}

function fieldError(errors: Record<string, string[]> | undefined, field: string): string | undefined {
    return errors?.[field]?.[0];
}
