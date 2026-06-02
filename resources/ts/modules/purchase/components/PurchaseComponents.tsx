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
    SupplierRefund,
} from '../types/purchase.types';
import { purchaseApi } from '../services/purchaseApi';

type PurchaseLookupName = 'currencies' | 'items' | 'suppliers' | 'warehouses';

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
    const { addLine, errors, globalError, isLoading, loadItemUoms, loadLookup, lookups, removeLine, setField, setGlobalError, setLineField, submit, values } = usePurchaseOrderForm(initialOrder);

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
                    <Field error={errors.supplier_id} label="Supplier"><LookupSelect disabled={isLoading} onChange={(value) => setField('supplierId', value)} onOpen={() => void loadLookup('suppliers')} options={lookups.suppliers} placeholder="Select supplier" value={values.supplierId} /></Field>
                    <Field error={errors.po_number} label="PO number"><Input onChange={(event) => setField('poNumber', event.target.value)} value={values.poNumber} /></Field>
                    <Field error={errors.order_date} label="Order date"><Input onChange={(event) => setField('orderDate', event.target.value)} type="date" value={values.orderDate} /></Field>
                    <Field error={errors.expected_date} label="Expected date"><Input onChange={(event) => setField('expectedDate', event.target.value)} type="date" value={values.expectedDate ?? ''} /></Field>
                    <Field error={errors.warehouse_id} label="Warehouse"><LookupSelect disabled={isLoading} onChange={(value) => setField('warehouseId', value)} onOpen={() => void loadLookup('warehouses')} options={lookups.warehouses} placeholder="Select warehouse" value={values.warehouseId} /></Field>
                    <Field label="Workflow"><Select onChange={(event) => setField('status', event.target.value)} value={values.status ?? 'draft'}><option value="draft">Save as draft</option><option value="submitted">Submit for approval</option></Select></Field>
                    <Field error={errors.notes} label="Notes"><Textarea onChange={(event) => setField('notes', event.target.value)} placeholder="Supplier instructions, delivery notes, internal remarks" value={values.notes ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Frontend collects item, UOM, quantity, price inputs. Backend resolves UOM conversion, discounts, tax, and totals." title="Order Lines">
                <PurchaseLinesEditor addLine={addLine} errors={errors} lines={values.lines} loadItemUoms={loadItemUoms} loadLookup={loadLookup} lookups={lookups} onLineChange={setLineField} quantityLabel="Ordered qty" removeLine={removeLine} />
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    {globalError ? <p className="mr-auto text-sm font-semibold text-red-600">{globalError}</p> : null}
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
            } else if (entityType === 'purchase_invoice') {
                if (action === 'post') {
                    await purchaseApi.invoices.post(entityId, sourceType, sourceId);
                } else if (action === 'cancel') {
                    await purchaseApi.invoices.cancel(entityId, sourceType, sourceId);
                } else {
                    await purchaseApi.invoices.reverse(entityId, sourceType, sourceId);
                }
            } else {
                setMessage('This entity type does not expose a Purchase workflow transition endpoint.');
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
                { label: 'Allowed actions', value: entityType === 'purchase_invoice' ? 'Post, cancel, and reverse use invoice/document backend endpoints.' : 'Validated by backend on submit.' },
            ]}
            status="Workflow"
            title="Workflow Actions"
        >
            <div className="flex flex-wrap items-center gap-2">
                {(entityType === 'purchase_invoice' ? ['post', 'cancel', 'reverse'] : ['submit', 'approve', 'cancel', 'reverse']).map((action) => (
                    <Button disabled={isSubmitting} key={action} onClick={() => void transition(action)} type="button" variant={action === 'submit' || action === 'post' ? 'blue' : 'secondary'}>
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
    const { addLine, errors, globalError, isLoading, loadItemUoms, loadLookup, lookups, removeLine, setField, setGlobalError, setLineField, submit, values } = useGrnForm(initialGrn);

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
                    <Field error={errors.supplier_id} label="Supplier"><LookupSelect disabled={isLoading} onChange={(value) => setField('supplierId', value)} onOpen={() => void loadLookup('suppliers')} options={lookups.suppliers} placeholder="Select supplier" value={values.supplierId} /></Field>
                    <Field error={errors.grn_number} label="GRN number"><Input onChange={(event) => setField('grnNumber', event.target.value)} value={values.grnNumber} /></Field>
                    <Field label="Source PO optional"><Input onChange={(event) => setField('purchaseOrderId', event.target.value)} placeholder="Purchase order id, if linked" value={values.purchaseOrderId ?? ''} /></Field>
                    <Field error={errors.received_date} label="GRN date"><Input onChange={(event) => setField('grnDate', event.target.value)} type="date" value={values.grnDate} /></Field>
                    <Field error={errors.warehouse_id} label="Warehouse"><LookupSelect disabled={isLoading} onChange={(value) => setField('warehouseId', value)} onOpen={() => void loadLookup('warehouses')} options={lookups.warehouses} placeholder="Select warehouse" value={values.warehouseId} /></Field>
                    <Field label="Status"><Select onChange={(event) => setField('status', event.target.value)} value={values.status ?? 'draft'}><option value="draft">Draft</option><option value="submitted">Submitted</option><option value="confirmed">Confirmed</option></Select></Field>
                    <Field error={errors.notes} label="Notes"><Textarea onChange={(event) => setField('notes', event.target.value)} placeholder="Receiving notes" value={values.notes ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Accepted/rejected quantities are submitted as inputs. Backend returns authoritative stock movement effect." title="Received Lines">
                <PurchaseLinesEditor addLine={addLine} errors={errors} lines={values.lines} loadItemUoms={loadItemUoms} loadLookup={loadLookup} lookups={lookups} onLineChange={setLineField} quantityLabel="Received qty" removeLine={removeLine} />
                <div className="mt-4 flex justify-end gap-3">{globalError ? <p className="mr-auto text-sm font-semibold text-red-600">{globalError}</p> : null}<Button disabled={isLoading} type="submit" variant="blue">{mode === 'edit' ? 'Update GRN' : 'Create GRN'}</Button></div>
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
    const { addLine, errors, globalError, isLoading, loadItemUoms, loadLookup, lookups, preview, previewInvoice, removeLine, setField, setGlobalError, setLineField, submit, values } = useInvoiceForm();

    async function handleSubmit(event: FormEvent<HTMLFormElement>): Promise<void> {
        event.preventDefault();
        setGlobalError('');
        const response = await submit(() => values.sourceType === 'direct'
            ? purchaseApi.invoices.createDirect(values)
            : values.sourceType === 'grn_header'
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
                    <Field label="Invoice source"><Select onChange={(event) => setField('sourceType', event.target.value as PurchaseInvoiceFormInput['sourceType'])} value={values.sourceType}><option value="direct">Direct invoice</option><option value="purchase_order">From PO</option><option value="grn_header">From GRN</option></Select></Field>
                    <Field error={errors.supplier_id ?? errors.party_id} label="Supplier"><LookupSelect disabled={isLoading} onChange={(value) => setField('supplierId', value)} onOpen={() => void loadLookup('suppliers')} options={lookups.suppliers} placeholder="Select supplier" value={values.supplierId} /></Field>
                    <Field error={errors.currency_id} label="Currency"><LookupSelect disabled={isLoading} onChange={(value) => setField('currencyId', value)} onOpen={() => void loadLookup('currencies')} options={lookups.currencies} placeholder="Select currency" value={values.currencyId} /></Field>
                    {values.sourceType !== 'direct' ? <Field error={errors.source_id} label="Source id"><Input onChange={(event) => setField('sourceId', event.target.value)} placeholder="Backend PO/GRN id" value={values.sourceId} /></Field> : null}
                    <Field error={errors.invoice_date} label="Invoice date"><Input onChange={(event) => setField('invoiceDate', event.target.value)} type="date" value={values.invoiceDate} /></Field>
                    <Field error={errors.due_date} label="Due date"><Input onChange={(event) => setField('dueDate', event.target.value)} type="date" value={values.dueDate ?? ''} /></Field>
                    <Field label="Supplier invoice no"><Input onChange={(event) => setField('supplierInvoiceNumber', event.target.value)} placeholder="Supplier reference" value={values.supplierInvoiceNumber ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Line amount, discounts, taxes, UOM conversion, payable amount, and balances are previewed by backend only." title="Invoice Lines">
                <PurchaseLinesEditor addLine={addLine} errors={errors} lines={values.lines} loadItemUoms={loadItemUoms} loadLookup={loadLookup} lookups={lookups} onLineChange={setLineField} quantityLabel="Invoice qty" removeLine={removeLine} />
                {preview ? <PurchaseInvoiceCalculationPanel preview={preview} /> : null}
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    {globalError ? <p className="mr-auto text-sm font-semibold text-red-600">{globalError}</p> : null}
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
    return (
        <PreviewPanel
            rows={[
                { label: 'Subtotal', value: preview?.calculated.subtotal ?? 'Run backend preview' },
                { label: 'Discount', value: preview?.calculated.discountTotal ?? 'Run backend preview' },
                { label: 'Tax', value: preview?.calculated.taxTotal ?? 'Run backend preview' },
                { label: 'Grand total', value: preview?.calculated.grandTotal ?? 'Run backend preview' },
                { label: 'UOM conversion', value: preview?.calculated.uomConversion ?? 'Validated by backend preview' },
            ]}
            status="Backend Preview"
            subtitle="Invoice total, discount, tax, UOM, and payable values are never calculated in the frontend."
            title="Invoice Calculation Preview"
        />
    );
}

export function PurchaseInvoiceDocumentPanel({ entityId, entityType, invoice }: { entityId?: string; entityType?: 'grn_header' | 'purchase_order' | 'purchase_return'; invoice?: PurchaseInvoice }) {
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
                { label: 'Document source', value: invoice?.sourceType && invoice.sourceId ? `${invoice.sourceType} ${invoice.sourceId}` : entityType && entityId ? `${entityType} ${entityId}` : 'Direct purchase invoice document' },
                { label: 'Document number', value: invoice?.invoiceNumber ?? 'Generated by Sequence/Document backend' },
                { label: 'Document status', value: invoice?.documentStatus ?? 'Document module backend' },
            ]}
            status="Document"
            title="Document Invoice"
        >
            <div className="flex flex-wrap items-center gap-2">
                <Button disabled={!entityId || !entityType} onClick={() => void generateDocument()} title={entityId && entityType ? 'Generate a backend document for the source record.' : 'Direct invoices are already Document module records.'} type="button" variant="secondary">Generate</Button>
                {message ? <span className="text-sm text-slate-600">{message}</span> : null}
            </div>
        </PreviewPanel>
    );
}

export function PurchasePaymentForm() {
    const navigate = useNavigate();
    const { errors, globalError, isLoading, loadLookup, lookups, preview, previewAllocation, setField, setGlobalError, submit, values } = usePaymentForm();

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
                    <Field error={errors.party_id ?? errors.supplier_id} label="Supplier"><LookupSelect disabled={isLoading} onChange={(value) => setField('supplierId', value)} onOpen={() => void loadLookup('suppliers')} options={lookups.suppliers} placeholder="Select supplier" value={values.supplierId} /></Field>
                    <Field error={errors.payment_date} label="Payment date"><Input onChange={(event) => setField('paymentDate', event.target.value)} type="date" value={values.paymentDate} /></Field>
                    <Field error={errors.payment_method} label="Payment method"><Select onChange={(event) => setField('method', event.target.value)} value={values.method}><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="check">Check</option><option value="card">Card</option></Select></Field>
                    <Field error={errors.amount} label="Amount"><Input onChange={(event) => setField('amount', event.target.value)} placeholder="Input amount only" type="number" value={values.amount} /></Field>
                    <Field error={errors.reference_number} label="Reference"><Input onChange={(event) => setField('reference', event.target.value)} placeholder="Bank/check/reference number" value={values.reference ?? ''} /></Field>
                    <Field label="Source type"><Select onChange={(event) => setField('sourceType', event.target.value)} value={values.sourceType ?? ''}><option value="">Unlinked supplier payment</option><option value="purchase_order">Purchase order</option><option value="grn_header">GRN</option></Select></Field>
                    <Field error={errors.source_id} label="Source id"><Input onChange={(event) => setField('sourceId', event.target.value)} placeholder="Persisted source id" value={values.sourceId ?? ''} /></Field>
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
    return (
        <PreviewPanel
            rows={[
                { label: 'Allocated amount', value: preview ? String(preview.requested_amount ?? preview.allocated_amount ?? '') : 'Run backend preview' },
                { label: 'Outstanding amount', value: preview ? String(preview.outstanding_amount ?? '') : 'Run backend preview' },
                { label: 'Balance after allocation', value: preview ? String(preview.remaining_after_allocation ?? '') : 'Run backend preview' },
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

export function PurchaseReturnForm({ initialReturn }: { initialReturn?: PurchaseReturn }) {
    const navigate = useNavigate();
    const { addLine, errors, globalError, isLoading, loadItemUoms, loadLookup, lookups, removeLine, setField, setGlobalError, setLineField, submit, values } = useReturnForm(initialReturn);

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
                    <Field error={errors.supplier_id} label="Supplier"><LookupSelect disabled={isLoading} onChange={(value) => setField('supplierId', value)} onOpen={() => void loadLookup('suppliers')} options={lookups.suppliers} placeholder="Select supplier" value={values.supplierId} /></Field>
                    <Field label="Source type"><Select onChange={(event) => setField('sourceType', event.target.value)} value={values.sourceType ?? ''}><option value="">Direct return</option><option value="purchase_order">Purchase order</option><option value="grn_header">GRN</option><option value="document">Supplier invoice document</option></Select></Field>
                    <Field label="Source id"><Input onChange={(event) => setField('sourceId', event.target.value)} placeholder="Persisted source id" value={values.sourceId ?? ''} /></Field>
                    <Field error={errors.return_number} label="Return number"><Input onChange={(event) => setField('returnNumber', event.target.value)} value={values.returnNumber} /></Field>
                    <Field error={errors.return_date} label="Return date"><Input onChange={(event) => setField('returnDate', event.target.value)} type="date" value={values.returnDate} /></Field>
                    <Field error={errors.return_reason} label="Reason"><Input onChange={(event) => setField('returnReason', event.target.value)} placeholder="Damage, over supply, wrong item..." value={values.returnReason ?? ''} /></Field>
                    <Field error={errors.notes} label="Notes"><Textarea onChange={(event) => setField('notes', event.target.value)} placeholder="Return notes" value={values.notes ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Backend validates returnable quantities and previews inventory/AP effects." title="Return Lines">
                <PurchaseLinesEditor addLine={addLine} errors={errors} lines={values.lines} loadItemUoms={loadItemUoms} loadLookup={loadLookup} lookups={lookups} onLineChange={setLineField} quantityLabel="Return qty" removeLine={removeLine} />
                <div className="mt-4 flex justify-end gap-3">{globalError ? <p className="mr-auto text-sm font-semibold text-red-600">{globalError}</p> : null}<Button disabled={isLoading} type="submit">{initialReturn ? 'Update Return' : 'Create Return'}</Button></div>
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
    const [message, setMessage] = useState('');

    async function initialize(): Promise<void> {
        await purchaseApi.settings.initialize();
        setMessage('Purchase settings initialized by backend.');
    }

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
                <div className="mt-4 flex justify-end gap-3">
                    {message ? <span className="mr-auto text-sm text-slate-600">{message}</span> : null}
                    <Button onClick={() => void initialize()} type="button" variant="secondary">Initialize Defaults</Button>
                    <Button disabled title="Editing purchase settings requires exposing writable config fields from the backend settings resource." type="button" variant="blue">Save Settings unavailable</Button>
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
                { header: 'Actions', key: 'actions', render: (row) => <RowActions disabledEditReason="Supplier invoice editing is source-scoped in the backend. Use source matching or create a correction/return from detail." editPath={`/purchase/invoices/${row.id}/edit`} viewPath={`/purchase/invoices/${row.id}`} /> },
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

function RowActions({ disabledEditReason, editPath, viewPath }: { disabledEditReason?: string; editPath: string; viewPath: string }) {
    return (
        <div className="flex gap-2">
            <Link to={viewPath}><Button variant="secondary">View</Button></Link>
            {disabledEditReason ? <Button disabled title={disabledEditReason} variant="ghost">Edit</Button> : <Link to={editPath}><Button variant="ghost">Edit</Button></Link>}
        </div>
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

function usePurchaseLookups() {
    const [lookups, setLookups] = useState({
        currencies: [] as PurchaseLookupOption[],
        items: [] as PurchaseLookupOption[],
        suppliers: [] as PurchaseLookupOption[],
        uomsByItem: {} as Record<string, PurchaseLookupOption[]>,
        warehouses: [] as PurchaseLookupOption[],
    });
    const [isLoading, setIsLoading] = useState(false);
    const mountedRef = useRef(true);
    const loadingRef = useRef(new Set<string>());

    useEffect(() => () => {
        mountedRef.current = false;
    }, []);

    const loadLookup = useCallback(async (name: PurchaseLookupName): Promise<void> => {
        if (loadingRef.current.has(name)) {
            return;
        }
        let shouldLoad = false;
        setLookups((current) => {
            shouldLoad = current[name].length === 0;
            return current;
        });
        if (!shouldLoad) {
            return;
        }
        loadingRef.current.add(name);
        setIsLoading(true);
        try {
            const response = await purchaseApi.lookups[name]();
            if (!mountedRef.current) return;
            setLookups((current) => current[name].length > 0 ? current : { ...current, [name]: response.data });
        } finally {
            loadingRef.current.delete(name);
            if (mountedRef.current) {
                setIsLoading(loadingRef.current.size > 0);
            }
        }
    }, []);

    const loadItemUoms = useCallback(async (itemId: string): Promise<void> => {
        if (!itemId || loadingRef.current.has(`uom:${itemId}`)) {
            return;
        }
        let shouldLoad = false;
        setLookups((current) => {
            shouldLoad = !current.uomsByItem[itemId] || current.uomsByItem[itemId].length === 0;
            return current;
        });
        if (!shouldLoad) {
            return;
        }
        loadingRef.current.add(`uom:${itemId}`);
        try {
            const response = await purchaseApi.lookups.itemUoms(itemId);
            if (!mountedRef.current) return;
            setLookups((current) => current.uomsByItem[itemId]
                ? current
                : { ...current, uomsByItem: { ...current.uomsByItem, [itemId]: response.data } });
        } catch {
            if (mountedRef.current) {
                setLookups((current) => current.uomsByItem[itemId]
                    ? current
                    : { ...current, uomsByItem: { ...current.uomsByItem, [itemId]: [] } });
            }
        } finally {
            loadingRef.current.delete(`uom:${itemId}`);
        }
    }, []);

    return { isLoading, loadItemUoms, loadLookup, lookups };
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

function lineClientKey(): string {
    return `purchase-line-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

function defaultLine(line?: Partial<PurchaseLineFormInput>): PurchaseLineFormInput {
    return {
        clientKey: line?.clientKey ?? lineClientKey(),
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
    const { isLoading, loadItemUoms, loadLookup, lookups } = usePurchaseLookups();
    const submitState = useSubmitState();
    const [values, setValues] = useState<PurchaseOrderFormInput>({
        expectedDate: initial?.expectedDate || '',
        lines: initial?.lines.length ? initial.lines.map((line) => defaultLine({ itemId: line.itemId, quantity: line.orderedQuantity, unitPrice: line.unitPrice, uomId: line.uomId })) : [defaultLine()],
        notes: '',
        orderDate: initial?.orderDate || today(),
        poNumber: initial?.poNumber || `PO-${Date.now()}`,
        status: initial?.status || 'draft',
        supplierId: initial?.supplierId || '',
        warehouseId: initial?.warehouseId || '',
    });

    return formState(values, setValues, lookups, loadItemUoms, loadLookup, isLoading || submitState.isSubmitting, submitState);
}

function useGrnForm(initial?: GoodsReceivedNote) {
    const { isLoading, loadItemUoms, loadLookup, lookups } = usePurchaseLookups();
    const submitState = useSubmitState();
    const [values, setValues] = useState<GrnFormInput>({
        grnDate: initial?.grnDate || today(),
        grnNumber: initial?.grnNumber || `GRN-${Date.now()}`,
        lines: initial?.lines.length ? initial.lines.map((line) => defaultLine({ itemId: line.itemId, quantity: line.acceptedQuantity, unitPrice: '0', uomId: line.uomId })) : [defaultLine()],
        notes: '',
        purchaseOrderId: initial?.sourcePo && /^\d+$/.test(initial.sourcePo) ? initial.sourcePo : '',
        status: initial?.status || 'draft',
        supplierId: initial?.supplierId || '',
        warehouseId: initial?.warehouseId || '',
    });

    return formState(values, setValues, lookups, loadItemUoms, loadLookup, isLoading || submitState.isSubmitting, submitState);
}

function useReturnForm(initial?: PurchaseReturn) {
    const { isLoading, loadItemUoms, loadLookup, lookups } = usePurchaseLookups();
    const submitState = useSubmitState();
    const [values, setValues] = useState<PurchaseReturnFormInput>({
        lines: initial?.lines.length ? initial.lines.map((line) => defaultLine({ itemId: line.itemId, quantity: line.returnQuantity, unitPrice: '0', uomId: line.uomId })) : [defaultLine()],
        notes: '',
        returnDate: today(),
        returnNumber: initial?.returnNumber || `PRET-${Date.now()}`,
        returnReason: '',
        sourceId: '',
        sourceType: '',
        status: initial?.status || 'draft',
        supplierId: initial?.supplierId || '',
    });

    return formState(values, setValues, lookups, loadItemUoms, loadLookup, isLoading || submitState.isSubmitting, submitState);
}

function useInvoiceForm() {
    const { isLoading, loadItemUoms, loadLookup, lookups } = usePurchaseLookups();
    const submitState = useSubmitState();
    const [preview, setPreview] = useState<PurchaseCalculationPreview>();
    const [values, setValues] = useState<PurchaseInvoiceFormInput>({
        currencyId: '',
        dueDate: '',
        invoiceDate: today(),
        lines: [defaultLine()],
        sourceId: '',
        sourceType: 'direct',
        supplierId: '',
        supplierInvoiceNumber: '',
    });

    async function previewInvoice(): Promise<void> {
        const result = await submitState.submit(() => purchaseApi.invoices.preview({
            lines: values.lines.map((line) => ({
                discount_type: line.discountType || null,
                discount_value: Number(line.discountValue || 0),
                item_id: Number(line.itemId) || null,
                quantity: Number(line.quantity),
                unit_price: Number(line.unitPrice),
                uom_id: Number(line.uomId) || null,
            })),
        }));
        if (result) {
            setPreview({
                breakdown: result.breakdown,
                calculated: result.calculated,
                errors: result.errors,
                input: result.input as Record<string, unknown>,
                warnings: result.warnings,
            });
        }
    }

    return { ...formState(values, setValues, lookups, loadItemUoms, loadLookup, isLoading || submitState.isSubmitting, submitState), preview, previewInvoice };
}

function usePaymentForm() {
    const { isLoading, loadLookup, lookups } = usePurchaseLookups();
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

    return { ...submitState, errors: submitState.errors, globalError: submitState.globalError, isLoading: isLoading || submitState.isSubmitting, loadLookup, lookups, preview, previewAllocation, setField, values };
}

function formState<T extends { lines: PurchaseLineFormInput[] }>(
    values: T,
    setValues: Dispatch<SetStateAction<T>>,
    lookups: ReturnType<typeof usePurchaseLookups>['lookups'],
    loadItemUoms: (itemId: string) => Promise<void>,
    loadLookup: (name: PurchaseLookupName) => Promise<void>,
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
        setValues((current) => ({ ...current, lines: [...current.lines, defaultLine()] }));
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

    useEffect(() => {
        setValues((current) => {
            let changed = false;
            const lines = current.lines.map((line) => {
                if (!line.itemId || line.uomId) {
                    return line;
                }

                const defaultUom = lookups.uomsByItem[line.itemId]?.[0];
                if (!defaultUom?.id) {
                    return line;
                }

                changed = true;
                return { ...line, uomId: defaultUom.id };
            });

            return changed ? { ...current, lines } : current;
        });
    }, [lookups.uomsByItem, setValues]);

    return { ...submitState, addLine, errors: submitState.errors, globalError: submitState.globalError, isLoading, loadItemUoms, loadLookup, lookups, removeLine, setField, setLineField, values };
}

function LookupSelect({ disabled, onChange, onOpen, options, placeholder, value }: { disabled?: boolean; onChange: (value: string) => void; onOpen?: () => void; options: PurchaseLookupOption[]; placeholder: string; value: string }) {
    return (
        <Select disabled={disabled} onChange={(event) => onChange(event.target.value)} onFocus={onOpen} onMouseDown={onOpen} value={value}>
            <option value="">{options.length === 0 ? `${placeholder} (open to load)` : placeholder}</option>
            {options.map((option) => <option key={`purchase-option-${option.id}`} value={option.id}>{option.label}</option>)}
        </Select>
    );
}

function PurchaseLinesEditor({
    addLine,
    errors,
    lines,
    loadItemUoms,
    loadLookup,
    lookups,
    onLineChange,
    quantityLabel,
    removeLine,
}: {
    addLine: () => void;
    errors: Record<string, string>;
    lines: PurchaseLineFormInput[];
    loadItemUoms: (itemId: string) => Promise<void>;
    loadLookup: (name: PurchaseLookupName) => Promise<void>;
    lookups: ReturnType<typeof usePurchaseLookups>['lookups'];
    onLineChange: (index: number, field: keyof PurchaseLineFormInput, value: string) => void;
    quantityLabel: string;
    removeLine: (index: number) => void;
}) {
    return (
        <div className="space-y-4">
            {lines.map((line, index) => (
                <div className="rounded-lg border border-slate-200 bg-white p-4" key={line.clientKey}>
                    <div className="mb-3 flex items-center justify-between gap-3">
                        <p className="text-sm font-bold text-slate-800">Line {index + 1}</p>
                        <Button disabled={lines.length === 1} onClick={() => removeLine(index)} title={lines.length === 1 ? 'At least one line is required.' : 'Remove this line'} type="button" variant="ghost">Remove</Button>
                    </div>
                    <PurchaseLineEditor errors={errors} line={line} lineIndex={index} loadItemUoms={loadItemUoms} loadLookup={loadLookup} lookups={lookups} onLineChange={onLineChange} quantityLabel={quantityLabel} />
                </div>
            ))}
            <div className="flex justify-end">
                <Button onClick={addLine} type="button" variant="secondary">Add line</Button>
            </div>
        </div>
    );
}

function PurchaseLineEditor({
    errors,
    line,
    lineIndex,
    loadItemUoms,
    loadLookup,
    lookups,
    onLineChange,
    quantityLabel,
}: {
    errors: Record<string, string>;
    line: PurchaseLineFormInput;
    lineIndex: number;
    loadItemUoms: (itemId: string) => Promise<void>;
    loadLookup: (name: PurchaseLookupName) => Promise<void>;
    lookups: ReturnType<typeof usePurchaseLookups>['lookups'];
    onLineChange: (index: number, field: keyof PurchaseLineFormInput, value: string) => void;
    quantityLabel: string;
}) {
    const itemUoms = line.itemId ? lookups.uomsByItem[line.itemId] ?? [] : [];
    const uomsLoaded = !line.itemId || Object.prototype.hasOwnProperty.call(lookups.uomsByItem, line.itemId);

    return (
        <div className="grid gap-4 md:grid-cols-6">
            <Field error={errors[`lines.${lineIndex}.item_id`] ?? errors.item_id} label="Item">
                <LookupSelect onChange={(value) => onLineChange(lineIndex, 'itemId', value)} onOpen={() => void loadLookup('items')} options={lookups.items} placeholder="Select item" value={line.itemId} />
            </Field>
            <Field error={errors[`lines.${lineIndex}.uom_id`] ?? errors.uom_id} label="UOM">
                <LookupSelect disabled={!line.itemId || !uomsLoaded || itemUoms.length === 0} onChange={(value) => onLineChange(lineIndex, 'uomId', value)} onOpen={() => line.itemId ? void loadItemUoms(line.itemId) : undefined} options={itemUoms} placeholder={line.itemId ? 'Select item UOM' : 'Select item first'} value={line.uomId} />
                {line.itemId && !uomsLoaded ? <span className="text-xs text-slate-500">Loading UOMs for selected item...</span> : null}
                {line.itemId && uomsLoaded && itemUoms.length === 0 ? <span className="text-xs text-amber-600">No purchasable UOM was returned. Re-select the item or refresh seeded Item/UOM data.</span> : null}
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
