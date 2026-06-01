import { useCallback, useEffect, useRef, useState, type Dispatch, type FormEvent, type ReactNode, type SetStateAction } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
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
    GrnFormInput,
    GoodsReceivedNote,
    GoodsReceivedNoteLine,
    PurchaseAdvance,
    PurchaseAuditEntry,
    PurchaseCalculationPreview,
    PurchaseDashboardMetric,
    PurchaseFinancePostingPreview,
    PurchaseInventoryEffect,
    PurchaseInvoice,
    PurchaseInvoiceFormInput,
    PurchaseInvoiceLine,
    PurchaseLineFormInput,
    PurchaseLookupOption,
    PurchaseOrder,
    PurchaseOrderFormInput,
    PurchaseOrderLine,
    PurchasePayment,
    PurchasePaymentAllocation,
    PurchasePaymentFormInput,
    PurchaseReturn,
    PurchaseReturnFormInput,
    PurchaseReturnLine,
    PurchaseSettings,
    PurchaseSettingsFormInput,
    SupplierRefund,
} from '../types/purchase.types';
import { purchaseApi } from '../services/purchaseApi';

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

export function PurchaseOrderForm({ initialOrder, mode = 'create' }: { initialOrder?: PurchaseOrder; mode?: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const { addLine, errors, globalError, isLoading, lookupActions, lookups, removeLine, setField, setGlobalError, setLineField, submit, values } = usePurchaseOrderForm(initialOrder);

    async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
        event.preventDefault();
        setGlobalError('');
        const response = mode === 'edit' && initialOrder
            ? await submit(() => purchaseApi.orders.updateWithLines(initialOrder.id, values))
            : await submit(() => purchaseApi.orders.createWithLines(values));
        if (response) {
            navigate(`/purchase/orders/${response.data.id}`);
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void handleSubmit(event)}>
            <FormSection description="Supplier eligibility, payment terms, workflow defaults, and sequence values are backend validated." title="Purchase Order Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={errors.supplier_id} label="Supplier"><LookupSelect disabled={isLoading} isLoading={lookupActions.loading.suppliers} onOpen={lookupActions.loadSuppliers} onChange={(value) => setField('supplierId', value)} options={lookups.suppliers} placeholder="Select supplier" value={values.supplierId} /></Field>
                    <Field error={errors.po_number} label="PO number"><Input onChange={(event) => setField('poNumber', event.target.value)} value={values.poNumber} /></Field>
                    <Field error={errors.order_date} label="Order date"><Input onChange={(event) => setField('orderDate', event.target.value)} type="date" value={values.orderDate} /></Field>
                    <Field error={errors.expected_date} label="Expected date"><Input onChange={(event) => setField('expectedDate', event.target.value)} type="date" value={values.expectedDate ?? ''} /></Field>
                    <Field error={errors.warehouse_id} label="Warehouse"><LookupSelect disabled={isLoading} isLoading={lookupActions.loading.warehouses} onOpen={lookupActions.loadWarehouses} onChange={(value) => setField('warehouseId', value)} options={lookups.warehouses} placeholder="Select warehouse" value={values.warehouseId} /></Field>
                    <Field label="Workflow"><Select onChange={(event) => setField('status', event.target.value)} value={values.status ?? 'draft'}><option value="draft">Save as draft</option><option value="submitted">Submit for approval</option></Select></Field>
                    <Field error={errors.notes} label="Notes"><Textarea onChange={(event) => setField('notes', event.target.value)} placeholder="Supplier instructions, delivery notes, internal remarks" value={values.notes ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Frontend collects item, UOM, quantity, price inputs. Backend resolves UOM conversion, discounts, tax, and totals." title="Order Lines">
                <div className="space-y-4">
                    {values.lines.map((line, index) => (
                        <PurchaseLineEditor errors={errors} key={line.clientKey} line={line} lineIndex={index} lookupActions={lookupActions} lookups={lookups} onLineChange={setLineField} onRemove={values.lines.length > 1 ? () => removeLine(index) : undefined} quantityLabel="Ordered qty" />
                    ))}
                </div>
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    {globalError ? <p className="mr-auto text-sm font-semibold text-red-600">{globalError}</p> : null}
                    <Button disabled={isLoading} onClick={addLine} type="button" variant="secondary">Add Line</Button>
                    <Button disabled={isLoading} type="submit" variant="blue">{mode === 'edit' ? 'Update With Lines' : 'Create With Lines'}</Button>
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

export function PurchaseWorkflowActions({ entityId, entityType, sourceId, sourceType, status }: { entityId: string; entityType: string; sourceId?: string; sourceType?: string; status: string }) {
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function transition(action: string): Promise<void> {
        setIsSubmitting(true);
        setMessage('');
        try {
            if (entityType === 'purchase_order') {
                await purchaseApi.orders.transition(entityId, action);
            } else if (entityType === 'grn_header') {
                await purchaseApi.grns.transition(entityId, action);
            } else if (entityType === 'purchase_return') {
                await purchaseApi.returns.transition(entityId, action);
            } else if (entityType === 'purchase_invoice' && sourceType && sourceId) {
                if (action === 'post') await purchaseApi.invoices.post(entityId, sourceType, sourceId);
                if (action === 'cancel') await purchaseApi.invoices.cancel(entityId, sourceType, sourceId);
                if (action === 'reverse') await purchaseApi.invoices.reverse(entityId, sourceType, sourceId);
            } else {
                setMessage('This record needs persisted source context before workflow actions are available.');
                return;
            }
            setMessage(`${action} requested from backend.`);
        } catch (error) {
            setMessage(error instanceof Error ? error.message : 'Workflow transition failed.');
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <PreviewPanel
            rows={[
                { label: 'Entity', value: `${entityType} ${entityId}` },
                { label: 'Current status', value: status },
                { label: 'Allowed actions', value: entityType === 'purchase_invoice' ? 'Invoice post/cancel/reverse endpoints' : 'Validated by backend on submit.' },
            ]}
            status="Workflow"
            title="Workflow Actions"
        >
            <div className="flex flex-wrap items-center gap-2">
                {(entityType === 'purchase_invoice' ? ['post', 'cancel', 'reverse'] : ['submit', 'approve', 'cancel', 'reverse']).map((action) => (
                    <Button disabled={isSubmitting || (entityType === 'purchase_invoice' && (!sourceType || !sourceId))} key={action} onClick={() => void transition(action)} type="button" variant={action === 'submit' || action === 'post' ? 'blue' : 'secondary'}>
                        {action.charAt(0).toUpperCase() + action.slice(1)}
                    </Button>
                ))}
                {message ? <span className="text-sm text-slate-600">{message}</span> : null}
            </div>
        </PreviewPanel>
    );
}

export function GrnForm({ initialGrn, mode = 'create' }: { initialGrn?: GoodsReceivedNote; mode?: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const { addLine, errors, globalError, isLoading, lookupActions, lookups, removeLine, setField, setGlobalError, setLineField, submit, values } = useGrnForm(initialGrn);

    async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
        event.preventDefault();
        setGlobalError('');
        const response = mode === 'edit' && initialGrn
            ? await submit(() => purchaseApi.grns.updateWithLines(initialGrn.id, values))
            : await submit(() => purchaseApi.grns.createDirect(values));
        if (response) {
            navigate(`/purchase/grns/${response.data.id}`);
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void handleSubmit(event)}>
            <FormSection description="GRN records quantity received. Backend validates PO lines, UOM conversion, warehouse, batch/serial, and stock effect." title="GRN Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={errors.supplier_id} label="Supplier"><LookupSelect disabled={isLoading} isLoading={lookupActions.loading.suppliers} onOpen={lookupActions.loadSuppliers} onChange={(value) => setField('supplierId', value)} options={lookups.suppliers} placeholder="Select supplier" value={values.supplierId} /></Field>
                    <Field error={errors.grn_number} label="GRN number"><Input onChange={(event) => setField('grnNumber', event.target.value)} value={values.grnNumber} /></Field>
                    <Field label="Source PO optional"><LookupSelect disabled={isLoading} isLoading={lookupActions.loading.purchaseOrders} onOpen={lookupActions.loadPurchaseOrders} onChange={(value) => setField('purchaseOrderId', value)} options={lookups.purchaseOrders} placeholder="Select linked PO" value={values.purchaseOrderId ?? ''} /></Field>
                    <Field error={errors.received_date} label="GRN date"><Input onChange={(event) => setField('grnDate', event.target.value)} type="date" value={values.grnDate} /></Field>
                    <Field error={errors.warehouse_id} label="Warehouse"><LookupSelect disabled={isLoading} isLoading={lookupActions.loading.warehouses} onOpen={lookupActions.loadWarehouses} onChange={(value) => setField('warehouseId', value)} options={lookups.warehouses} placeholder="Select warehouse" value={values.warehouseId} /></Field>
                    <Field label="Status"><Select onChange={(event) => setField('status', event.target.value)} value={values.status ?? 'draft'}><option value="draft">Draft</option><option value="submitted">Submitted</option><option value="confirmed">Confirmed</option></Select></Field>
                    <Field error={errors.notes} label="Notes"><Textarea onChange={(event) => setField('notes', event.target.value)} placeholder="Receiving notes" value={values.notes ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Accepted/rejected quantities are submitted as inputs. Backend returns authoritative stock movement effect." title="Received Lines">
                <div className="space-y-4">
                    {values.lines.map((line, index) => (
                        <PurchaseLineEditor errors={errors} key={line.clientKey} line={line} lineIndex={index} lookupActions={lookupActions} lookups={lookups} onLineChange={setLineField} onRemove={values.lines.length > 1 ? () => removeLine(index) : undefined} quantityLabel="Received qty" />
                    ))}
                </div>
                <div className="mt-4 flex justify-end gap-3">{globalError ? <p className="mr-auto text-sm font-semibold text-red-600">{globalError}</p> : null}<Button disabled={isLoading} onClick={addLine} type="button" variant="secondary">Add Line</Button><Button disabled={isLoading} type="submit" variant="blue">{mode === 'edit' ? 'Update GRN' : 'Create GRN'}</Button></div>
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
    const navigate = useNavigate();
    const { addLine, errors, globalError, isLoading, lookupActions, lookups, preview, previewInvoice, removeLine, setField, setGlobalError, setLineField, submit, values } = useInvoiceForm();

    async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
        event.preventDefault();
        setGlobalError('');
        const response = await submit(() => values.sourceType === 'grn_header'
            ? purchaseApi.invoices.createFromGrn(values.sourceId, values)
            : purchaseApi.invoices.createFromPo(values.sourceId, values));
        if (response) {
            navigate('/purchase/invoices');
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void handleSubmit(event)}>
            <FormSection description="Supports direct invoice, invoice from PO, GRN, or multiple GRNs according to backend settings." title="Supplier Invoice Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Invoice source"><Select onChange={(event) => setField('sourceType', event.target.value as PurchaseInvoiceFormInput['sourceType'])} value={values.sourceType}><option value="purchase_order">From PO</option><option value="grn_header">From GRN</option></Select></Field>
                    <Field error={errors.source_id} label="Source document"><LookupSelect disabled={isLoading} isLoading={values.sourceType === 'grn_header' ? lookupActions.loading.grns : lookupActions.loading.purchaseOrders} onOpen={values.sourceType === 'grn_header' ? lookupActions.loadGrns : lookupActions.loadPurchaseOrders} onChange={(value) => setField('sourceId', value)} options={values.sourceType === 'grn_header' ? lookups.grns : lookups.purchaseOrders} placeholder={values.sourceType === 'grn_header' ? 'Select GRN' : 'Select purchase order'} value={values.sourceId} /></Field>
                    <Field error={errors.invoice_date} label="Invoice date"><Input onChange={(event) => setField('invoiceDate', event.target.value)} type="date" value={values.invoiceDate} /></Field>
                    <Field error={errors.due_date} label="Due date"><Input onChange={(event) => setField('dueDate', event.target.value)} type="date" value={values.dueDate ?? ''} /></Field>
                    <Field label="Supplier invoice no"><Input onChange={(event) => setField('supplierInvoiceNumber', event.target.value)} placeholder="Supplier reference" value={values.supplierInvoiceNumber ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Line amount, discounts, taxes, UOM conversion, payable amount, and balances are previewed by backend only." title="Invoice Lines">
                <div className="space-y-4">
                    {values.lines.map((line, index) => (
                        <PurchaseLineEditor errors={errors} key={line.clientKey} line={line} lineIndex={index} lookupActions={lookupActions} lookups={lookups} onLineChange={setLineField} onRemove={values.lines.length > 1 ? () => removeLine(index) : undefined} quantityLabel="Invoice qty" />
                    ))}
                </div>
                {preview ? <PurchaseInvoiceCalculationPanel preview={preview} /> : null}
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    {globalError ? <p className="mr-auto text-sm font-semibold text-red-600">{globalError}</p> : null}
                    <Button disabled={isLoading} onClick={addLine} type="button" variant="secondary">Add Line</Button>
                    <Button disabled={isLoading} onClick={() => void previewInvoice()} type="button" variant="secondary">Preview Calculation</Button>
                    <Button disabled={isLoading} type="submit">{mode === 'edit' ? 'Update Invoice' : 'Create Invoice'}</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function PurchaseInvoiceLineTable({ rows }: { rows: PurchaseInvoiceLine[] }) {
    return <SimpleTable columns={[['item', 'Item'], ['sourceLine', 'Source Line'], ['invoiceQuantity', 'Invoice Qty'], ['uom', 'UOM'], ['unitPrice', 'Backend Price'], ['discountAmount', 'Discount'], ['taxAmount', 'Tax'], ['lineTotal', 'Line Total']]} rows={rows} />;
}

export function PurchaseInvoiceCalculationPanel({ preview }: { preview?: PurchaseCalculationPreview }) {
    if (!preview) {
        return (
            <PreviewPanel
                rows={[{ label: 'Preview', value: 'Not requested for this invoice view' }]}
                status="Unavailable"
                subtitle="Open the invoice form to request a backend calculation preview before saving."
                title="Invoice Calculation Preview"
            />
        );
    }

    return (
        <PreviewPanel
            rows={[
                { label: 'Subtotal', value: preview.calculated.subtotal },
                { label: 'Discount', value: preview.calculated.discountTotal },
                { label: 'Tax', value: preview.calculated.taxTotal },
                { label: 'Grand total', value: preview.calculated.grandTotal },
                { label: 'UOM conversion', value: preview.calculated.uomConversion },
            ]}
            status="Backend Preview"
            subtitle="Invoice total, discount, tax, UOM, and payable values are never calculated in the frontend."
            title="Invoice Calculation Preview"
        />
    );
}

export function PurchaseInvoiceDocumentPanel({ entityId, entityType }: { entityId?: string; entityType?: 'grn_header' | 'purchase_order' | 'purchase_return' }) {
    const [message, setMessage] = useState('');

    async function generateDocument(): Promise<void> {
        if (!entityId || !entityType) {
            setMessage('Document generation requires a persisted purchase source.');
            return;
        }

        await purchaseApi.invoices.generateDocument(entityType, entityId);
        setMessage('Document generation requested from backend.');
    }

    return (
        <PreviewPanel
            rows={[
                { label: 'Document source', value: entityType && entityId ? `${entityType} ${entityId}` : 'Persisted source required' },
                { label: 'Generation', value: entityType && entityId ? 'Available' : 'Unavailable for this invoice' },
            ]}
            status="Document"
            title="Document Invoice"
        >
            <div className="flex flex-wrap items-center gap-2">
                <Button disabled={!entityId || !entityType} onClick={() => void generateDocument()} type="button" variant="secondary">Generate</Button>
                {message ? <span className="text-sm text-slate-600">{message}</span> : null}
            </div>
        </PreviewPanel>
    );
}

export function PurchasePaymentForm() {
    const navigate = useNavigate();
    const { errors, globalError, isLoading, lookupActions, lookups, preview, previewAllocation, setField, setGlobalError, submit, values } = usePaymentForm();

    async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
        event.preventDefault();
        setGlobalError('');
        const response = await submit(() => purchaseApi.payments.create(values));
        if (response) {
            navigate('/purchase/payments');
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void handleSubmit(event)}>
            <FormSection description="Payment is routed through Payment module. Backend validates payable invoices, allocations, balances, and posting." title="Supplier Payment">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={errors.party_id ?? errors.supplier_id} label="Supplier"><LookupSelect disabled={isLoading} isLoading={lookupActions.loading.suppliers} onOpen={lookupActions.loadSuppliers} onChange={(value) => setField('supplierId', value)} options={lookups.suppliers} placeholder="Select supplier" value={values.supplierId} /></Field>
                    <Field error={errors.payment_date} label="Payment date"><Input onChange={(event) => setField('paymentDate', event.target.value)} type="date" value={values.paymentDate} /></Field>
                    <Field error={errors.payment_method} label="Payment method"><Select onChange={(event) => setField('method', event.target.value)} value={values.method}><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="check">Check</option><option value="card">Card</option></Select></Field>
                    <Field error={errors.amount} label="Amount"><Input onChange={(event) => setField('amount', event.target.value)} placeholder="Input amount only" type="number" value={values.amount} /></Field>
                    <Field error={errors.reference_number} label="Reference"><Input onChange={(event) => setField('reference', event.target.value)} placeholder="Bank/check/reference number" value={values.reference ?? ''} /></Field>
                    <Field label="Source type"><Select onChange={(event) => setField('sourceType', event.target.value)} value={values.sourceType ?? ''}><option value="">Unlinked supplier payment</option><option value="purchase_order">Purchase order</option><option value="grn_header">GRN</option></Select></Field>
                    <Field error={errors.source_id} label="Source document"><LookupSelect disabled={isLoading || !values.sourceType} isLoading={values.sourceType === 'purchase_order' ? lookupActions.loading.purchaseOrders : lookupActions.loading.grns} onOpen={values.sourceType === 'purchase_order' ? lookupActions.loadPurchaseOrders : lookupActions.loadGrns} onChange={(value) => setField('sourceId', value)} options={values.sourceType === 'purchase_order' ? lookups.purchaseOrders : lookups.grns} placeholder="Select source document" value={values.sourceId ?? ''} /></Field>
                </div>
            </FormSection>
            <PurchasePaymentAllocationPanel preview={preview} />
            <div className="flex justify-end gap-3">
                {globalError ? <p className="mr-auto text-sm font-semibold text-red-600">{globalError}</p> : null}
                <Button disabled={isLoading || !values.sourceId || !values.amount} onClick={() => void previewAllocation()} type="button" variant="secondary">Preview Allocation</Button>
                <Button disabled={isLoading} type="submit">Create Payment</Button>
            </div>
        </form>
    );
}

export function PurchasePaymentAllocationPanel({ allocations = [], preview }: { allocations?: PurchasePaymentAllocation[]; preview?: Record<string, unknown> }) {
    const rows = preview
        ? [
            { label: 'Allocated amount', value: String(preview.requested_amount ?? preview.allocated_amount ?? '') },
            { label: 'Outstanding amount', value: String(preview.outstanding_amount ?? '') },
            { label: 'Balance after allocation', value: String(preview.remaining_after_allocation ?? '') },
        ]
        : [{ label: 'Preview', value: 'Select a source document and amount to request allocation preview.' }];

    return (
        <PreviewPanel
            rows={rows}
            status={preview ? 'Backend Preview' : 'Not requested'}
            title="Payment Allocation"
        >
            {allocations.length ? <SimpleTable columns={[['sourceDocument', 'Document'], ['allocatedAmount', 'Allocated'], ['documentBalanceAfter', 'Balance After'], ['status', 'Status']]} rows={allocations} /> : null}
        </PreviewPanel>
    );
}

export function PurchaseAdvancePanel({ advances }: { advances: PurchaseAdvance[] }) {
    return <SimpleTable columns={[['advanceNumber', 'Advance #'], ['supplier', 'Supplier'], ['amount', 'Amount'], ['remainingAmount', 'Backend Remaining'], ['status', 'Status']]} rows={advances} />;
}

export function PurchaseReturnForm({ initialReturn }: { initialReturn?: PurchaseReturn }) {
    const navigate = useNavigate();
    const { addLine, errors, globalError, isLoading, lookupActions, lookups, removeLine, setField, setGlobalError, setLineField, submit, values } = useReturnForm(initialReturn);

    async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
        event.preventDefault();
        setGlobalError('');
        const response = initialReturn
            ? await submit(() => purchaseApi.returns.updateWithLines(initialReturn.id, values))
            : await submit(() => purchaseApi.returns.createWithLines(values));
        if (response) {
            navigate(`/purchase/returns/${response.data.id}`);
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void handleSubmit(event)}>
            <FormSection description="Returnable quantity, stock reversal, AP adjustment, and refund eligibility are backend-owned." title="Purchase Return">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={errors.supplier_id} label="Supplier"><LookupSelect disabled={isLoading} isLoading={lookupActions.loading.suppliers} onOpen={lookupActions.loadSuppliers} onChange={(value) => setField('supplierId', value)} options={lookups.suppliers} placeholder="Select supplier" value={values.supplierId} /></Field>
                    <Field label="Source type"><Select onChange={(event) => setField('sourceType', event.target.value)} value={values.sourceType ?? ''}><option value="">Direct return</option><option value="purchase_order">Purchase order</option><option value="grn_header">GRN</option></Select></Field>
                    <Field label="Source document"><LookupSelect disabled={isLoading || !values.sourceType} onChange={(value) => setField('sourceId', value)} options={values.sourceType === 'purchase_order' ? lookups.purchaseOrders : lookups.grns} placeholder="Select source document" value={values.sourceId ?? ''} /></Field>
                    <Field error={errors.return_number} label="Return number"><Input onChange={(event) => setField('returnNumber', event.target.value)} value={values.returnNumber} /></Field>
                    <Field error={errors.return_date} label="Return date"><Input onChange={(event) => setField('returnDate', event.target.value)} type="date" value={values.returnDate} /></Field>
                    <Field error={errors.return_reason} label="Reason"><Input onChange={(event) => setField('returnReason', event.target.value)} placeholder="Damage, over supply, wrong item..." value={values.returnReason ?? ''} /></Field>
                    <Field error={errors.notes} label="Notes"><Textarea onChange={(event) => setField('notes', event.target.value)} placeholder="Return notes" value={values.notes ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Backend validates returnable quantities and previews inventory/AP effects." title="Return Lines">
                <div className="space-y-4">
                    {values.lines.map((line, index) => (
                        <PurchaseLineEditor errors={errors} key={line.clientKey} line={line} lineIndex={index} lookupActions={lookupActions} lookups={lookups} onLineChange={setLineField} onRemove={values.lines.length > 1 ? () => removeLine(index) : undefined} quantityLabel="Return qty" />
                    ))}
                </div>
                <div className="mt-4 flex justify-end gap-3">{globalError ? <p className="mr-auto text-sm font-semibold text-red-600">{globalError}</p> : null}<Button disabled={isLoading} onClick={addLine} type="button" variant="secondary">Add Line</Button><Button disabled={isLoading} type="submit">{initialReturn ? 'Update Return' : 'Create Return'}</Button></div>
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
    const submitState = useSubmitState();
    const [message, setMessage] = useState('');
    const [values, setValues] = useState<PurchaseSettingsFormInput>({
        allow_direct_grn: settings.allowGrnWithoutPo,
        allow_direct_purchase_document: settings.allowDirectInvoice,
        allow_over_receipt: settings.allowOverReceipt,
        require_grn_before_invoice: !settings.allowInvoiceWithoutGrn,
    });

    async function initialize(): Promise<void> {
        await purchaseApi.settings.initialize();
        setMessage('Purchase settings initialized by backend.');
    }

    async function save(event: FormEvent<HTMLFormElement>): Promise<void> {
        event.preventDefault();
        const response = await submitState.submit(() => purchaseApi.settings.update(values));
        if (response) {
            setMessage('Purchase settings saved.');
        }
    }

    function setBoolean(field: keyof PurchaseSettingsFormInput, value: string): void {
        setValues((current) => ({ ...current, [field]: value === 'true' }));
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void save(event)}>
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
                    <Field error={submitState.errors.allow_direct_purchase_document} label="Allow direct invoice"><Select onChange={(event) => setBoolean('allow_direct_purchase_document', event.target.value)} value={String(values.allow_direct_purchase_document)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                    <Field error={submitState.errors.allow_direct_grn} label="Allow GRN without PO"><Select onChange={(event) => setBoolean('allow_direct_grn', event.target.value)} value={String(values.allow_direct_grn)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                    <Field error={submitState.errors.require_grn_before_invoice} label="Require GRN before invoice"><Select onChange={(event) => setBoolean('require_grn_before_invoice', event.target.value)} value={String(values.require_grn_before_invoice)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                    <Field error={submitState.errors.allow_over_receipt} label="Allow over receipt"><Select onChange={(event) => setBoolean('allow_over_receipt', event.target.value)} value={String(values.allow_over_receipt)}><option value="true">Yes</option><option value="false">No</option></Select></Field>
                </div>
                <div className="mt-4 flex justify-end gap-3">
                    {submitState.globalError || message ? <span className="mr-auto text-sm text-slate-600">{submitState.globalError || message}</span> : null}
                    <Button disabled={submitState.isSubmitting} onClick={() => void initialize()} type="button" variant="secondary">Initialize Defaults</Button>
                    <Button disabled={submitState.isSubmitting} type="submit" variant="blue">Save Settings</Button>
                </div>
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
                { header: 'Actions', key: 'actions', render: (row) => <RowActions editPath={`/purchase/returns/${row.id}/edit`} viewPath={`/purchase/returns/${row.id}`} /> },
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

function usePurchaseLookups() {
    const mountedRef = useRef(true);
    const loadedLookupRef = useRef(new Set<string>());
    const loadingLookupRef = useRef(new Set<string>());
    const loadedItemUomsRef = useRef(new Set<string>());
    const loadingItemUomsRef = useRef(new Set<string>());
    const [lookups, setLookups] = useState({
        grns: [] as PurchaseLookupOption[],
        items: [] as PurchaseLookupOption[],
        purchaseOrders: [] as PurchaseLookupOption[],
        suppliers: [] as PurchaseLookupOption[],
        uomsByItem: {} as Record<string, PurchaseLookupOption[]>,
        warehouses: [] as PurchaseLookupOption[],
    });
    const [loading, setLoading] = useState({
        grns: false,
        items: false,
        purchaseOrders: false,
        suppliers: false,
        warehouses: false,
    });

    useEffect(() => {
        return () => {
            mountedRef.current = false;
        };
    }, []);

    const loadLookup = useCallback(async (
        key: keyof Omit<typeof lookups, 'uomsByItem'>,
        loader: () => Promise<{ data: PurchaseLookupOption[] }>,
    ): Promise<void> => {
        if (loadedLookupRef.current.has(key) || loadingLookupRef.current.has(key)) {
            return;
        }

        loadingLookupRef.current.add(key);
        setLoading((current) => ({ ...current, [key]: true }));

        try {
            const response = await loader();
            loadedLookupRef.current.add(key);

            if (mountedRef.current) {
                setLookups((current) => ({ ...current, [key]: response.data }));
            }
        } catch {
            // Keep the form usable; field/backend validation still protects invalid selections.
        } finally {
            loadingLookupRef.current.delete(key);
            if (mountedRef.current) {
                setLoading((current) => ({ ...current, [key]: false }));
            }
        }
    }, []);

    const loadSuppliers = useCallback(() => loadLookup('suppliers', purchaseApi.lookups.suppliers), [loadLookup]);
    const loadItems = useCallback(() => loadLookup('items', purchaseApi.lookups.items), [loadLookup]);
    const loadWarehouses = useCallback(() => loadLookup('warehouses', purchaseApi.lookups.warehouses), [loadLookup]);
    const loadPurchaseOrders = useCallback(() => loadLookup('purchaseOrders', purchaseApi.lookups.purchaseOrders), [loadLookup]);
    const loadGrns = useCallback(() => loadLookup('grns', purchaseApi.lookups.grns), [loadLookup]);

    const loadItemUoms = useCallback(async (itemId: string): Promise<void> => {
        if (!itemId || loadedItemUomsRef.current.has(itemId) || loadingItemUomsRef.current.has(itemId)) {
            return;
        }

        loadingItemUomsRef.current.add(itemId);

        try {
            const response = await purchaseApi.lookups.itemUoms(itemId);
            loadedItemUomsRef.current.add(itemId);

            if (!mountedRef.current) {
                return;
            }

            setLookups((current) => current.uomsByItem[itemId]
                ? current
                : {
                    ...current,
                    uomsByItem: { ...current.uomsByItem, [itemId]: response.data },
                });
        } catch {
            // Keep line editing responsive; backend validation reports missing UOMs on submit.
        } finally {
            loadingItemUomsRef.current.delete(itemId);
        }
    }, []);

    return {
        isLoading: false,
        loadGrns,
        loadItemUoms,
        loadItems,
        loadPurchaseOrders,
        loadSuppliers,
        loadWarehouses,
        loading,
        lookups,
    };
}

let purchaseLineKeySequence = 0;

function nextPurchaseLineClientKey(): string {
    purchaseLineKeySequence += 1;
    return `purchase-line-${purchaseLineKeySequence}`;
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

function defaultLine(line?: Partial<PurchaseLineFormInput>): PurchaseLineFormInput {
    return {
        clientKey: line?.clientKey ?? nextPurchaseLineClientKey(),
        discountType: line?.discountType ?? '',
        discountValue: line?.discountValue ?? '',
        itemId: line?.itemId ?? '',
        quantity: line?.quantity ?? '1',
        unitPrice: line?.unitPrice ?? '0',
        uomId: line?.uomId ?? '',
    };
}

function parseApiErrors(error: unknown): { fields: Record<string, string>; message: string } {
    if (error instanceof ApiError) {
        return {
            fields: Object.fromEntries(Object.entries(error.errors).map(([key, value]) => [key, value.join(' ')])),
            message: error.message,
        };
    }

    return { fields: {}, message: error instanceof Error ? error.message : 'Request failed.' };
}

function useSubmitState() {
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [globalError, setGlobalError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function submit<T>(callback: () => Promise<T>): Promise<T | null> {
        setIsSubmitting(true);
        setErrors({});
        setGlobalError('');
        try {
            return await callback();
        } catch (error) {
            const parsed = parseApiErrors(error);
            setErrors(parsed.fields);
            setGlobalError(parsed.message);
            return null;
        } finally {
            setIsSubmitting(false);
        }
    }

    return { errors, globalError, isSubmitting, setGlobalError, submit };
}

function usePurchaseOrderForm(initial?: PurchaseOrder) {
    const lookupActions = usePurchaseLookups();
    const { isLoading, loadItemUoms, lookups } = lookupActions;
    const submitState = useSubmitState();
    const [values, setValues] = useState<PurchaseOrderFormInput>({
        expectedDate: initial?.expectedDate || '',
        lines: [defaultLine(initial?.lines[0] ? { itemId: initial.lines[0].itemId, quantity: initial.lines[0].orderedQuantity, unitPrice: initial.lines[0].unitPrice, uomId: initial.lines[0].uomId } : undefined)],
        notes: '',
        orderDate: initial?.orderDate || today(),
        poNumber: initial?.poNumber || '',
        status: initial?.status || 'draft',
        supplierId: initial?.supplierId || '',
        warehouseId: initial?.warehouseId || '',
    });

    return formState(values, setValues, lookups, loadItemUoms, lookupActions, isLoading || submitState.isSubmitting, submitState);
}

function useGrnForm(initial?: GoodsReceivedNote) {
    const lookupActions = usePurchaseLookups();
    const { isLoading, loadItemUoms, lookups } = lookupActions;
    const submitState = useSubmitState();
    const [values, setValues] = useState<GrnFormInput>({
        grnDate: initial?.grnDate || today(),
        grnNumber: initial?.grnNumber || '',
        lines: [defaultLine(initial?.lines[0] ? { itemId: initial.lines[0].itemId, quantity: initial.lines[0].acceptedQuantity, unitPrice: '0', uomId: initial.lines[0].uomId } : undefined)],
        notes: '',
        purchaseOrderId: initial?.sourcePo && /^\d+$/.test(initial.sourcePo) ? initial.sourcePo : '',
        status: initial?.status || 'draft',
        supplierId: initial?.supplierId || '',
        warehouseId: initial?.warehouseId || '',
    });

    return formState(values, setValues, lookups, loadItemUoms, lookupActions, isLoading || submitState.isSubmitting, submitState);
}

function useReturnForm(initial?: PurchaseReturn) {
    const lookupActions = usePurchaseLookups();
    const { isLoading, loadItemUoms, lookups } = lookupActions;
    const submitState = useSubmitState();
    const [values, setValues] = useState<PurchaseReturnFormInput>({
        lines: [defaultLine(initial?.lines[0] ? { itemId: initial.lines[0].itemId, quantity: initial.lines[0].returnQuantity, unitPrice: '0', uomId: initial.lines[0].uomId } : undefined)],
        notes: '',
        returnDate: today(),
        returnNumber: initial?.returnNumber || '',
        returnReason: '',
        sourceId: '',
        sourceType: '',
        status: initial?.status || 'draft',
        supplierId: initial?.supplierId || '',
    });

    return formState(values, setValues, lookups, loadItemUoms, lookupActions, isLoading || submitState.isSubmitting, submitState);
}

function useInvoiceForm() {
    const lookupActions = usePurchaseLookups();
    const { isLoading, loadItemUoms, lookups } = lookupActions;
    const submitState = useSubmitState();
    const [preview, setPreview] = useState<PurchaseCalculationPreview>();
    const [values, setValues] = useState<PurchaseInvoiceFormInput>({
        dueDate: '',
        invoiceDate: today(),
        lines: [defaultLine()],
        sourceId: '',
        sourceType: 'purchase_order',
        supplierInvoiceNumber: '',
    });

    async function previewInvoice(): Promise<void> {
        const result = await submitState.submit(() => purchaseApi.invoices.preview({
            lines: values.lines.map((line) => ({
                discount_type: line.discountType || null,
                discount_value: Number(line.discountValue || 0),
                quantity: Number(line.quantity),
                unit_price: Number(line.unitPrice),
            })),
        }));
        if (result) {
            setPreview({
                breakdown: result.breakdown as never,
                calculated: result.calculated,
                errors: result.errors,
                input: result.input as Record<string, unknown>,
                warnings: result.warnings,
            });
        }
    }

    return { ...formState(values, setValues, lookups, loadItemUoms, lookupActions, isLoading || submitState.isSubmitting, submitState), preview, previewInvoice };
}

function usePaymentForm() {
    const lookupActions = usePurchaseLookups();
    const { isLoading, lookups } = lookupActions;
    const submitState = useSubmitState();
    const [preview, setPreview] = useState<Record<string, unknown>>();
    const [values, setValues] = useState<PurchasePaymentFormInput>({
        amount: '',
        method: 'bank_transfer',
        paymentDate: today(),
        reference: '',
        sourceId: '',
        sourceType: '',
        supplierId: '',
    });

    function setField<K extends keyof PurchasePaymentFormInput>(field: K, value: PurchasePaymentFormInput[K]): void {
        setValues((current) => ({ ...current, [field]: value }));
    }

    async function previewAllocation(): Promise<void> {
        const result = await submitState.submit(() => purchaseApi.payments.previewAllocation({
            allocated_amount: Number(values.amount),
            source_id: Number(values.sourceId),
            source_type: values.sourceType,
        }));
        if (result) {
            setPreview(result.calculated);
        }
    }

    return { ...submitState, errors: submitState.errors, globalError: submitState.globalError, isLoading: isLoading || submitState.isSubmitting, lookupActions, lookups, preview, previewAllocation, setField, values };
}

function formState<T extends { lines: PurchaseLineFormInput[] }>(
    values: T,
    setValues: Dispatch<SetStateAction<T>>,
    lookups: ReturnType<typeof usePurchaseLookups>['lookups'],
    loadItemUoms: (itemId: string) => Promise<void>,
    lookupActions: ReturnType<typeof usePurchaseLookups>,
    isLoading: boolean,
    submitState: ReturnType<typeof useSubmitState>,
) {
    function setField<K extends keyof T>(field: K, value: T[K]): void {
        setValues((current) => ({ ...current, [field]: value }));
    }

    function setLineField(index: number, field: keyof PurchaseLineFormInput, value: string): void {
        setValues((current) => {
            const lines = [...current.lines];
            const next = { ...lines[index], [field]: value };
            if (field === 'itemId') {
                next.uomId = '';
                void loadItemUoms(value);
            }
            lines[index] = next;
            return { ...current, lines };
        });
    }

    function addLine(): void {
        setValues((current) => ({ ...current, lines: [...current.lines, defaultLine({ quantity: '1', unitPrice: '0' })] }));
    }

    function removeLine(index: number): void {
        setValues((current) => {
            if (current.lines.length <= 1) {
                return current;
            }

            return { ...current, lines: current.lines.filter((_, lineIndex) => lineIndex !== index) };
        });
    }

    useEffect(() => {
        values.lines.forEach((line) => {
            if (line.itemId) {
                void loadItemUoms(line.itemId);
            }
        });
    }, [loadItemUoms, values.lines]);

    return { ...submitState, addLine, errors: submitState.errors, globalError: submitState.globalError, isLoading, lookupActions, lookups, removeLine, setField, setLineField, values };
}

function LookupSelect({
    disabled,
    isLoading = false,
    onChange,
    onOpen,
    options,
    placeholder,
    value,
}: {
    disabled?: boolean;
    isLoading?: boolean;
    onChange: (value: string) => void;
    onOpen?: () => Promise<void>;
    options: PurchaseLookupOption[];
    placeholder: string;
    value: string;
}) {
    function loadOnDemand(): void {
        if (!disabled) {
            void onOpen?.();
        }
    }

    return (
        <Select disabled={disabled} onChange={(event) => onChange(event.target.value)} onFocus={loadOnDemand} onMouseDown={loadOnDemand} value={value}>
            <option value="">{isLoading ? 'Loading options...' : placeholder}</option>
            {options.map((option) => <option key={`${option.id}:${option.label}`} value={option.id}>{option.label}</option>)}
        </Select>
    );
}

function PurchaseLineEditor({
    errors,
    line,
    lineIndex,
    lookupActions,
    lookups,
    onLineChange,
    onRemove,
    quantityLabel,
}: {
    errors: Record<string, string>;
    line: PurchaseLineFormInput;
    lineIndex: number;
    lookupActions: ReturnType<typeof usePurchaseLookups>;
    lookups: ReturnType<typeof usePurchaseLookups>['lookups'];
    onLineChange: (index: number, field: keyof PurchaseLineFormInput, value: string) => void;
    onRemove?: () => void;
    quantityLabel: string;
}) {
    const itemUoms = line.itemId ? lookups.uomsByItem[line.itemId] ?? [] : [];

    return (
        <div className="grid gap-4 rounded-lg border border-slate-200 p-4 md:grid-cols-6">
            <Field error={errors[`lines.${lineIndex}.item_id`] ?? errors.item_id} label="Item">
                <LookupSelect isLoading={lookupActions.loading.items} onOpen={lookupActions.loadItems} onChange={(value) => onLineChange(lineIndex, 'itemId', value)} options={lookups.items} placeholder="Select item first" value={line.itemId} />
            </Field>
            <Field error={errors[`lines.${lineIndex}.uom_id`] ?? errors.uom_id} label="UOM">
                <LookupSelect disabled={!line.itemId} onOpen={() => lookupActions.loadItemUoms(line.itemId)} onChange={(value) => onLineChange(lineIndex, 'uomId', value)} options={itemUoms} placeholder={line.itemId ? 'Select item UOM' : 'Select item first'} value={line.uomId} />
                {line.itemId && itemUoms.length === 0 ? <span className="text-xs text-amber-600">No UOM configured for this item.</span> : null}
            </Field>
            <Field error={errors[`lines.${lineIndex}.quantity`] ?? errors[`lines.${lineIndex}.ordered_qty`] ?? errors[`lines.${lineIndex}.received_qty`] ?? errors[`lines.${lineIndex}.return_qty`]} label={quantityLabel}>
                <Input min="0" onChange={(event) => onLineChange(lineIndex, 'quantity', event.target.value)} step="0.0001" type="number" value={line.quantity} />
            </Field>
            <Field error={errors[`lines.${lineIndex}.unit_price`] ?? errors.unit_price} label="Unit price">
                <Input min="0" onChange={(event) => onLineChange(lineIndex, 'unitPrice', event.target.value)} step="0.0001" type="number" value={line.unitPrice} />
            </Field>
            <Field label="Discount type">
                <Select onChange={(event) => onLineChange(lineIndex, 'discountType', event.target.value)} value={line.discountType ?? ''}><option value="">None</option><option value="fixed">Fixed</option><option value="percentage">Percentage</option></Select>
            </Field>
            <Field error={errors[`lines.${lineIndex}.discount_value`]} label="Discount value">
                <Input min="0" onChange={(event) => onLineChange(lineIndex, 'discountValue', event.target.value)} step="0.0001" type="number" value={line.discountValue ?? ''} />
            </Field>
            {onRemove ? <div className="flex items-end"><Button onClick={onRemove} type="button" variant="ghost">Remove</Button></div> : null}
        </div>
    );
}

function Field({ children, error, label }: { children: ReactNode; error?: string; label: string }) {
    return (
        <label className="space-y-2 text-sm">
            <span className="font-semibold text-slate-700">{label}</span>
            {children}
            {error ? <span className="block text-xs font-semibold text-red-600">{error}</span> : null}
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
