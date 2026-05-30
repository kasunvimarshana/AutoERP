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
    CustomerAdvance,
    CustomerRefund,
    GoodsDeliveryNote,
    GoodsDeliveryNoteLine,
    SalesAuditEntry,
    SalesCreditCheckResult,
    SalesDashboardMetric,
    SalesFinancePostingPreview,
    SalesInventoryEffect,
    SalesInvoice,
    SalesInvoiceLine,
    SalesOrder,
    SalesOrderLine,
    SalesPayment,
    SalesPaymentAllocation,
    SalesQuotation,
    SalesQuotationLine,
    SalesReturn,
    SalesReturnLine,
    SalesSettings,
    SalesStockAvailabilityPreview,
} from '../types/sales.types';

export function SalesDashboardCards({ metrics }: { metrics: SalesDashboardMetric[] }) {
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

export function SalesQuotationForm() {
    return (
        <form className="space-y-5">
            <FormSection description="Quotation/proforma is a clean frontend placeholder until backend quotation endpoints are added." title="Quotation Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Customer"><Input placeholder="Select customer" /></Field>
                    <Field label="Quotation date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Expiry date"><Input type="date" /></Field>
                    <Field label="Price list"><Input placeholder="Backend price list" /></Field>
                    <Field label="Status"><Select><option>Draft</option><option>Send</option></Select></Field>
                    <Field label="Notes"><Textarea placeholder="Quotation notes" /></Field>
                </div>
            </FormSection>
            <FormSection description="Pricing, discounts, tax, and totals are previewed by backend when quotation APIs exist." title="Quotation Lines">
                <SalesQuotationLineTable rows={[draftQuotationLine]} />
                <div className="mt-4 flex justify-end gap-3"><Button variant="secondary">Save Draft</Button><Button variant="blue">Preview Price</Button></div>
            </FormSection>
        </form>
    );
}

export function SalesQuotationLineTable({ rows }: { rows: SalesQuotationLine[] }) {
    return <SimpleTable columns={[['item', 'Item'], ['quantity', 'Qty'], ['uom', 'UOM'], ['unitPrice', 'Backend Price'], ['discountAmount', 'Discount'], ['taxAmount', 'Tax'], ['lineTotal', 'Line Total']]} rows={rows} />;
}

export function SalesOrderForm({ mode = 'create' }: { mode?: 'create' | 'edit' }) {
    return (
        <form className="space-y-5">
            <FormSection description="Customer eligibility, credit, stock availability, pricing, and workflow defaults are backend validated." title="Sales Order Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Customer"><Input placeholder="Select customer" /></Field>
                    <Field label="Order date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Expected delivery"><Input type="date" /></Field>
                    <Field label="Payment term"><Input placeholder="Backend customer/default term" /></Field>
                    <Field label="Warehouse"><Input placeholder="Default warehouse" /></Field>
                    <Field label="Workflow"><Select><option>Save as draft</option><option>Submit for approval</option></Select></Field>
                    <Field label="Notes"><Textarea placeholder="Delivery notes, customer PO, internal remarks" /></Field>
                </div>
            </FormSection>
            <SalesCreditCheckPanel />
            <FormSection description="Frontend collects item, UOM, quantity, and price inputs. Backend resolves pricing, stock availability, discounts, tax, and totals." title="Order Lines">
                <SalesOrderLineTable rows={[draftOrderLine]} />
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    <Button variant="secondary">Save Draft</Button>
                    <Button variant="blue">Preview Price / Stock</Button>
                    <Button>{mode === 'edit' ? 'Update With Lines' : 'Create With Lines'}</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function SalesOrderLineTable({ rows }: { rows: SalesOrderLine[] }) {
    return (
        <SimpleTable
            columns={[
                ['item', 'Item'],
                ['orderedQuantity', 'Qty'],
                ['uom', 'UOM'],
                ['backendConvertedQuantity', 'Backend Base Qty'],
                ['stockAvailability', 'Stock Availability'],
                ['unitPrice', 'Unit Price'],
                ['discountAmount', 'Discount'],
                ['taxAmount', 'Tax'],
                ['lineTotal', 'Line Total'],
                ['deliveredQuantity', 'Delivered'],
                ['remainingQuantity', 'Remaining'],
            ]}
            rows={rows}
        />
    );
}

export function SalesOrderSummaryCard({ order }: { order: SalesOrder }) {
    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Sales Order</p>
                    <h2 className="mt-1 text-2xl font-bold text-slate-950">{order.soNumber}</h2>
                    <p className="mt-2 text-sm text-slate-500">{order.customer} - {order.workflow}</p>
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

export function SalesWorkflowActions({ entityId, entityType, status }: { entityId: string; entityType: string; status: string }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Entity', value: `${entityType} / ${entityId}` },
                { label: 'Current status', value: status },
                { label: 'Allowed actions', value: 'Backend permission/status result' },
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

export function GdnForm({ mode = 'create' }: { mode?: 'create' | 'edit' }) {
    return (
        <form className="space-y-5">
            <FormSection description="GDN records quantity delivered. Backend validates sales order lines, stock availability, UOM, warehouse, batch/serial, and stock issue." title="Delivery / GDN Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Customer"><Input placeholder="Select customer" /></Field>
                    <Field label="Source order optional"><Input placeholder="SO-2026-0001 or direct delivery" /></Field>
                    <Field label="Delivery date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Warehouse"><Input placeholder="Issue warehouse" /></Field>
                    <Field label="Picking status"><Select><option>Pending picking</option><option>Picked</option></Select></Field>
                    <Field label="Notes"><Textarea placeholder="Dispatch notes" /></Field>
                </div>
            </FormSection>
            <FormSection description="Delivered/rejected quantities are submitted as inputs. Backend returns authoritative stock movement effect." title="Delivered Lines">
                <GdnLineTable rows={[draftGdnLine]} />
                <div className="mt-4 flex justify-end gap-3"><Button variant="secondary">Save Draft</Button><Button variant="blue">{mode === 'edit' ? 'Update GDN' : 'Create GDN'}</Button></div>
            </FormSection>
        </form>
    );
}

export function GdnLineTable({ rows }: { rows: GoodsDeliveryNoteLine[] }) {
    return <SimpleTable columns={[['item', 'Item'], ['sourceLine', 'Source Line'], ['orderedQuantity', 'Ordered'], ['pickedQuantity', 'Picked'], ['deliveredQuantity', 'Delivered'], ['rejectedQuantity', 'Rejected'], ['uom', 'UOM'], ['backendBaseQuantity', 'Backend Base Qty']]} rows={rows} />;
}

export function GdnInventoryEffectPanel({ effects }: { effects: SalesInventoryEffect[] }) {
    return (
        <PreviewPanel status="Inventory Preview" subtitle="Readonly stock effect returned by Inventory/Sales backend. Frontend does not calculate stock." title="Inventory Effect">
            <SimpleTable columns={[['sourceReference', 'Source'], ['item', 'Item'], ['warehouse', 'Warehouse'], ['quantityEffect', 'Quantity Effect'], ['decision', 'Decision']]} rows={effects.map((effect, index) => ({ ...effect, id: `${effect.sourceReference}-${index}` }))} />
        </PreviewPanel>
    );
}

export function SalesInvoiceForm({ mode = 'create' }: { mode?: 'create' | 'edit' }) {
    return (
        <form className="space-y-5">
            <FormSection description="Supports direct invoice, invoice from sales order, GDN, or multiple GDNs according to backend settings." title="Customer Invoice Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Customer"><Input placeholder="Select customer" /></Field>
                    <Field label="Invoice source"><Select><option>Direct invoice</option><option>From sales order</option><option>From GDN</option><option>From multiple GDNs</option></Select></Field>
                    <Field label="Source reference"><Input placeholder="SO / GDN / document reference" /></Field>
                    <Field label="Invoice date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Due date"><Input type="date" /></Field>
                    <Field label="Customer PO/ref"><Input placeholder="Customer reference" /></Field>
                    <Field label="Notes"><Textarea placeholder="Invoice notes" /></Field>
                </div>
            </FormSection>
            <FormSection description="Line amount, pricing, discounts, taxes, UOM conversion, receivable amount, and balances are previewed by backend only." title="Invoice Lines">
                <SalesInvoiceLineTable rows={[draftInvoiceLine]} />
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    <Button variant="secondary">Save Draft</Button>
                    <Button variant="blue">Preview Calculation</Button>
                    <Button>{mode === 'edit' ? 'Update Invoice' : 'Create Invoice'}</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function SalesInvoiceLineTable({ rows }: { rows: SalesInvoiceLine[] }) {
    return <SimpleTable columns={[['item', 'Item'], ['sourceLine', 'Source Line'], ['invoiceQuantity', 'Invoice Qty'], ['uom', 'UOM'], ['unitPrice', 'Backend Price'], ['discountAmount', 'Discount'], ['taxAmount', 'Tax'], ['lineTotal', 'Line Total']]} rows={rows} />;
}

export function SalesInvoiceCalculationPanel() {
    return (
        <PreviewPanel
            rows={[
                { label: 'Pricing', value: 'Backend resolved' },
                { label: 'Subtotal', value: 'Backend calculated' },
                { label: 'Discount', value: 'Backend calculated' },
                { label: 'Tax', value: 'Backend calculated' },
                { label: 'Grand total', value: 'Backend calculated' },
                { label: 'UOM conversion', value: 'Backend returned' },
            ]}
            status="Backend Preview"
            subtitle="Invoice total, price, discount, tax, UOM, and receivable values are never calculated in the frontend."
            title="Invoice Calculation Preview"
        />
    );
}

export function SalesInvoiceDocumentPanel() {
    return (
        <PreviewPanel
            rows={[
                { label: 'Document definition', value: 'Customer Invoice Standard' },
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

export function SalesPaymentForm() {
    return (
        <form className="space-y-5">
            <FormSection description="Payment is routed through Payment module. Backend validates receivable invoices, allocations, balances, and posting." title="Customer Payment">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Customer"><Input placeholder="Select customer" /></Field>
                    <Field label="Payment date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Payment method"><Select><option>Bank Transfer</option><option>Cash</option><option>Check</option><option>Card</option></Select></Field>
                    <Field label="Amount"><Input placeholder="Input amount only" /></Field>
                    <Field label="Reference"><Input placeholder="Bank/check/reference number" /></Field>
                    <Field label="Source invoice optional"><Input placeholder="SINV-..." /></Field>
                </div>
            </FormSection>
            <SalesPaymentAllocationPanel />
            <div className="flex justify-end gap-3"><Button variant="secondary">Save Draft</Button><Button variant="blue">Preview Allocation</Button><Button>Create Payment</Button></div>
        </form>
    );
}

export function SalesPaymentAllocationPanel({ allocations = [] }: { allocations?: SalesPaymentAllocation[] }) {
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

export function CustomerAdvancePanel({ advances }: { advances: CustomerAdvance[] }) {
    return <SimpleTable columns={[['advanceNumber', 'Advance #'], ['customer', 'Customer'], ['amount', 'Amount'], ['remainingAmount', 'Backend Remaining'], ['status', 'Status']]} rows={advances} />;
}

export function SalesReturnForm() {
    return (
        <form className="space-y-5">
            <FormSection description="Returnable quantity, stock reversal, AR adjustment, and refund eligibility are backend-owned." title="Sales Return">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Customer"><Input placeholder="Select customer" /></Field>
                    <Field label="Source document"><Input placeholder="SO / GDN / Invoice / Direct" /></Field>
                    <Field label="Return date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Reason"><Input placeholder="Damage, wrong item, customer return..." /></Field>
                    <Field label="Notes"><Textarea placeholder="Return notes" /></Field>
                </div>
            </FormSection>
            <FormSection description="Backend validates returnable quantities and previews inventory/AR effects." title="Return Lines">
                <SalesReturnLineTable rows={[draftReturnLine]} />
                <div className="mt-4 flex justify-end gap-3"><Button variant="blue">Preview Return Effect</Button><Button>Create Return</Button></div>
            </FormSection>
        </form>
    );
}

export function SalesReturnLineTable({ rows }: { rows: SalesReturnLine[] }) {
    return <SimpleTable columns={[['item', 'Item'], ['sourceLine', 'Source Line'], ['backendReturnableQuantity', 'Backend Returnable'], ['returnQuantity', 'Return Qty'], ['uom', 'UOM']]} rows={rows} />;
}

export function CustomerRefundPanel({ refunds }: { refunds: CustomerRefund[] }) {
    return <SimpleTable columns={[['refundNumber', 'Refund #'], ['customer', 'Customer'], ['sourceReference', 'Source'], ['amount', 'Backend Amount'], ['method', 'Method'], ['status', 'Status']]} rows={refunds} />;
}

export function SalesFinancePostingPanel({ preview }: { preview: SalesFinancePostingPreview }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'AR impact', value: preview.calculated.arImpact },
                { label: 'COGS impact', value: preview.calculated.cogsImpact },
                { label: 'Tax impact', value: preview.calculated.taxImpact },
                { label: 'Journal impact', value: preview.calculated.journalImpact },
                { label: 'Eligibility', value: preview.calculated.eligibility },
            ]}
            status="Finance Preview"
            title="Finance / AR / COGS Posting"
        >
            <SimpleTable columns={[['account', 'Account'], ['debit', 'Debit'], ['credit', 'Credit'], ['description', 'Description']]} rows={preview.lines.map((line, index) => ({ ...line, id: `posting-${index}` }))} />
        </PreviewPanel>
    );
}

export function SalesStockAvailabilityPanel({ preview }: { preview?: SalesStockAvailabilityPreview }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Requested quantity', value: preview?.calculated.requestedQuantity ?? 'Input only' },
                { label: 'Available quantity', value: preview?.calculated.availableQuantity ?? 'Backend calculated' },
                { label: 'Reserved quantity', value: preview?.calculated.reservedQuantity ?? 'Backend calculated' },
                { label: 'Decision', value: preview?.calculated.decision ?? 'Backend decision' },
            ]}
            status="Stock Preview"
            title="Stock Availability"
        />
    );
}

export function SalesCreditCheckPanel({ result }: { result?: SalesCreditCheckResult }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Credit limit', value: result?.calculated.creditLimit ?? 'Backend returned' },
                { label: 'Current exposure', value: result?.calculated.currentExposure ?? 'Backend calculated' },
                { label: 'Projected exposure', value: result?.calculated.projectedExposure ?? 'Backend calculated' },
                { label: 'Decision', value: result?.calculated.decision ?? 'Backend credit decision' },
            ]}
            status="Credit Check"
            subtitle="Credit exposure and eligibility are backend-owned."
            title="Customer Credit Check"
        />
    );
}

export function SalesSourceReferencePanel({ reference }: { reference?: string }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Source reference', value: reference ?? 'Direct / not linked' },
                { label: 'Allowed workflow', value: 'Quotation -> SO -> GDN -> Invoice, SO -> Invoice, Direct Invoice' },
            ]}
            status="Source"
            title="Source Reference"
        />
    );
}

export function SalesActivityTimeline({ rows }: { rows: SalesAuditEntry[] }) {
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

export function SalesSettingsForm({ settings }: { settings: SalesSettings }) {
    return (
        <form className="space-y-5">
            <FormSection description="Settings guide backend workflow behavior; global configuration stays outside Sales." title="Accounting, Document, and Warehouse Defaults">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Default receivable account"><Input defaultValue={settings.defaultReceivableAccount} /></Field>
                    <Field label="Default income account"><Input defaultValue={settings.defaultIncomeAccount} /></Field>
                    <Field label="Default inventory account"><Input defaultValue={settings.defaultInventoryAccount} /></Field>
                    <Field label="Default COGS account"><Input defaultValue={settings.defaultCogsAccount} /></Field>
                    <Field label="Default tax group"><Input defaultValue={settings.defaultTaxGroup} /></Field>
                    <Field label="Default warehouse"><Input defaultValue={settings.defaultWarehouse} /></Field>
                    <Field label="Payment term"><Input defaultValue={settings.defaultPaymentTerm} /></Field>
                    <Field label="Invoice document definition"><Input defaultValue={settings.invoiceDocumentDefinition} /></Field>
                    <Field label="Credit check behavior"><Input defaultValue={settings.creditCheckBehavior} /></Field>
                </div>
            </FormSection>
            <FormSection description="Sequences are previewed/generated by backend Sequence/Document services." title="Sequences and Workflow Rules">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Quotation sequence"><Input defaultValue={settings.quotationSequence} /></Field>
                    <Field label="Sales order sequence"><Input defaultValue={settings.salesOrderSequence} /></Field>
                    <Field label="Delivery sequence"><Input defaultValue={settings.deliverySequence} /></Field>
                    <Field label="Invoice sequence"><Input defaultValue={settings.invoiceSequence} /></Field>
                    <Field label="Return sequence"><Input defaultValue={settings.returnSequence} /></Field>
                    <Field label="Stock deduction timing"><Input defaultValue={settings.stockDeductionTiming} /></Field>
                    <Field label="Allow direct invoice"><Select defaultValue={String(settings.allowDirectInvoice)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                    <Field label="Allow delivery without order"><Select defaultValue={String(settings.allowDeliveryWithoutOrder)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                    <Field label="Allow invoice without delivery"><Select defaultValue={String(settings.allowInvoiceWithoutDelivery)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                    <Field label="Allow negative stock"><Select defaultValue={String(settings.allowNegativeStock)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                </div>
                <div className="mt-4 flex justify-end gap-3"><Button variant="secondary">Initialize Defaults</Button><Button variant="blue">Save Settings</Button></div>
            </FormSection>
        </form>
    );
}

export function SalesQuotationTable({ rows }: { rows: SalesQuotation[] }) {
    return <DataTable columns={[{ header: 'Quotation #', key: 'quotationNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/sales/quotations/${row.id}`}>{row.quotationNumber}</Link> }, { header: 'Customer', key: 'customer' }, { header: 'Date', key: 'quotationDate' }, { header: 'Expiry', key: 'expiryDate' }, { header: 'Backend Total', key: 'grandTotal' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} />;
}

export function SalesOrderTable({ rows }: { rows: SalesOrder[] }) {
    return (
        <DataTable
            columns={[
                { header: 'SO #', key: 'soNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/sales/orders/${row.id}`}>{row.soNumber}</Link> },
                { header: 'Customer', key: 'customer' },
                { header: 'Order Date', key: 'orderDate' },
                { header: 'Expected', key: 'expectedDate' },
                { header: 'Backend Total', key: 'grandTotal' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Updated', key: 'updatedAt' },
                { header: 'Actions', key: 'actions', render: (row) => <RowActions editPath={`/sales/orders/${row.id}/edit`} viewPath={`/sales/orders/${row.id}`} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function GdnTable({ rows }: { rows: GoodsDeliveryNote[] }) {
    return (
        <DataTable
            columns={[
                { header: 'GDN #', key: 'gdnNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/sales/deliveries/${row.id}`}>{row.gdnNumber}</Link> },
                { header: 'Customer', key: 'customer' },
                { header: 'Source SO', key: 'sourceOrder' },
                { header: 'Delivery Date', key: 'deliveryDate' },
                { header: 'Inventory Status', key: 'inventoryStatus' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <RowActions editPath={`/sales/deliveries/${row.id}/edit`} viewPath={`/sales/deliveries/${row.id}`} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function SalesInvoiceTable({ rows }: { rows: SalesInvoice[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Invoice #', key: 'invoiceNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/sales/invoices/${row.id}`}>{row.invoiceNumber}</Link> },
                { header: 'Customer', key: 'customer' },
                { header: 'Source', key: 'sourceReference' },
                { header: 'Backend Total', key: 'grandTotal' },
                { header: 'Paid', key: 'paidAmount' },
                { header: 'Balance', key: 'balance' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <RowActions editPath={`/sales/invoices/${row.id}/edit`} viewPath={`/sales/invoices/${row.id}`} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function SalesPaymentTable({ rows }: { rows: SalesPayment[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Payment #', key: 'paymentNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/sales/payments/${row.id}`}>{row.paymentNumber}</Link> },
                { header: 'Customer', key: 'customer' },
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

export function SalesReturnTable({ rows }: { rows: SalesReturn[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Return #', key: 'returnNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/sales/returns/${row.id}`}>{row.returnNumber}</Link> },
                { header: 'Customer', key: 'customer' },
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

const draftQuotationLine: SalesQuotationLine = { discountAmount: 'Backend calculated', id: 'draft-quotation-line', item: 'Select item', lineTotal: 'Backend calculated', quantity: 'Input quantity', taxAmount: 'Backend calculated', unitPrice: 'Backend resolved', uom: 'Select UOM' };
const draftOrderLine: SalesOrderLine = { backendConvertedQuantity: 'Backend converted', deliveredQuantity: 'Backend tracked', discountAmount: 'Backend calculated', id: 'draft-so-line', item: 'Select item', lineTotal: 'Backend calculated', orderedQuantity: 'Input quantity', remainingQuantity: 'Backend calculated', stockAvailability: 'Backend checked', taxAmount: 'Backend calculated', unitPrice: 'Input / backend resolved', uom: 'Select UOM' };
const draftGdnLine: GoodsDeliveryNoteLine = { backendBaseQuantity: 'Backend converted', deliveredQuantity: 'Input quantity', id: 'draft-gdn-line', item: 'Select item', orderedQuantity: 'Backend SO qty if linked', pickedQuantity: 'Input quantity', rejectedQuantity: 'Input quantity', sourceLine: 'Optional SO line', uom: 'Select UOM' };
const draftInvoiceLine: SalesInvoiceLine = { discountAmount: 'Backend calculated', id: 'draft-invoice-line', invoiceQuantity: 'Input quantity', item: 'Select item', lineTotal: 'Backend calculated', sourceLine: 'Optional SO/GDN line', taxAmount: 'Backend calculated', unitPrice: 'Input / backend resolved', uom: 'Select UOM' };
const draftReturnLine: SalesReturnLine = { backendReturnableQuantity: 'Backend calculated', id: 'draft-return-line', item: 'Select item', returnQuantity: 'Input quantity', sourceLine: 'Source GDN/invoice line', uom: 'Select UOM' };
