import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { DataTable, type DataTableColumn } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
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
    PaymentPostingPreview,
    PaymentSourceReference,
    Refund,
    WriteOff,
} from '../types/payment.types';

export function PaymentSummaryCard({ payment }: { payment: Payment }) {
    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{payment.direction.replaceAll('_', ' ')}</p>
                    <h2 className="mt-1 text-2xl font-bold text-slate-950">{payment.paymentNumber}</h2>
                    <p className="mt-2 text-sm text-slate-500">{payment.party} · {payment.methodName} · {payment.paymentDate}</p>
                </div>
                <div className="flex flex-wrap items-center gap-3">
                    <StatusBadge status={payment.status} />
                    <Button variant="secondary">Preview posting</Button>
                    <Button>Allocate</Button>
                </div>
            </div>
            <div className="mt-5 grid gap-3 md:grid-cols-4">
                {[
                    ['Amount', `${payment.currency} ${payment.amount}`],
                    ['Allocated', payment.allocatedAmount],
                    ['Unallocated', payment.unallocatedAmount],
                    ['Source', payment.sourceReference ?? 'No source selected'],
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

export function PaymentForm({ initial, mode = 'create' }: { initial?: Partial<PaymentFormInput>; mode?: 'create' | 'edit' }) {
    const defaults: PaymentFormInput = {
        amount: initial?.amount ?? '',
        currency: initial?.currency ?? 'LKR',
        direction: initial?.direction ?? 'customer_receipt',
        partyType: initial?.partyType ?? 'customer',
        paymentDate: initial?.paymentDate ?? '2026-05-30',
        paymentMethodId: initial?.paymentMethodId ?? 'method-001',
        reference: initial?.reference ?? '',
        sourceModule: initial?.sourceModule ?? '',
        sourceReference: initial?.sourceReference ?? '',
        sourceType: initial?.sourceType ?? '',
    };

    return (
        <form className="space-y-5">
            <FormSection description="Capture payment inputs only. Backend owns numbering, posting, settlement, balances, allocation, and reversal effects." title="Basic Information">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Direction">
                        <Select defaultValue={defaults.direction}>
                            <option value="customer_receipt">Customer receipt</option>
                            <option value="supplier_payment">Supplier payment</option>
                            <option value="generic_receipt">Generic receipt</option>
                            <option value="generic_payment">Generic payment</option>
                        </Select>
                    </Field>
                    <Field label="Party type">
                        <Select defaultValue={defaults.partyType}>
                            <option value="customer">Customer</option>
                            <option value="supplier">Supplier</option>
                            <option value="employee">Employee</option>
                            <option value="other">Other party</option>
                        </Select>
                    </Field>
                    <Field label="Party selector">
                        <Input placeholder="Search customer, supplier, employee, or party" />
                    </Field>
                    <Field label="Payment date">
                        <Input defaultValue={defaults.paymentDate} type="date" />
                    </Field>
                    <Field label="Payment method">
                        <Select defaultValue={defaults.paymentMethodId}>
                            <option value="method-001">Cash</option>
                            <option value="method-002">Bank transfer</option>
                            <option value="method-003">Card</option>
                            <option value="method-004">Check</option>
                            <option value="method-005">Online transfer</option>
                        </Select>
                    </Field>
                    <Field label="Currency">
                        <Select defaultValue={defaults.currency}>
                            <option value="LKR">LKR</option>
                            <option value="USD">USD</option>
                        </Select>
                    </Field>
                    <Field label="Amount">
                        <Input defaultValue={defaults.amount} inputMode="decimal" placeholder="Enter amount" />
                    </Field>
                    <Field label="Reference number">
                        <Input defaultValue={defaults.reference} placeholder="Bank/card/check/reference number" />
                    </Field>
                    <Field label="Account placeholder">
                        <Input placeholder="Future backend account selector" />
                    </Field>
                </div>
            </FormSection>
            <FormSection description="Source links are generic. Payment must not know Sales, Purchase, VehicleService, or VehicleRental business rules." title="Source Reference">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Source module">
                        <Select defaultValue={defaults.sourceModule}>
                            <option value="">No source</option>
                            <option value="sales">Sales</option>
                            <option value="purchase">Purchase</option>
                            <option value="vehicle_service">Vehicle Service</option>
                            <option value="vehicle_rental">Vehicle Rental</option>
                            <option value="voucher">Voucher</option>
                            <option value="generic">Generic</option>
                        </Select>
                    </Field>
                    <Field label="Source type">
                        <Input defaultValue={defaults.sourceType} placeholder="invoice, voucher, agreement, receipt" />
                    </Field>
                    <Field label="Source reference">
                        <Input defaultValue={defaults.sourceReference} placeholder="Document number/reference" />
                    </Field>
                </div>
            </FormSection>
            <FormSection description="Method-specific fields are captured as inputs; backend validates and posts them." title="Payment Method Details">
                <div className="grid gap-4 md:grid-cols-4">
                    <Field label="Cash register">
                        <Input placeholder="Required for cash method" />
                    </Field>
                    <Field label="Bank account">
                        <Input placeholder="Required for bank/check methods" />
                    </Field>
                    <Field label="Check number/date">
                        <Input placeholder="Check number or due date" />
                    </Field>
                    <Field label="Card/online transaction">
                        <Input placeholder="Gateway transaction reference" />
                    </Field>
                </div>
                <Field label="Notes">
                    <Textarea placeholder="Internal notes. No posting logic here." />
                </Field>
            </FormSection>
            <PaymentAllocationPreviewPanel />
            <div className="flex items-center justify-end gap-3">
                <Button variant="secondary">Save draft</Button>
                <Button variant="blue">{mode === 'edit' ? 'Update payment' : 'Create payment'}</Button>
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
                { header: 'Document', key: 'documentNumber' },
                { header: 'Type', key: 'documentType' },
                { header: 'Allocated', key: 'allocatedAmount' },
                { header: 'Date', key: 'allocationDate' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={allocations}
        />
    );
}

export function PaymentAllocationPanel({ allocations }: { allocations: PaymentAllocation[] }) {
    return (
        <div className="space-y-4">
            <PaymentAllocationPreviewPanel />
            <PaymentAllocationTable allocations={allocations} />
        </div>
    );
}

export function PaymentAllocationPreviewPanel() {
    return (
        <PreviewPanel
            rows={[
                { label: 'Allocated amount', value: 'Backend/mock calculated' },
                { label: 'Remaining unallocated amount', value: 'Backend/mock calculated' },
                { label: 'Target remaining balance', value: 'Backend/mock calculated' },
            ]}
            status="Preview"
            subtitle="Allocation preview is requested from backend/mock. Frontend never derives balances."
            title="Allocation Preview"
        >
            <div className="grid gap-3 md:grid-cols-4">
                <Input placeholder="Target document/reference" />
                <Input placeholder="Requested amount if backend allows" />
                <Input placeholder="Allocation date" type="date" />
                <Button variant="blue">Preview allocation</Button>
            </div>
        </PreviewPanel>
    );
}

export function PaymentPostingPreviewPanel({ preview }: { preview: PaymentPostingPreview }) {
    return (
        <PreviewPanel
            rows={preview.breakdown.map((row) => ({ label: row.label, value: row.value }))}
            status="Backend Preview"
            subtitle="Finance posting preview is readonly. Backend/Finance owns journal impact."
            title="Finance / Posting Preview"
        >
            <DataTable
                columns={[
                    { header: 'Account', key: 'account' },
                    { header: 'Direction', key: 'direction' },
                    { header: 'Amount', key: 'amount' },
                ]}
                getRowKey={(row) => `${row.account}-${row.direction}`}
                rows={preview.journalImpact}
            />
        </PreviewPanel>
    );
}

export function PaymentSourceReferencePanel({ references }: { references: PaymentSourceReference[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Source Module', key: 'sourceModule' },
                { header: 'Source Type', key: 'sourceType' },
                { header: 'Reference', key: 'sourceReference' },
                { header: 'Label', key: 'label' },
            ]}
            getRowKey={(row) => row.id}
            rows={references}
        />
    );
}

export function PaymentMethodForm() {
    return (
        <FormSection description="Configure reusable payment methods. Posting accounts are backend validated." title="Payment Method">
            <div className="grid gap-4 md:grid-cols-4">
                <Field label="Code"><Input placeholder="CASH" /></Field>
                <Field label="Name"><Input placeholder="Cash" /></Field>
                <Field label="Type"><Select><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="card">Card</option><option value="check">Check</option></Select></Field>
                <Field label="Linked account"><Input placeholder="Backend account selector" /></Field>
            </div>
        </FormSection>
    );
}

export function AdvancePaymentPanel({ advances }: { advances: AdvancePayment[] }) {
    return <SimpleTable rows={advances} columns={[['advanceNumber', 'Advance #'], ['party', 'Party'], ['amount', 'Amount'], ['remainingAmount', 'Remaining'], ['status', 'Status']]} />;
}

export function RefundPanel({ refunds: rows }: { refunds: Refund[] }) {
    return <SimpleTable rows={rows} columns={[['paymentNumber', 'Source Payment'], ['amount', 'Refund Amount'], ['methodName', 'Method'], ['reason', 'Reason'], ['status', 'Status']]} />;
}

export function WriteOffPanel({ writeOffs: rows }: { writeOffs: WriteOff[] }) {
    return <SimpleTable rows={rows} columns={[['documentNumber', 'Document'], ['documentType', 'Type'], ['amount', 'Amount'], ['reason', 'Reason'], ['status', 'Status']]} />;
}

export function CashRegisterPanel({ registers }: { registers: CashRegister[] }) {
    return <SimpleTable rows={registers} columns={[['code', 'Code'], ['name', 'Name'], ['openingBalance', 'Opening'], ['currentBalance', 'Current'], ['status', 'Status'], ['assignedUser', 'Responsible']]} />;
}

export function CheckPaymentPanel({ checks: rows }: { checks: CheckPayment[] }) {
    return <SimpleTable rows={rows} columns={[['checkNumber', 'Check #'], ['type', 'Type'], ['party', 'Party'], ['bank', 'Bank'], ['amount', 'Amount'], ['dueDate', 'Due'], ['status', 'Status']]} />;
}

export function PaymentActivityTimeline({ entries }: { entries: PaymentAuditEntry[] }) {
    return (
        <div className="space-y-3">
            {entries.map((entry) => (
                <div className="rounded-lg border border-slate-200 bg-white p-4" key={entry.id}>
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-sm font-semibold text-slate-900">{entry.description}</p>
                        <span className="text-xs text-slate-400">{entry.time}</span>
                    </div>
                    <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">{entry.actor}</p>
                </div>
            ))}
        </div>
    );
}

export function PaymentMethodsTable({ methods }: { methods: PaymentMethod[] }) {
    return <SimpleTable rows={methods} columns={[['code', 'Code'], ['name', 'Name'], ['type', 'Type'], ['accountName', 'Account'], ['isActive', 'Active']]} />;
}

export function PaymentGroupsTable({ groups }: { groups: PaymentGroup[] }) {
    return <SimpleTable rows={groups} columns={[['transactionNumber', 'Transaction #'], ['groupType', 'Type'], ['direction', 'Direction'], ['totalAmount', 'Total'], ['status', 'Status']]} />;
}

function SimpleTable<T extends { id: string }>({ columns, rows }: { columns: Array<[keyof T & string, string]>; rows: T[] }) {
    return (
        <DataTable
            columns={columns.map(([key, header]) => ({
                header,
                key,
                render: (row: T) => key.toLowerCase().includes('status') ? <StatusBadge status={String(row[key])} /> : String(row[key] ?? ''),
            }))}
            getRowKey={(row) => String(row.id)}
            rows={rows}
        />
    );
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return (
        <label className="space-y-2 text-sm">
            <span className="font-semibold text-slate-700">{label}</span>
            {children}
        </label>
    );
}
