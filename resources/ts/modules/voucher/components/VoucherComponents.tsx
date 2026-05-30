import { useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { voucherTypes } from '../mock/voucherMock';
import type {
    Voucher,
    VoucherAllocation,
    VoucherAuditEntry,
    VoucherDashboardMetric,
    VoucherSettings,
    VoucherType,
} from '../types/voucher.types';

function Label({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
        </div>
    );
}

const partyTypeOptions = [
    { label: 'Customer', value: 'customer' },
    { label: 'Supplier', value: 'supplier' },
    { label: 'Employee', value: 'employee' },
    { label: 'Other party', value: 'other' },
    { label: 'Internal', value: 'internal' },
];

const sourceModuleOptions = [
    { label: 'Voucher', value: 'voucher' },
    { label: 'Finance', value: 'finance' },
    { label: 'Payment', value: 'payment' },
    { label: 'Purchase', value: 'purchase' },
    { label: 'Sales', value: 'sales' },
    { label: 'Vehicle Service', value: 'vehicle_service' },
    { label: 'Vehicle Rental', value: 'vehicle_rental' },
    { label: 'Generic source', value: 'generic' },
];

export function VoucherPageHeader({ actions, subtitle, title }: { actions?: ReactNode; subtitle?: string; title: string }) {
    return <PageHeader actions={actions} eyebrow="Voucher" subtitle={subtitle} title={title} />;
}

export function VoucherDashboardCards({ metrics }: { metrics: VoucherDashboardMetric[] }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            {metrics.map((metric) => (
                <Card className="p-5" key={metric.label}>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{metric.label}</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{metric.value}</p>
                    <p className="mt-2 text-xs font-semibold text-slate-500">{metric.tone} status from backend/mock</p>
                </Card>
            ))}
        </div>
    );
}

export function VoucherTypeSummaryCard({ type }: { type: VoucherType }) {
    return (
        <Card className="p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{type.code}</p>
                    <h2 className="mt-1 text-xl font-bold text-slate-950">{type.name}</h2>
                    <p className="mt-2 text-sm text-slate-500">{type.category} / {type.direction}</p>
                </div>
                <StatusBadge status={type.status} />
            </div>
            <div className="mt-5 grid gap-3 text-sm md:grid-cols-2">
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p className="text-slate-500">Approval</p>
                    <p className="font-semibold text-slate-900">{type.requiresApproval ? 'Required' : 'Not required'}</p>
                </div>
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p className="text-slate-500">Payment method</p>
                    <p className="font-semibold text-slate-900">{type.requiresPaymentMethod ? 'Required' : 'Optional'}</p>
                </div>
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p className="text-slate-500">Balance validation</p>
                    <p className="font-semibold text-slate-900">{type.requiresBalancedLines ? 'Backend required' : 'Backend optional'}</p>
                </div>
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p className="text-slate-500">Sequence</p>
                    <p className="font-semibold text-slate-900">{type.defaultSequence}</p>
                </div>
            </div>
        </Card>
    );
}

export function VoucherTypeTable({ rows }: { rows: VoucherType[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vouchers/types/${row.id}`}>{row.code}</Link> },
                { header: 'Name', key: 'name' },
                { header: 'Category', key: 'category' },
                { header: 'Direction', key: 'direction' },
                { header: 'Payment method', key: 'requiresPaymentMethod', render: (row) => (row.requiresPaymentMethod ? 'Required' : 'Optional') },
                { header: 'Approval', key: 'requiresApproval', render: (row) => (row.requiresApproval ? 'Required' : 'Not required') },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <Link to={`/vouchers/types/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function VoucherTable({ rows }: { rows: Voucher[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Voucher', key: 'voucherNumber', render: (row) => <Link className="font-semibold text-blue-700 hover:underline" to={`/vouchers/${row.id}`}>{row.voucherNumber}</Link> },
                { header: 'Type', key: 'voucherType' },
                { header: 'Party', key: 'party' },
                { header: 'Payment method', key: 'paymentMethod' },
                { header: 'Backend total', key: 'totalAmount' },
                { header: 'Approval', key: 'approvalStatus', render: (row) => <StatusBadge status={row.approvalStatus} /> },
                { header: 'Posting', key: 'postingStatus', render: (row) => <StatusBadge status={row.postingStatus} /> },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <Link to={`/vouchers/${row.id}/edit`}><Button variant="secondary">Edit</Button></Link> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function VoucherTypeForm({ type }: { type?: VoucherType }) {
    return (
        <FormSection description="Voucher type behavior is saved for backend workflow, payment, posting, document, and approval validation." title="Voucher Type">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Type code"><Input defaultValue={type?.code} placeholder="PAY-VOU" /></Field>
                <Field label="Type name"><Input defaultValue={type?.name} placeholder="Payment Voucher" /></Field>
                <Field label="Category"><Select defaultValue={type?.category} options={[
                    { label: 'Payment voucher', value: 'payment' },
                    { label: 'Receipt voucher', value: 'receipt' },
                    { label: 'Journal voucher', value: 'journal' },
                    { label: 'Contra voucher', value: 'contra' },
                    { label: 'Expense voucher', value: 'expense' },
                    { label: 'Advance voucher', value: 'advance' },
                    { label: 'Refund voucher', value: 'refund' },
                    { label: 'Write-off voucher', value: 'write_off' },
                    { label: 'Adjustment voucher', value: 'adjustment' },
                ]} /></Field>
                <Field label="Direction"><Select defaultValue={type?.direction} options={[
                    { label: 'Payment', value: 'payment' },
                    { label: 'Receipt', value: 'receipt' },
                    { label: 'Journal', value: 'journal' },
                    { label: 'Contra', value: 'contra' },
                    { label: 'Adjustment', value: 'adjustment' },
                ]} /></Field>
                <Field label="Default sequence"><Input defaultValue={type?.defaultSequence} placeholder="Backend sequence code" /></Field>
                <Field label="Document definition"><Input defaultValue={type?.defaultDocumentDefinition} placeholder="Document definition" /></Field>
                <Field label="Requires payment method"><Select defaultValue={String(type?.requiresPaymentMethod ?? true)} options={[{ label: 'Required', value: 'true' }, { label: 'Not required', value: 'false' }]} /></Field>
                <Field label="Requires balanced lines"><Select defaultValue={String(type?.requiresBalancedLines ?? true)} options={[{ label: 'Required', value: 'true' }, { label: 'Optional', value: 'false' }]} /></Field>
                <Field label="Requires approval"><Select defaultValue={String(type?.requiresApproval ?? true)} options={[{ label: 'Required', value: 'true' }, { label: 'Not required', value: 'false' }]} /></Field>
                <Field label="Active"><Select defaultValue={String(type?.activeFlag ?? true)} options={[{ label: 'Active', value: 'true' }, { label: 'Inactive', value: 'false' }]} /></Field>
                <div className="md:col-span-2">
                    <Field label="Description"><Textarea placeholder="Generic voucher type notes. No business-module workflow belongs here." /></Field>
                </div>
            </div>
        </FormSection>
    );
}

export function VoucherHeaderForm({ voucher }: { voucher?: Voucher }) {
    return (
        <FormSection description="Header values are collected for backend validation, numbering, workflow, payment and posting preview." title="Header">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Voucher type"><Select defaultValue={voucher?.voucherType} options={voucherTypes.map((type) => ({ label: type.name, value: type.name }))} placeholder="Select voucher type" /></Field>
                <Field label="Voucher date"><Input defaultValue={voucher?.voucherDate} type="date" /></Field>
                <Field label="Reference number"><Input defaultValue={voucher?.referenceNumber} placeholder="External/reference number" /></Field>
                <Field label="Party type"><Select defaultValue={voucher?.partyType} options={partyTypeOptions} placeholder="Select party type" /></Field>
                <Field label="Party"><Input defaultValue={voucher?.party} placeholder="Customer, supplier, employee, or other party" /></Field>
                <Field label="Currency"><Input defaultValue={voucher?.currency ?? 'LKR'} placeholder="Currency" /></Field>
                <Field label="Source module"><Select defaultValue={voucher?.sourceReference.sourceModule} options={sourceModuleOptions} /></Field>
                <Field label="Source type"><Input defaultValue={voucher?.sourceReference.sourceType} placeholder="Source type" /></Field>
                <Field label="Source reference"><Input defaultValue={voucher?.sourceReference.sourceNumber} placeholder="Source document/reference" /></Field>
                <Field label="Payment method"><Input defaultValue={voucher?.paymentMethod} placeholder="Backend validates if required" /></Field>
                <Field label="Status"><Input readOnly value={voucher?.status ?? 'draft'} /></Field>
                <div className="md:col-span-2 xl:col-span-3">
                    <Field label="Description"><Textarea defaultValue={voucher?.description} placeholder="Voucher description" /></Field>
                </div>
            </div>
        </FormSection>
    );
}

export function VoucherLineTable({ voucher }: { voucher: Voucher }) {
    return (
        <DataTable
            columns={[
                { header: 'Line', key: 'lineNo' },
                { header: 'Account', key: 'account' },
                { header: 'Debit', key: 'debitAmount' },
                { header: 'Credit', key: 'creditAmount' },
                { header: 'Backend amount', key: 'amountPreview' },
                { header: 'Cost center', key: 'costCenter' },
                { header: 'Party', key: 'party' },
                { header: 'Tax preview', key: 'taxPreview' },
                { header: 'Source', key: 'sourceReference' },
            ]}
            getRowKey={(row) => row.id}
            rows={voucher.lines}
        />
    );
}

export function VoucherLineEditor({ voucher }: { voucher?: Voucher }) {
    return (
        <div className="space-y-5">
            <FormSection description="Line inputs are sent to backend for balance validation and posting preview. The frontend does not total debit or credit lines." title="Voucher Lines">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Field label="Account"><Input placeholder="Account selector" /></Field>
                    <Field label="Debit"><Input placeholder="Debit amount input" /></Field>
                    <Field label="Credit"><Input placeholder="Credit amount input" /></Field>
                    <Field label="Amount"><Input placeholder="Backend validates direction" /></Field>
                    <Field label="Cost center"><Input placeholder="Cost center selector" /></Field>
                    <Field label="Tax rate"><Input placeholder="Tax rate if applicable" /></Field>
                    <Field label="Party"><Input placeholder="Optional party" /></Field>
                    <Field label="Line source"><Input placeholder="Source reference" /></Field>
                    <div className="md:col-span-2 xl:col-span-4">
                        <Field label="Description"><Textarea placeholder="Line description" /></Field>
                    </div>
                </div>
            </FormSection>
            {voucher ? <VoucherLineTable voucher={voucher} /> : null}
        </div>
    );
}

export function VoucherAllocationTable({ rows }: { rows: VoucherAllocation[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Target module', key: 'targetModule' },
                { header: 'Target type', key: 'targetType' },
                { header: 'Target reference', key: 'targetReference' },
                { header: 'Backend allocation', key: 'allocatedAmount' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function VoucherAllocationPanel({ voucher }: { voucher: Voucher }) {
    return (
        <div className="space-y-5">
            <FormSection description="Allocations point to generic target modules and documents. Backend owns allocation balances and target eligibility." title="Allocations">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Field label="Target module"><Select options={sourceModuleOptions} placeholder="Target module" /></Field>
                    <Field label="Target type"><Input placeholder="Invoice, payable, receivable, advance, refund..." /></Field>
                    <Field label="Target reference"><Input placeholder="Target document/reference" /></Field>
                    <Field label="Allocation amount"><Input placeholder="Backend validates balance" /></Field>
                </div>
            </FormSection>
            <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                <VoucherAllocationTable rows={voucher.allocations} />
                <PreviewPanel rows={[
                    { label: 'Allocation balance', value: voucher.paymentImpact.calculated.allocationBalance },
                    { label: 'Settlement status', value: voucher.paymentImpact.calculated.settlementStatus },
                    { label: 'Payment method', value: voucher.paymentImpact.calculated.paymentMethodValidation },
                ]} status="Backend Preview" title="Allocation Preview" />
            </div>
        </div>
    );
}

export function VoucherPostingPreviewPanel({ voucher }: { voucher: Voucher }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Debit total', value: voucher.postingPreview.calculated.debitTotal },
                { label: 'Credit total', value: voucher.postingPreview.calculated.creditTotal },
                { label: 'Balanced?', value: voucher.postingPreview.calculated.balanced },
                { label: 'Posting eligibility', value: voucher.postingPreview.calculated.eligibility },
                { label: 'Journal impact', value: voucher.postingPreview.calculated.journalImpact },
            ]}
            status="Finance Preview"
            title="Posting Preview / Finance"
        >
            <DataTable
                columns={[
                    { header: 'Account', key: 'account' },
                    { header: 'Effect', key: 'effect' },
                ]}
                getRowKey={(row) => `${row.account}-${row.effect}`}
                rows={voucher.postingPreview.breakdown}
            />
        </PreviewPanel>
    );
}

export function VoucherPaymentImpactPanel({ voucher }: { voucher: Voucher }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Payment impact', value: voucher.paymentImpact.calculated.paymentImpact },
                { label: 'Allocation balance', value: voucher.paymentImpact.calculated.allocationBalance },
                { label: 'Settlement status', value: voucher.paymentImpact.calculated.settlementStatus },
                { label: 'Payment validation', value: voucher.paymentImpact.calculated.paymentMethodValidation },
            ]}
            status="Payment Preview"
            title="Payment Impact"
        />
    );
}

export function VoucherDocumentPanel({ voucher }: { voucher: Voucher }) {
    return (
        <PreviewPanel rows={[
            { label: 'Document number', value: voucher.document.documentNumber },
            { label: 'Template', value: voucher.document.template },
            { label: 'Status', value: voucher.document.status },
        ]} status="Document" title="Voucher Document" />
    );
}

export function VoucherApprovalPanel({ voucher }: { voucher: Voucher }) {
    return (
        <div className="space-y-5">
            <PreviewPanel rows={[
                { label: 'Approval status', value: voucher.approvalStatus },
                { label: 'Voucher status', value: voucher.status },
                { label: 'Backend actions', value: voucher.availableActions?.map((action) => action.label).join(', ') ?? 'Backend action list' },
            ]} status="Workflow" title="Approval Workflow" />
            <DataTable
                columns={[
                    { header: 'Level', key: 'level' },
                    { header: 'Actor', key: 'actor' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                    { header: 'Remarks', key: 'remarks' },
                    { header: 'Timestamp', key: 'timestamp' },
                ]}
                getRowKey={(row) => row.id}
                rows={voucher.approvals}
            />
        </div>
    );
}

export function VoucherWorkflowActions({ voucher }: { voucher: Voucher }) {
    const actions = voucher.availableActions ?? [
        { action: 'submit', label: 'Submit' },
        { action: 'approve', label: 'Approve' },
        { action: 'post', label: 'Post' },
        { action: 'reverse', label: 'Reverse', tone: 'danger' as const },
    ];

    return (
        <Card className="p-5">
            <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">Workflow Actions</h3>
            <p className="mt-1 text-sm text-slate-500">Actions call backend workflow endpoints. The frontend does not infer allowed status transitions.</p>
            <div className="mt-4 flex flex-wrap gap-2">
                {actions.map((action) => (
                    <Button key={action.action} variant={action.tone === 'danger' ? 'danger' : action.tone === 'primary' ? 'primary' : 'secondary'}>{action.label}</Button>
                ))}
            </div>
            <p className="mt-4 text-sm font-semibold text-slate-600">Current backend/mock status: {voucher.status}</p>
        </Card>
    );
}

export function VoucherActivityTimeline({ rows }: { rows: VoucherAuditEntry[] }) {
    return (
        <div className="space-y-3">
            {rows.map((entry) => (
                <Card className="p-4" key={entry.id}>
                    <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p className="font-semibold text-slate-900">{entry.note}</p>
                            <p className="mt-1 text-sm text-slate-500">{entry.actor} / {entry.type}</p>
                        </div>
                        <div className="text-sm text-slate-500">{entry.timestamp}</div>
                    </div>
                </Card>
            ))}
        </div>
    );
}

export function VoucherForm({ voucher }: { voucher: Voucher }) {
    const [activeTab, setActiveTab] = useState('header');
    const tabs = [
        { label: 'Header', value: 'header' },
        { label: 'Lines', value: 'lines' },
        { label: 'Allocations', value: 'allocations' },
        { label: 'Review & Preview', value: 'review' },
    ];

    return (
        <div className="space-y-5">
            <Card className="p-5"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} trailing={<StatusBadge status="Backend owned" />} /></Card>
            {activeTab === 'header' ? <VoucherHeaderForm voucher={voucher} /> : null}
            {activeTab === 'lines' ? <VoucherLineEditor voucher={voucher} /> : null}
            {activeTab === 'allocations' ? <VoucherAllocationPanel voucher={voucher} /> : null}
            {activeTab === 'review' ? (
                <div className="grid gap-5 xl:grid-cols-2">
                    <PreviewPanel rows={[
                        { label: 'Voucher', value: voucher.voucherNumber },
                        { label: 'Type', value: voucher.voucherType },
                        { label: 'Backend debit total', value: voucher.totalDebit },
                        { label: 'Backend credit total', value: voucher.totalCredit },
                        { label: 'Backend amount', value: voucher.totalAmount },
                    ]} status="Summary" title="Voucher Summary" />
                    <VoucherPostingPreviewPanel voucher={voucher} />
                    <VoucherPaymentImpactPanel voucher={voucher} />
                    <VoucherDocumentPanel voucher={voucher} />
                </div>
            ) : null}
        </div>
    );
}

export function VoucherSettingsForm({ settings }: { settings: VoucherSettings }) {
    return (
        <FormSection description="Voucher settings define defaults for sequence, document, approval, payment method, and partial allocation behavior." title="Voucher Settings">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Default sequence"><Input defaultValue={settings.defaultSequence} /></Field>
                <Field label="Default document definition"><Input defaultValue={settings.defaultDocumentDefinition} /></Field>
                <Field label="Default payment method"><Input defaultValue={settings.defaultPaymentMethod} /></Field>
                <Field label="Require approval"><Select defaultValue={String(settings.requireApproval)} options={[{ label: 'Required', value: 'true' }, { label: 'Not required', value: 'false' }]} /></Field>
                <Field label="Allow direct posting"><Select defaultValue={String(settings.allowDirectPosting)} options={[{ label: 'Allowed', value: 'true' }, { label: 'Blocked', value: 'false' }]} /></Field>
                <Field label="Allow partial allocation"><Select defaultValue={String(settings.allowPartialAllocation)} options={[{ label: 'Allowed', value: 'true' }, { label: 'Blocked', value: 'false' }]} /></Field>
            </div>
        </FormSection>
    );
}
