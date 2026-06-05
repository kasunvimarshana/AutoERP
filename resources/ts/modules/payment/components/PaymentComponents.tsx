import { FormEvent, ReactNode, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { DataTable, type DataTableColumn } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { paymentApi } from '../services/paymentApi';
import type {
    AdvancePayment,
    CashRegister,
    CheckPayment,
    Payment,
    PaymentAllocation,
    PaymentAuditEntry,
    PaymentFormInput,
    PaymentGroup,
    PaymentMethod,
    PaymentMethodFormInput,
    PaymentPostingPreview,
    PaymentSourceReference,
    Refund,
    WriteOff,
} from '../types/payment.types';

function apiError(error: unknown, fallback: string) {
    if (error instanceof ApiError) return { errors: error.errors, message: error.message };
    return { errors: {}, message: error instanceof Error ? error.message : fallback };
}

function field(formData: FormData, key: string) {
    return String(formData.get(key) ?? '').trim();
}

function today() {
    return new Date().toISOString().slice(0, 10);
}

export function PaymentSummaryCard({ onChanged, payment }: { onChanged?: () => void; payment: Payment }) {
    const [isSaving, setIsSaving] = useState(false);
    const [message, setMessage] = useState('');

    async function run(action: 'post' | 'reverse' | 'refund' | 'void') {
        setIsSaving(true);
        setMessage('');
        try {
            if (action === 'post') await paymentApi.postPayment(payment.id);
            if (action === 'reverse') await paymentApi.reversePayment(payment.id, { reason: 'Requested from payment detail.' });
            if (action === 'refund') await paymentApi.refundPayment(payment.id, { reason: 'Requested from payment detail.' });
            if (action === 'void') await paymentApi.voidPayment(payment.id);
            setMessage(`${action} completed by backend.`);
            onChanged?.();
        } catch (caught) {
            setMessage(apiError(caught, `Unable to ${action} payment.`).message);
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{payment.direction.replaceAll('_', ' ')}</p>
                    <h2 className="mt-1 text-2xl font-bold text-slate-950">{payment.paymentNumber}</h2>
                    <p className="mt-2 text-sm text-slate-500">{payment.party} - {payment.methodName} - {payment.paymentDate}</p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <StatusBadge status={payment.status} />
                    <Button disabled={isSaving || payment.status !== 'draft'} onClick={() => void run('post')} variant="secondary">Post</Button>
                    <Button disabled={isSaving || !['posted', 'reconciled'].includes(payment.status)} onClick={() => void run('reverse')} variant="secondary">Reverse</Button>
                    <Button disabled={isSaving || payment.status === 'voided'} onClick={() => void run('refund')} variant="secondary">Refund</Button>
                    <Button disabled={isSaving || payment.status !== 'draft'} onClick={() => void run('void')} variant="ghost">Void</Button>
                </div>
            </div>
            {message ? <p className="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">{message}</p> : null}
            <div className="mt-5 grid gap-3 md:grid-cols-4">
                {[
                    ['Amount', `${payment.currency} ${payment.amount}`],
                    ['Allocated', payment.allocatedAmount],
                    ['Unallocated', payment.unallocatedAmount],
                    ['Source', payment.sourceReference || 'No source reference'],
                ].map(([label, value]) => (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4" key={label}>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
                        <p className="mt-2 text-sm font-bold text-slate-900">{value}</p>
                    </div>
                ))}
            </div>
        </Card>
    );
}

export function PaymentForm({ initial, mode = 'create', paymentId }: { initial?: Partial<PaymentFormInput>; mode?: 'create' | 'edit'; paymentId?: string }) {
    const navigate = useNavigate();
    const [methods, setMethods] = useState<PaymentMethod[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
    const [globalError, setGlobalError] = useState('');
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        paymentApi.listPaymentMethods().then((response) => setMethods(response.data));
    }, []);

    const defaults: PaymentFormInput = {
        amount: initial?.amount ?? '',
        currency: initial?.currency ?? 'LKR',
        direction: initial?.direction ?? 'generic_receipt',
        notes: initial?.notes ?? '',
        partyId: initial?.partyId ?? '',
        partyName: initial?.partyName ?? '',
        partyType: initial?.partyType ?? 'external_party',
        paymentDate: initial?.paymentDate ?? today(),
        paymentMethodId: initial?.paymentMethodId ?? methods[0]?.id ?? '',
        paymentMethodName: initial?.paymentMethodName ?? '',
        reference: initial?.reference ?? '',
        sourceId: initial?.sourceId ?? '',
        sourceModule: initial?.sourceModule ?? '',
        sourceReference: initial?.sourceReference ?? '',
        sourceType: initial?.sourceType ?? '',
    };

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setIsSaving(true);
        setFieldErrors({});
        setGlobalError('');
        const formData = new FormData(event.currentTarget);
        const method = methods.find((row) => row.id === field(formData, 'payment_method_id'));
        const payload: PaymentFormInput = {
            amount: field(formData, 'amount'),
            currency: field(formData, 'currency') || 'LKR',
            direction: field(formData, 'direction') as PaymentFormInput['direction'],
            notes: field(formData, 'notes'),
            partyId: field(formData, 'party_id'),
            partyName: field(formData, 'party_name'),
            partyType: field(formData, 'party_type') || 'external_party',
            paymentDate: field(formData, 'payment_date'),
            paymentMethodId: field(formData, 'payment_method_id'),
            paymentMethodName: method?.name,
            reference: field(formData, 'reference'),
            sourceId: field(formData, 'source_id'),
            sourceModule: field(formData, 'source_module'),
            sourceReference: field(formData, 'source_reference'),
            sourceType: field(formData, 'source_type'),
        };

        try {
            const response = mode === 'edit' && paymentId
                ? await paymentApi.updatePayment(paymentId, payload)
                : await paymentApi.createPayment(payload);
            navigate(`/payments/payments/${response.data.id}`);
        } catch (caught) {
            const parsed = apiError(caught, 'Unable to save payment.');
            setFieldErrors(parsed.errors);
            setGlobalError(parsed.message);
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={submit}>
            {globalError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{globalError}</div> : null}
            <FormSection description="Backend owns numbering, validation, posting, settlement, balances, allocation, and reversal effects." title="Basic Information">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Direction"><Select defaultValue={defaults.direction} name="direction"><option value="customer_receipt">Customer receipt</option><option value="supplier_payment">Supplier payment</option><option value="generic_receipt">Generic receipt</option><option value="generic_payment">Generic payment</option></Select></Field>
                    <Field label="Party type"><Input defaultValue={defaults.partyType} name="party_type" /></Field>
                    <Field label="Party display name"><Input defaultValue={defaults.partyName} name="party_name" /></Field>
                    <Field label="Party ID"><Input defaultValue={defaults.partyId} inputMode="numeric" name="party_id" /></Field>
                    <Field label="Payment date"><Input defaultValue={defaults.paymentDate} name="payment_date" type="date" /><FieldError message={fieldErrors.payment_date?.[0]} /></Field>
                    <Field label="Payment method"><Select defaultValue={defaults.paymentMethodId} name="payment_method_id">{methods.map((method) => <option key={method.id} value={method.id}>{method.name}</option>)}</Select><FieldError message={fieldErrors.payment_method_id?.[0]} /></Field>
                    <Field label="Currency"><Input defaultValue={defaults.currency} name="currency" /></Field>
                    <Field label="Amount"><Input defaultValue={defaults.amount} inputMode="decimal" name="amount" /><FieldError message={fieldErrors.amount?.[0]} /></Field>
                    <Field label="Reference number"><Input defaultValue={defaults.reference} name="reference" /></Field>
                </div>
            </FormSection>
            <FormSection description="These references are generic and source-module supplied. Payment does not own source business workflow." title="Source Reference">
                <div className="grid gap-4 md:grid-cols-4">
                    <Field label="Source module"><Input defaultValue={defaults.sourceModule} name="source_module" /></Field>
                    <Field label="Source type"><Input defaultValue={defaults.sourceType} name="source_type" /></Field>
                    <Field label="Source ID"><Input defaultValue={defaults.sourceId} inputMode="numeric" name="source_id" /></Field>
                    <Field label="Source reference"><Input defaultValue={defaults.sourceReference} name="source_reference" /></Field>
                </div>
            </FormSection>
            <FormSection description="Method-specific details are stored as references or notes; backend validates posting behavior." title="Method Details">
                <Field label="Notes"><Textarea defaultValue={defaults.notes} name="notes" /></Field>
            </FormSection>
            <div className="flex items-center justify-end gap-3">
                <Link to="/payments/payments"><Button type="button" variant="secondary">Cancel</Button></Link>
                <Button disabled={isSaving || methods.length === 0} type="submit" variant="blue">{isSaving ? 'Saving...' : mode === 'edit' ? 'Update payment' : 'Create payment'}</Button>
            </div>
        </form>
    );
}

export function PaymentTable({ payments: rows }: { payments: Payment[] }) {
    const columns: Array<DataTableColumn<Payment>> = [
        { header: 'Payment #', key: 'paymentNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/payments/payments/${row.id}`}>{row.paymentNumber}</Link> },
        { header: 'Direction', key: 'direction', render: (row) => row.direction.replaceAll('_', ' ') },
        { header: 'Party', key: 'party' },
        { header: 'Method', key: 'methodName' },
        { header: 'Amount', key: 'amount', render: (row) => `${row.currency} ${row.amount}` },
        { header: 'Allocated', key: 'allocatedAmount' },
        { header: 'Unallocated', key: 'unallocatedAmount' },
        { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
        { header: 'Actions', key: 'actions', render: (row) => <div className="flex gap-2"><Link to={`/payments/payments/${row.id}`}><Button variant="secondary">View</Button></Link><Link to={`/payments/payments/${row.id}/edit`}><Button variant="ghost">Edit</Button></Link></div> },
    ];

    return <DataTable columns={columns} getRowKey={(row) => row.id} rows={rows} />;
}

export function PaymentAllocationTable({ allocations }: { allocations: PaymentAllocation[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Target', key: 'targetNumber' },
                { header: 'Type', key: 'targetType' },
                { header: 'Allocated', key: 'allocatedAmount' },
                { header: 'Date', key: 'allocationDate' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={allocations}
        />
    );
}

export function PaymentAllocationPanel({ allocations, payment }: { allocations: PaymentAllocation[]; payment?: Payment }) {
    return (
        <div className="space-y-4">
            {payment ? <PaymentAllocationPreviewPanel payment={payment} /> : <EmptyState description="Select a payment detail record to preview a new allocation." title="Allocation preview unavailable" />}
            {allocations.length ? <PaymentAllocationTable allocations={allocations} /> : <EmptyState description="No allocations returned by the Payment API." title="No allocations" />}
        </div>
    );
}

export function PaymentAllocationPreviewPanel({ payment }: { payment: Payment }) {
    const [rows, setRows] = useState<Array<{ label: string; value: string }>>([]);
    const [message, setMessage] = useState('');
    const [isSaving, setIsSaving] = useState(false);

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setIsSaving(true);
        setMessage('');
        const formData = new FormData(event.currentTarget);
        try {
            const response = await paymentApi.previewAllocation(payment.id, {
                allocatedAmount: field(formData, 'allocated_amount'),
                targetId: field(formData, 'target_id'),
                targetType: field(formData, 'target_type'),
            });
            setRows([
                ...response.breakdown.map((row) => ({ label: String(row.label), value: String(row.value) })),
                { label: 'Remaining after allocation', value: response.calculated.remainingUnallocatedAmount },
                { label: 'Backend decision', value: response.calculated.targetRemainingBalance },
            ]);
            setMessage(response.errors[0] ?? response.warnings[0] ?? 'Backend preview completed.');
        } catch (caught) {
            setMessage(apiError(caught, 'Unable to preview allocation.').message);
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <PreviewPanel rows={rows} status="Backend Preview" subtitle="Allocation preview is returned by Payment API. The frontend does not derive balances." title="Allocation Preview">
            <form className="grid gap-3 md:grid-cols-4" onSubmit={submit}>
                <Input name="target_type" />
                <Input inputMode="numeric" name="target_id" />
                <Input inputMode="decimal" name="allocated_amount" />
                <Button disabled={isSaving} type="submit" variant="blue">{isSaving ? 'Previewing...' : 'Preview allocation'}</Button>
            </form>
            {message ? <p className="mt-3 text-sm text-slate-500">{message}</p> : null}
        </PreviewPanel>
    );
}

export function PaymentPostingPreviewPanel({ preview }: { preview: PaymentPostingPreview }) {
    return (
        <PreviewPanel rows={preview.breakdown.map((row) => ({ label: row.label, value: row.value }))} status="Readonly" subtitle={preview.warnings[0] ?? 'Finance owns journal impact.'} title="Finance / Posting Preview">
            {preview.journalImpact.length ? (
                <DataTable columns={[{ header: 'Account', key: 'account' }, { header: 'Direction', key: 'direction' }, { header: 'Amount', key: 'amount' }]} getRowKey={(row) => `${row.account}-${row.direction}`} rows={preview.journalImpact} />
            ) : <EmptyState description="No generic Finance posting preview endpoint is configured for Payment." title="Posting preview unavailable" />}
        </PreviewPanel>
    );
}

export function PaymentSourceReferencePanel({ references }: { references: PaymentSourceReference[] }) {
    if (references.length === 0) return <EmptyState description="This payment has no source reference." title="No source reference" />;
    return <DataTable columns={[{ header: 'Source Module', key: 'sourceModule' }, { header: 'Source Type', key: 'sourceType' }, { header: 'Reference', key: 'sourceReference' }, { header: 'Label', key: 'label' }]} getRowKey={(row) => row.id} rows={references} />;
}

export function PaymentMethodForm({ onSaved }: { onSaved?: () => void }) {
    const [error, setError] = useState('');
    const [isSaving, setIsSaving] = useState(false);

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setIsSaving(true);
        setError('');
        const formData = new FormData(event.currentTarget);
        const payload: PaymentMethodFormInput = {
            accountId: field(formData, 'account_id'),
            code: field(formData, 'code'),
            isActive: formData.get('is_active') === 'on',
            name: field(formData, 'name'),
            type: field(formData, 'type') as PaymentMethodFormInput['type'],
        };
        try {
            await paymentApi.createPaymentMethod(payload);
            event.currentTarget.reset();
            onSaved?.();
        } catch (caught) {
            setError(apiError(caught, 'Unable to save payment method.').message);
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form onSubmit={submit}>
            <FormSection description="Configure reusable payment methods. Account is optional when seeded defaults already provide one." title="Payment Method">
                {error ? <div className="rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div> : null}
                <div className="grid gap-4 md:grid-cols-5">
                    <Field label="Code"><Input name="code" /></Field>
                    <Field label="Name"><Input name="name" /></Field>
                    <Field label="Type"><Select name="type"><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="card">Card</option><option value="check">Check</option><option value="online">Online</option><option value="other">Other</option></Select></Field>
                    <Field label="Account ID"><Input inputMode="numeric" name="account_id" /></Field>
                    <Field label="Active"><input className="mt-3 h-4 w-4" defaultChecked name="is_active" type="checkbox" /></Field>
                </div>
                <div className="mt-4 flex justify-end"><Button disabled={isSaving} type="submit">{isSaving ? 'Saving...' : 'Create method'}</Button></div>
            </FormSection>
        </form>
    );
}

export function AdvancePaymentPanel({ advances }: { advances: AdvancePayment[] }) {
    return advances.length ? <SimpleTable rows={advances} columns={[['advanceNumber', 'Advance #'], ['party', 'Party'], ['amount', 'Amount'], ['remainingAmount', 'Remaining'], ['status', 'Status']]} /> : <EmptyState description="No advances returned by backend." title="No advances" />;
}

export function RefundPanel({ refunds: rows }: { refunds: Refund[] }) {
    return rows.length ? <SimpleTable rows={rows} columns={[['paymentNumber', 'Source Payment'], ['amount', 'Refund Amount'], ['methodName', 'Method'], ['reason', 'Reason'], ['status', 'Status']]} /> : <EmptyState description="Refunds are created from a payment detail action; no generic refund list endpoint exists yet." title="No refund list" />;
}

export function WriteOffPanel({ writeOffs: rows }: { writeOffs: WriteOff[] }) {
    return rows.length ? <SimpleTable rows={rows} columns={[['targetNumber', 'Target'], ['targetType', 'Type'], ['amount', 'Amount'], ['reason', 'Reason'], ['status', 'Status']]} /> : <EmptyState description="No write-offs returned by backend." title="No write-offs" />;
}

export function CashRegisterPanel({ registers }: { registers: CashRegister[] }) {
    return registers.length ? <SimpleTable rows={registers} columns={[['code', 'Code'], ['name', 'Name'], ['openingBalance', 'Opening'], ['currentBalance', 'Current'], ['status', 'Status'], ['assignedUser', 'Responsible']]} /> : <EmptyState description="No cash registers returned by backend." title="No cash registers" />;
}

export function CheckPaymentPanel({ checks: rows }: { checks: CheckPayment[] }) {
    return rows.length ? <SimpleTable rows={rows} columns={[['checkNumber', 'Check #'], ['type', 'Type'], ['party', 'Party'], ['bank', 'Bank'], ['amount', 'Amount'], ['dueDate', 'Due'], ['status', 'Status']]} /> : <EmptyState description="No checks returned by backend." title="No checks" />;
}

export function PaymentActivityTimeline({ entries }: { entries: PaymentAuditEntry[] }) {
    return <div className="space-y-3">{entries.map((entry) => <Card className="p-4" key={entry.id}><p className="text-sm font-semibold text-slate-900">{entry.description}</p><p className="mt-1 text-xs text-slate-400">{entry.actor} - {entry.time}</p></Card>)}</div>;
}

export function PaymentMethodsTable({ methods }: { methods: PaymentMethod[] }) {
    return methods.length ? <SimpleTable rows={methods} columns={[['code', 'Code'], ['name', 'Name'], ['type', 'Type'], ['accountId', 'Account ID'], ['isActive', 'Active']]} /> : <EmptyState description="No payment methods returned by backend." title="No payment methods" />;
}

export function PaymentGroupsTable({ groups }: { groups: PaymentGroup[] }) {
    return groups.length ? <SimpleTable rows={groups} columns={[['transactionNumber', 'Transaction #'], ['groupType', 'Type'], ['direction', 'Direction'], ['totalAmount', 'Total'], ['status', 'Status']]} /> : <EmptyState description="No payment groups returned by backend." title="No payment groups" />;
}

function SimpleTable<T extends { id: string }>({ columns, rows }: { columns: Array<[keyof T & string, string]>; rows: T[] }) {
    return <DataTable columns={columns.map(([key, header]) => ({ header, key, render: (row: T) => key.toLowerCase().includes('status') ? <StatusBadge status={String(row[key])} /> : String(row[key] ?? '') }))} getRowKey={(row) => String(row.id)} rows={rows} />;
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return <label className="space-y-2 text-sm"><span className="font-semibold text-slate-700">{label}</span>{children}</label>;
}
