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
    GoodsReceivedNote,
    GoodsReceivedNoteLine,
    PurchaseAdvance,
    PurchaseAuditEntry,
    PurchaseDashboardMetric,
    PurchaseFinancePostingPreview,
    PurchaseInventoryEffect,
    PurchaseInvoice,
    PurchaseInvoiceLine,
    PurchaseOrder,
    PurchaseOrderLine,
    PurchasePayment,
    PurchasePaymentAllocation,
    PurchaseReturn,
    PurchaseReturnLine,
    PurchaseSettings,
    SupplierRefund,
} from '../types/purchase.types';

export function PurchaseDashboardCards({ metrics }: { metrics: PurchaseDashboardMetric[] }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {metrics.map((metric) => (
                <Card className="p-5" key={metric.label}>
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{metric.label}</p>
                            <p className="mt-3 text-3xl font-bold text-slate-950">{metric.value}</p>
                        </div>
                        <StatusBadge status={metric.status} />
                    </div>
                </Card>
            ))}
        </div>
    );
}

export function PurchaseOrderForm({ mode = 'create' }: { mode?: 'create' | 'edit' }) {
    return (
        <form className="space-y-5">
            <FormSection description="Supplier eligibility, payment terms, workflow defaults, and sequence values are backend validated." title="Purchase Order Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Supplier"><Input placeholder="Select supplier" /></Field>
                    <Field label="Order date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Expected date"><Input type="date" /></Field>
                    <Field label="Payment term"><Input placeholder="Backend supplier/default term" /></Field>
                    <Field label="Warehouse"><Input placeholder="Default warehouse" /></Field>
                    <Field label="Workflow"><Select><option>Save as draft</option><option>Submit for approval</option></Select></Field>
                    <Field label="Notes"><Textarea placeholder="Supplier instructions, delivery notes, internal remarks" /></Field>
                </div>
            </FormSection>
            <FormSection description="Frontend collects item, UOM, quantity, price inputs. Backend resolves UOM conversion, discounts, tax, and totals." title="Order Lines">
                <PurchaseOrderLineTable rows={[draftOrderLine]} />
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    <Button variant="secondary">Save Draft</Button>
                    <Button variant="blue">{mode === 'edit' ? 'Update With Lines' : 'Create With Lines'}</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function PurchaseOrderLineTable({ rows }: { rows: PurchaseOrderLine[] }) {
    return (
        <SimpleTable
            columns={[
                ['item', 'Item'],
                ['orderedQuantity', 'Qty'],
                ['uom', 'UOM'],
                ['backendConvertedQuantity', 'Backend Base Qty'],
                ['unitPrice', 'Unit Price'],
                ['discountAmount', 'Discount'],
                ['taxAmount', 'Tax'],
                ['lineTotal', 'Line Total'],
                ['receivedQuantity', 'Received'],
                ['remainingQuantity', 'Remaining'],
            ]}
            rows={rows}
        />
    );
}

export function PurchaseOrderSummaryCard({ order }: { order: PurchaseOrder }) {
    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Purchase Order</p>
                    <h2 className="mt-1 text-2xl font-bold text-slate-950">{order.poNumber}</h2>
                    <p className="mt-2 text-sm text-slate-500">{order.supplier} - {order.workflow}</p>
                    <div className="mt-4 grid gap-3 text-sm md:grid-cols-3">
                        <Info label="Order date" value={order.orderDate} />
                        <Info label="Expected" value={order.expectedDate} />
                        <Info label="Backend total" value={order.grandTotal} />
                    </div>
                </div>
                <StatusBadge status={order.status} />
            </div>
        </Card>
    );
}

export function PurchaseWorkflowActions({ entityId, entityType, status }: { entityId: string; entityType: string; status: string }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Entity', value: `${entityType} / ${entityId}` },
                { label: 'Current status', value: status },
                { label: 'Allowed actions', value: 'Backend workflow response' },
            ]}
            status="Workflow"
            title="Workflow Actions"
        >
            <div className="flex flex-wrap gap-2">
                <Button variant="blue">Submit</Button>
                <Button variant="secondary">Approve</Button>
                <Button variant="ghost">Cancel</Button>
                <Button variant="ghost">Reverse</Button>
            </div>
        </PreviewPanel>
    );
}

export function GrnForm({ mode = 'create' }: { mode?: 'create' | 'edit' }) {
    return (
        <form className="space-y-5">
            <FormSection description="GRN records quantity received. Backend validates PO lines, UOM conversion, warehouse, batch/serial, and stock effect." title="GRN Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Supplier"><Input placeholder="Select supplier" /></Field>
                    <Field label="Source PO optional"><Input placeholder="PO-2026-0001 or direct GRN" /></Field>
                    <Field label="GRN date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Warehouse"><Input placeholder="Receiving warehouse" /></Field>
                    <Field label="Inspection status"><Select><option>Pending inspection</option><option>Accepted</option></Select></Field>
                    <Field label="Notes"><Textarea placeholder="Receiving notes" /></Field>
                </div>
            </FormSection>
            <FormSection description="Accepted/rejected quantities are submitted as inputs. Backend returns authoritative stock movement effect." title="Received Lines">
                <GrnLineTable rows={[draftGrnLine]} />
                <div className="mt-4 flex justify-end gap-3"><Button variant="secondary">Save Draft</Button><Button variant="blue">{mode === 'edit' ? 'Update GRN' : 'Create GRN'}</Button></div>
            </FormSection>
        </form>
    );
}

export function GrnLineTable({ rows }: { rows: GoodsReceivedNoteLine[] }) {
    return <SimpleTable columns={[['item', 'Item'], ['sourceLine', 'Source Line'], ['orderedQuantity', 'Ordered'], ['acceptedQuantity', 'Accepted'], ['rejectedQuantity', 'Rejected'], ['uom', 'UOM'], ['backendBaseQuantity', 'Backend Base Qty']]} rows={rows} />;
}

export function GrnInventoryEffectPanel({ effects }: { effects: PurchaseInventoryEffect[] }) {
    return (
        <PreviewPanel status="Inventory Preview" subtitle="Readonly stock effect returned by Inventory/Purchase backend. Frontend does not calculate stock." title="Inventory Effect">
            <SimpleTable columns={[['sourceReference', 'Source'], ['item', 'Item'], ['warehouse', 'Warehouse'], ['quantityEffect', 'Quantity Effect'], ['decision', 'Decision']]} rows={effects.map((effect, index) => ({ ...effect, id: `${effect.sourceReference}-${index}` }))} />
        </PreviewPanel>
    );
}

export function PurchaseInvoiceForm({ mode = 'create' }: { mode?: 'create' | 'edit' }) {
    return (
        <form className="space-y-5">
            <FormSection description="Supports direct invoice, invoice from PO, GRN, or multiple GRNs according to backend settings." title="Supplier Invoice Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Supplier"><Input placeholder="Select supplier" /></Field>
                    <Field label="Invoice source"><Select><option>Direct invoice</option><option>From PO</option><option>From GRN</option><option>From multiple GRNs</option></Select></Field>
                    <Field label="Source reference"><Input placeholder="PO / GRN / document reference" /></Field>
                    <Field label="Invoice date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Due date"><Input type="date" /></Field>
                    <Field label="Supplier invoice no"><Input placeholder="Supplier reference" /></Field>
                    <Field label="Notes"><Textarea placeholder="Invoice notes" /></Field>
                </div>
            </FormSection>
            <FormSection description="Line amount, discounts, taxes, UOM conversion, payable amount, and balances are previewed by backend only." title="Invoice Lines">
                <PurchaseInvoiceLineTable rows={[draftInvoiceLine]} />
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    <Button variant="secondary">Save Draft</Button>
                    <Button variant="blue">Preview Calculation</Button>
                    <Button>{mode === 'edit' ? 'Update Invoice' : 'Create Invoice'}</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function PurchaseInvoiceLineTable({ rows }: { rows: PurchaseInvoiceLine[] }) {
    return <SimpleTable columns={[['item', 'Item'], ['sourceLine', 'Source Line'], ['invoiceQuantity', 'Invoice Qty'], ['uom', 'UOM'], ['unitPrice', 'Backend Price'], ['discountAmount', 'Discount'], ['taxAmount', 'Tax'], ['lineTotal', 'Line Total']]} rows={rows} />;
}

export function PurchaseInvoiceCalculationPanel() {
    return (
        <PreviewPanel
            rows={[
                { label: 'Subtotal', value: 'Backend calculated' },
                { label: 'Discount', value: 'Backend calculated' },
                { label: 'Tax', value: 'Backend calculated' },
                { label: 'Grand total', value: 'Backend calculated' },
                { label: 'UOM conversion', value: 'Backend returned' },
            ]}
            status="Backend Preview"
            subtitle="Invoice total, discount, tax, UOM, and payable values are never calculated in the frontend."
            title="Invoice Calculation Preview"
        />
    );
}

export function PurchaseInvoiceDocumentPanel() {
    return (
        <PreviewPanel
            rows={[
                { label: 'Document definition', value: 'Supplier Invoice Standard' },
                { label: 'Document number', value: 'Backend sequence preview' },
                { label: 'Rendering', value: 'Document module backend' },
            ]}
            status="Document"
            title="Document Invoice"
        >
            <div className="flex flex-wrap gap-2"><Button variant="secondary">Preview Document</Button><Button variant="ghost">Generate</Button></div>
        </PreviewPanel>
    );
}

export function PurchasePaymentForm() {
    return (
        <form className="space-y-5">
            <FormSection description="Payment is routed through Payment module. Backend validates payable invoices, allocations, balances, and posting." title="Supplier Payment">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Supplier"><Input placeholder="Select supplier" /></Field>
                    <Field label="Payment date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Payment method"><Select><option>Bank Transfer</option><option>Cash</option><option>Check</option><option>Card</option></Select></Field>
                    <Field label="Amount"><Input placeholder="Input amount only" /></Field>
                    <Field label="Reference"><Input placeholder="Bank/check/reference number" /></Field>
                    <Field label="Source invoice optional"><Input placeholder="PINV-..." /></Field>
                </div>
            </FormSection>
            <PurchasePaymentAllocationPanel />
            <div className="flex justify-end gap-3"><Button variant="secondary">Save Draft</Button><Button variant="blue">Preview Allocation</Button><Button>Create Payment</Button></div>
        </form>
    );
}

export function PurchasePaymentAllocationPanel({ allocations = [] }: { allocations?: PurchasePaymentAllocation[] }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Allocated amount', value: 'Backend calculated' },
                { label: 'Unallocated amount', value: 'Backend calculated' },
                { label: 'Invoice balance after allocation', value: 'Backend calculated' },
            ]}
            status="Backend Preview"
            title="Payment Allocation"
        >
            {allocations.length ? <SimpleTable columns={[['sourceDocument', 'Document'], ['allocatedAmount', 'Allocated'], ['documentBalanceAfter', 'Balance After'], ['status', 'Status']]} rows={allocations} /> : null}
        </PreviewPanel>
    );
}

export function PurchaseAdvancePanel({ advances }: { advances: PurchaseAdvance[] }) {
    return <SimpleTable columns={[['advanceNumber', 'Advance #'], ['supplier', 'Supplier'], ['amount', 'Amount'], ['remainingAmount', 'Backend Remaining'], ['status', 'Status']]} rows={advances} />;
}

export function PurchaseReturnForm() {
    return (
        <form className="space-y-5">
            <FormSection description="Returnable quantity, stock reversal, AP adjustment, and refund eligibility are backend-owned." title="Purchase Return">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Supplier"><Input placeholder="Select supplier" /></Field>
                    <Field label="Source document"><Input placeholder="PO / GRN / Invoice / Direct" /></Field>
                    <Field label="Return date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Reason"><Input placeholder="Damage, over supply, wrong item..." /></Field>
                    <Field label="Notes"><Textarea placeholder="Return notes" /></Field>
                </div>
            </FormSection>
            <FormSection description="Backend validates returnable quantities and previews inventory/AP effects." title="Return Lines">
                <PurchaseReturnLineTable rows={[draftReturnLine]} />
                <div className="mt-4 flex justify-end gap-3"><Button variant="blue">Preview Return Effect</Button><Button>Create Return</Button></div>
            </FormSection>
        </form>
    );
}

export function PurchaseReturnLineTable({ rows }: { rows: PurchaseReturnLine[] }) {
    return <SimpleTable columns={[['item', 'Item'], ['sourceLine', 'Source Line'], ['backendReturnableQuantity', 'Backend Returnable'], ['returnQuantity', 'Return Qty'], ['uom', 'UOM']]} rows={rows} />;
}

export function SupplierRefundPanel({ refunds }: { refunds: SupplierRefund[] }) {
    return <SimpleTable columns={[['refundNumber', 'Refund #'], ['supplier', 'Supplier'], ['sourceReference', 'Source'], ['amount', 'Backend Amount'], ['method', 'Method'], ['status', 'Status']]} rows={refunds} />;
}

export function PurchaseFinancePostingPanel({ preview }: { preview: PurchaseFinancePostingPreview }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'AP impact', value: preview.calculated.apImpact },
                { label: 'Tax impact', value: preview.calculated.taxImpact },
                { label: 'Journal impact', value: preview.calculated.journalImpact },
                { label: 'Eligibility', value: preview.calculated.eligibility },
            ]}
            status="Finance Preview"
            title="Finance / AP Posting"
        >
            <SimpleTable columns={[['account', 'Account'], ['debit', 'Debit'], ['credit', 'Credit'], ['description', 'Description']]} rows={preview.lines.map((line, index) => ({ ...line, id: `posting-${index}` }))} />
        </PreviewPanel>
    );
}

export function PurchaseSourceReferencePanel({ reference }: { reference?: string }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Source reference', value: reference ?? 'Direct / not linked' },
                { label: 'Allowed workflow', value: 'PO -> GRN -> Invoice, PO -> Invoice, GRN -> Invoice, Direct Invoice' },
            ]}
            status="Source"
            title="Source Reference"
        />
    );
}

export function PurchaseActivityTimeline({ rows }: { rows: PurchaseAuditEntry[] }) {
    return (
        <div className="space-y-3">
            {rows.map((entry) => (
                <Card className="p-4" key={entry.id}>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold text-slate-950">{entry.description}</p>
                            <p className="mt-1 text-xs font-bold uppercase tracking-wide text-slate-400">{entry.actor} - {entry.type}</p>
                        </div>
                        <span className="text-xs text-slate-400">{entry.time}</span>
                    </div>
                </Card>
            ))}
        </div>
    );
}

export function PurchaseSettingsForm({ settings }: { settings: PurchaseSettings }) {
    return (
        <form className="space-y-5">
            <FormSection description="Settings guide backend workflow behavior; global configuration stays outside Purchase." title="Accounting, Document, and Warehouse Defaults">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Default payable account"><Input defaultValue={settings.defaultPayableAccount} /></Field>
                    <Field label="Default payment term"><Input defaultValue={settings.defaultPaymentTerm} /></Field>
                    <Field label="Default tax group"><Input defaultValue={settings.defaultTaxGroup} /></Field>
                    <Field label="Default warehouse"><Input defaultValue={settings.defaultWarehouse} /></Field>
                    <Field label="Invoice document definition"><Input defaultValue={settings.invoiceDocumentDefinition} /></Field>
                    <Field label="Invoice matching rule"><Input defaultValue={settings.invoiceMatchingRule} /></Field>
                </div>
            </FormSection>
            <FormSection description="Sequences are previewed/generated by backend Sequence/Document services." title="Sequences and Workflow Rules">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="PO sequence"><Input defaultValue={settings.poSequence} /></Field>
                    <Field label="GRN sequence"><Input defaultValue={settings.grnSequence} /></Field>
                    <Field label="Invoice sequence"><Input defaultValue={settings.invoiceSequence} /></Field>
                    <Field label="Return sequence"><Input defaultValue={settings.returnSequence} /></Field>
                    <Field label="Stock receive timing"><Input defaultValue={settings.stockReceiveTiming} /></Field>
                    <Field label="Allow direct invoice"><Select defaultValue={String(settings.allowDirectInvoice)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                    <Field label="Allow GRN without PO"><Select defaultValue={String(settings.allowGrnWithoutPo)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                    <Field label="Allow invoice without GRN"><Select defaultValue={String(settings.allowInvoiceWithoutGrn)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                    <Field label="Allow over receipt"><Select defaultValue={String(settings.allowOverReceipt)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                </div>
                <div className="mt-4 flex justify-end gap-3"><Button variant="secondary">Initialize Defaults</Button><Button variant="blue">Save Settings</Button></div>
            </FormSection>
        </form>
    );
}

export function PurchaseOrderTable({ rows }: { rows: PurchaseOrder[] }) {
    return (
        <DataTable
            columns={[
                { header: 'PO #', key: 'poNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/purchase/orders/${row.id}`}>{row.poNumber}</Link> },
                { header: 'Supplier', key: 'supplier' },
                { header: 'Order Date', key: 'orderDate' },
                { header: 'Expected', key: 'expectedDate' },
                { header: 'Backend Total', key: 'grandTotal' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Updated', key: 'updatedAt' },
                { header: 'Actions', key: 'actions', render: (row) => <RowActions editPath={`/purchase/orders/${row.id}/edit`} viewPath={`/purchase/orders/${row.id}`} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function GrnTable({ rows }: { rows: GoodsReceivedNote[] }) {
    return (
        <DataTable
            columns={[
                { header: 'GRN #', key: 'grnNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/purchase/grns/${row.id}`}>{row.grnNumber}</Link> },
                { header: 'Supplier', key: 'supplier' },
                { header: 'Source PO', key: 'sourcePo' },
                { header: 'GRN Date', key: 'grnDate' },
                { header: 'Inventory Status', key: 'inventoryStatus' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <RowActions editPath={`/purchase/grns/${row.id}/edit`} viewPath={`/purchase/grns/${row.id}`} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function PurchaseInvoiceTable({ rows }: { rows: PurchaseInvoice[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Invoice #', key: 'invoiceNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/purchase/invoices/${row.id}`}>{row.invoiceNumber}</Link> },
                { header: 'Supplier', key: 'supplier' },
                { header: 'Source', key: 'sourceReference' },
                { header: 'Backend Total', key: 'grandTotal' },
                { header: 'Paid', key: 'paidAmount' },
                { header: 'Balance', key: 'balance' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <RowActions editPath={`/purchase/invoices/${row.id}/edit`} viewPath={`/purchase/invoices/${row.id}`} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function PurchasePaymentTable({ rows }: { rows: PurchasePayment[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Payment #', key: 'paymentNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/purchase/payments/${row.id}`}>{row.paymentNumber}</Link> },
                { header: 'Supplier', key: 'supplier' },
                { header: 'Method', key: 'method' },
                { header: 'Amount', key: 'amount' },
                { header: 'Unallocated', key: 'unallocatedAmount' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Payment Date', key: 'paymentDate' },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function PurchaseReturnTable({ rows }: { rows: PurchaseReturn[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Return #', key: 'returnNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/purchase/returns/${row.id}`}>{row.returnNumber}</Link> },
                { header: 'Supplier', key: 'supplier' },
                { header: 'Source', key: 'sourceReference' },
                { header: 'Backend Total', key: 'returnTotal' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Updated', key: 'updatedAt' },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

function RowActions({ editPath, viewPath }: { editPath: string; viewPath: string }) {
    return <div className="flex gap-2"><Link to={viewPath}><Button variant="secondary">View</Button></Link><Link to={editPath}><Button variant="ghost">Edit</Button></Link></div>;
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

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
            <p className="mt-1 font-semibold text-slate-900">{value}</p>
        </div>
    );
}

const draftOrderLine: PurchaseOrderLine = {
    backendConvertedQuantity: 'Backend converted',
    discountAmount: 'Backend calculated',
    id: 'draft-po-line',
    item: 'Select item',
    lineTotal: 'Backend calculated',
    orderedQuantity: 'Input quantity',
    receivedQuantity: 'Backend tracked',
    remainingQuantity: 'Backend calculated',
    taxAmount: 'Backend calculated',
    unitPrice: 'Input / backend resolved',
    uom: 'Select UOM',
};

const draftGrnLine: GoodsReceivedNoteLine = {
    acceptedQuantity: 'Input quantity',
    backendBaseQuantity: 'Backend converted',
    id: 'draft-grn-line',
    item: 'Select item',
    orderedQuantity: 'Backend PO qty if linked',
    rejectedQuantity: 'Input quantity',
    sourceLine: 'Optional PO line',
    uom: 'Select UOM',
};

const draftInvoiceLine: PurchaseInvoiceLine = {
    discountAmount: 'Backend calculated',
    id: 'draft-invoice-line',
    invoiceQuantity: 'Input quantity',
    item: 'Select item',
    lineTotal: 'Backend calculated',
    sourceLine: 'Optional PO/GRN line',
    taxAmount: 'Backend calculated',
    unitPrice: 'Input / backend resolved',
    uom: 'Select UOM',
};

const draftReturnLine: PurchaseReturnLine = {
    backendReturnableQuantity: 'Backend calculated',
    id: 'draft-return-line',
    item: 'Select item',
    returnQuantity: 'Input quantity',
    sourceLine: 'Source GRN/invoice line',
    uom: 'Select UOM',
};
