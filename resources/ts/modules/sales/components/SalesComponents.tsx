import { useCallback, useEffect, useMemo, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable, type DataTableColumn } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { ApiError } from '../../../services/api/apiErrors';
import { salesApi } from '../services/salesApi';
import type {
    CustomerAdvance,
    CustomerRefund,
    GoodsDeliveryNote,
    GoodsDeliveryNoteLine,
    SalesAuditEntry,
    SalesCalculationPreview,
    SalesCreditCheckResult,
    SalesDashboardMetric,
    SalesFinancePostingPreview,
    SalesInventoryEffect,
    SalesInvoice,
    SalesInvoiceLine,
    SalesOrder,
    SalesOrderFormInput,
    SalesOrderLine,
    SalesPayment,
    SalesPaymentAllocation,
    SalesPaymentFormInput,
    SalesQuotation,
    SalesQuotationLine,
    SalesReturn,
    SalesReturnFormInput,
    SalesReturnLine,
    SalesSettings,
    SalesStockAvailabilityPreview,
    GdnFormInput,
    SalesInvoiceFormInput,
    SalesLineFormInput,
    SalesLookupOption,
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
        <EmptyState
            description="This repository does not contain a Sales quotation migration, service, controller, or API route. Sales order, delivery, invoice, payment, and return flows are connected to real backend APIs."
            title="Quotation backend unavailable"
        />
    );
}

export function SalesQuotationLineTable({ rows }: { rows: SalesQuotationLine[] }) {
    return <SimpleTable columns={[['item', 'Item'], ['quantity', 'Qty'], ['uom', 'UOM'], ['unitPrice', 'Backend Price'], ['discountAmount', 'Discount'], ['taxAmount', 'Tax'], ['lineTotal', 'Line Total']]} rows={rows} />;
}

export function SalesOrderForm({ mode = 'create' }: { mode?: 'create' | 'edit' }) {
    const { id } = useParams();
    const navigate = useNavigate();
    const { errors, globalError, isLoading, loadCustomers, loadItemUoms, loadItems, loadWarehouses, loading, lookups } = useSalesLookups();
    const [isSaving, setIsSaving] = useState(false);
    const [values, setValues] = useState<SalesOrderFormInput>(() => ({
        customerId: '',
        expectedDate: '',
        lines: [emptyLine()],
        notes: '',
        orderDate: today(),
        soNumber: `SO-${Date.now()}`,
        status: 'draft',
        warehouseId: '',
    }));
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [submitError, setSubmitError] = useState('');

    useEffect(() => {
        if (mode !== 'edit' || !id) return;
        salesApi.orders.get(id).then((response) => {
            const order = response.data;
            setValues({
                customerId: order.customerId ?? '',
                expectedDate: order.expectedDate,
                lines: order.lines.length ? order.lines.map((line) => emptyLine({ itemId: line.itemId ?? '', quantity: line.orderedQuantity, unitPrice: line.unitPrice, uomId: line.uomId ?? '' })) : [emptyLine()],
                notes: '',
                orderDate: order.orderDate,
                soNumber: order.soNumber,
                status: order.status,
                warehouseId: order.warehouseId ?? '',
            });
        }).catch((caught: unknown) => setSubmitError(errorMessage(caught, 'Unable to load sales order.')));
    }, [id, mode]);

    function setField<K extends keyof SalesOrderFormInput>(field: K, value: SalesOrderFormInput[K]): void {
        setValues((current) => ({ ...current, [field]: value }));
    }

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setIsSaving(true);
        setFieldErrors({});
        setSubmitError('');
        try {
            const response = mode === 'edit' && id
                ? await salesApi.orders.updateWithLines(id, values)
                : await salesApi.orders.createWithLines(values);
            navigate(`/sales/orders/${response.data.id}`);
        } catch (caught) {
            setFieldErrors(extractFieldErrors(caught));
            setSubmitError(errorMessage(caught, 'Unable to save sales order.'));
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void submit(event)}>
            {globalError || submitError ? <FormError message={submitError || globalError} /> : null}
            <FormSection description="Customer, warehouse, pricing, UOM, stock, tax, totals, and workflow are backend validated." title="Sales Order Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={fieldErrors.customer_id} label="Customer"><LookupSelect disabled={isLoading} isLoading={loading.customers} onOpen={loadCustomers} onChange={(value) => setField('customerId', value)} options={lookups.customers} placeholder="Select customer" value={values.customerId} /></Field>
                    <Field error={fieldErrors.so_number} label="SO number"><Input onChange={(event) => setField('soNumber', event.target.value)} value={values.soNumber} /></Field>
                    <Field error={fieldErrors.order_date} label="Order date"><Input onChange={(event) => setField('orderDate', event.target.value)} type="date" value={values.orderDate} /></Field>
                    <Field error={fieldErrors.requested_delivery_date} label="Expected delivery"><Input onChange={(event) => setField('expectedDate', event.target.value)} type="date" value={values.expectedDate ?? ''} /></Field>
                    <Field error={fieldErrors.warehouse_id} label="Warehouse"><LookupSelect disabled={isLoading} isLoading={loading.warehouses} onOpen={loadWarehouses} onChange={(value) => setField('warehouseId', value)} options={lookups.warehouses} placeholder="Select warehouse" value={values.warehouseId} /></Field>
                    <Field error={fieldErrors.status} label="Workflow"><Select onChange={(event) => setField('status', event.target.value)} value={values.status}><option value="draft">Draft</option><option value="submitted">Submit</option></Select></Field>
                    <Field error={fieldErrors.notes} label="Notes"><Textarea onChange={(event) => setField('notes', event.target.value)} placeholder="Delivery notes, customer PO, internal remarks" value={values.notes ?? ''} /></Field>
                </div>
            </FormSection>
            <SalesCreditCheckPanel />
            <FormSection description="Frontend collects item, UOM, quantity, and price inputs. Backend resolves stock, discounts, tax, and totals." title="Order Lines">
                <SalesLineEditor errors={fieldErrors} lines={values.lines} loadItemUoms={loadItemUoms} loadItems={loadItems} loadingItems={loading.items} lookups={lookups} onChange={(lines) => setField('lines', lines)} quantityLabel="Ordered quantity" />
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    <Button disabled={isSaving} type="submit">{mode === 'edit' ? 'Update With Lines' : 'Create With Lines'}</Button>
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
    const [message, setMessage] = useState('');
    const [isBusy, setIsBusy] = useState(false);
    const supported = entityType === 'sales_order' || entityType === 'gdn_header' || entityType === 'sales_return';

    async function transition(action: string): Promise<void> {
        if (!supported) return;
        setIsBusy(true);
        setMessage('');
        try {
            if (entityType === 'sales_order') await salesApi.orders.transition(entityId, action);
            if (entityType === 'gdn_header') await salesApi.deliveries.transition(entityId, action);
            if (entityType === 'sales_return') await salesApi.returns.transition(entityId, action);
            setMessage(`${action} requested successfully.`);
        } catch (caught) {
            setMessage(errorMessage(caught, `Unable to ${action}.`));
        } finally {
            setIsBusy(false);
        }
    }

    return (
        <PreviewPanel
            rows={[
                { label: 'Entity', value: `${entityType} / ${entityId}` },
                { label: 'Current status', value: status },
                { label: 'Allowed actions', value: supported ? 'Backend validates transition permissions and status.' : 'Use the source document workflow for invoice status changes.' },
            ]}
            status="Workflow"
            title="Workflow Actions"
        >
            {message ? <p className="mb-3 text-sm font-semibold text-slate-600">{message}</p> : null}
            <div className="flex flex-wrap gap-2">
                <Button disabled={!supported || isBusy} onClick={() => void transition('submit')} type="button" variant="blue">Submit</Button>
                <Button disabled={!supported || isBusy} onClick={() => void transition('approve')} type="button" variant="secondary">Approve</Button>
                <Button disabled={!supported || isBusy} onClick={() => void transition('cancel')} type="button" variant="ghost">Cancel</Button>
                <Button disabled={!supported || isBusy} onClick={() => void transition('reverse')} type="button" variant="ghost">Reverse</Button>
            </div>
        </PreviewPanel>
    );
}

export function GdnForm({ mode = 'create' }: { mode?: 'create' | 'edit' }) {
    const { id } = useParams();
    const navigate = useNavigate();
    const { errors: lookupError, globalError, isLoading, loadCustomers, loadItemUoms, loadItems, loadWarehouses, loading, lookups } = useSalesLookups();
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [isSaving, setIsSaving] = useState(false);
    const [submitError, setSubmitError] = useState('');
    const [values, setValues] = useState<GdnFormInput>(() => ({
        customerId: '',
        deliveryDate: today(),
        gdnNumber: `GDN-${Date.now()}`,
        lines: [emptyLine()],
        notes: '',
        salesOrderId: '',
        status: 'draft',
        warehouseId: '',
    }));

    useEffect(() => {
        if (mode !== 'edit' || !id) return;
        salesApi.deliveries.get(id).then((response) => {
            const delivery = response.data;
            setValues({
                customerId: delivery.customerId ?? '',
                deliveryDate: delivery.deliveryDate,
                gdnNumber: delivery.gdnNumber,
                lines: delivery.lines.length ? delivery.lines.map((line) => emptyLine({ itemId: line.itemId ?? '', quantity: line.deliveredQuantity, unitPrice: '0', uomId: line.uomId ?? '' })) : [emptyLine()],
                notes: '',
                salesOrderId: delivery.sourceOrder && /^\d+$/.test(delivery.sourceOrder) ? delivery.sourceOrder : '',
                status: delivery.status,
                warehouseId: delivery.warehouseId ?? '',
            });
        }).catch((caught: unknown) => setSubmitError(errorMessage(caught, 'Unable to load delivery.')));
    }, [id, mode]);

    function setField<K extends keyof GdnFormInput>(field: K, value: GdnFormInput[K]): void {
        setValues((current) => ({ ...current, [field]: value }));
    }

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setIsSaving(true);
        setFieldErrors({});
        setSubmitError('');
        try {
            const response = mode === 'edit' && id
                ? await salesApi.deliveries.updateWithLines(id, values)
                : await salesApi.deliveries.createDirect(values);
            navigate(`/sales/deliveries/${response.data.id}`);
        } catch (caught) {
            setFieldErrors(extractFieldErrors(caught));
            setSubmitError(errorMessage(caught, 'Unable to save delivery.'));
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void submit(event)}>
            {globalError || lookupError || submitError ? <FormError message={submitError || globalError || lookupError} /> : null}
            <FormSection description="GDN records delivered quantity. Backend validates order lines, stock, UOM, warehouse, and stock issue." title="Delivery / GDN Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={fieldErrors.customer_id} label="Customer"><LookupSelect disabled={isLoading} isLoading={loading.customers} onOpen={loadCustomers} onChange={(value) => setField('customerId', value)} options={lookups.customers} placeholder="Select customer" value={values.customerId} /></Field>
                    <Field label="Source order optional"><Input onChange={(event) => setField('salesOrderId', event.target.value)} placeholder="Persisted sales order id, if linked" value={values.salesOrderId ?? ''} /></Field>
                    <Field error={fieldErrors.gdn_number} label="GDN number"><Input onChange={(event) => setField('gdnNumber', event.target.value)} value={values.gdnNumber} /></Field>
                    <Field error={fieldErrors.delivery_date} label="Delivery date"><Input onChange={(event) => setField('deliveryDate', event.target.value)} type="date" value={values.deliveryDate} /></Field>
                    <Field error={fieldErrors.warehouse_id} label="Warehouse"><LookupSelect disabled={isLoading} isLoading={loading.warehouses} onOpen={loadWarehouses} onChange={(value) => setField('warehouseId', value)} options={lookups.warehouses} placeholder="Issue warehouse" value={values.warehouseId} /></Field>
                    <Field error={fieldErrors.status} label="Picking status"><Select onChange={(event) => setField('status', event.target.value)} value={values.status}><option value="draft">Draft</option><option value="picked">Picked</option></Select></Field>
                    <Field error={fieldErrors.notes} label="Notes"><Textarea onChange={(event) => setField('notes', event.target.value)} placeholder="Dispatch notes" value={values.notes ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Delivered quantities are submitted as inputs. Backend owns stock movement effects." title="Delivered Lines">
                <SalesLineEditor errors={fieldErrors} lines={values.lines} loadItemUoms={loadItemUoms} loadItems={loadItems} loadingItems={loading.items} lookups={lookups} onChange={(lines) => setField('lines', lines)} quantityLabel="Delivered quantity" />
                <div className="mt-4 flex justify-end gap-3"><Button disabled={isSaving} type="submit" variant="blue">{mode === 'edit' ? 'Update GDN' : 'Create GDN'}</Button></div>
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
    const navigate = useNavigate();
    const { errors: lookupError, globalError, isLoading, loadItemUoms, loadItems, loading, lookups } = useSalesLookups();
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [isSaving, setIsSaving] = useState(false);
    const [preview, setPreview] = useState<SalesCalculationPreview['calculated']>();
    const [submitError, setSubmitError] = useState('');
    const [values, setValues] = useState<SalesInvoiceFormInput>(() => ({
        dueDate: '',
        invoiceDate: today(),
        lines: [emptyLine()],
        sourceId: '',
        sourceType: 'sales_order',
    }));

    function setField<K extends keyof SalesInvoiceFormInput>(field: K, value: SalesInvoiceFormInput[K]): void {
        setValues((current) => ({ ...current, [field]: value }));
    }

    async function previewInvoice(): Promise<void> {
        setSubmitError('');
        try {
            const response = await salesApi.invoices.preview({ lines: values.lines.map((line) => ({ quantity: Number(line.quantity), unit_price: Number(line.unitPrice) })) });
            setPreview(response.calculated);
        } catch (caught) {
            setFieldErrors(extractFieldErrors(caught));
            setSubmitError(errorMessage(caught, 'Unable to preview invoice.'));
        }
    }

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setIsSaving(true);
        setFieldErrors({});
        setSubmitError('');
        try {
            const response = values.sourceType === 'gdn_header'
                ? await salesApi.invoices.createFromDelivery(values.sourceId, values)
                : await salesApi.invoices.createFromOrder(values.sourceId, values);
            const created = asComponentRecord(response.data);
            navigate(`/sales/invoices/${String(created.id ?? created.document_id ?? values.sourceId)}`);
        } catch (caught) {
            setFieldErrors(extractFieldErrors(caught));
            setSubmitError(errorMessage(caught, 'Unable to save invoice.'));
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void submit(event)}>
            {globalError || lookupError || submitError ? <FormError message={submitError || globalError || lookupError} /> : null}
            <FormSection description="Supports invoice from sales order or GDN according to backend settings." title="Customer Invoice Header">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={fieldErrors.source_type} label="Invoice source"><Select onChange={(event) => setField('sourceType', event.target.value as SalesInvoiceFormInput['sourceType'])} value={values.sourceType}><option value="sales_order">From sales order</option><option value="gdn_header">From GDN</option></Select></Field>
                    <Field error={fieldErrors.source_id} label="Source id"><Input onChange={(event) => setField('sourceId', event.target.value)} placeholder="Persisted source id" value={values.sourceId} /></Field>
                    <Field error={fieldErrors.invoice_date} label="Invoice date"><Input onChange={(event) => setField('invoiceDate', event.target.value)} type="date" value={values.invoiceDate} /></Field>
                    <Field error={fieldErrors.due_date} label="Due date"><Input onChange={(event) => setField('dueDate', event.target.value)} type="date" value={values.dueDate ?? ''} /></Field>
                    <Field label="Customer PO/ref"><Input onChange={(event) => setField('customerReference', event.target.value)} value={values.customerReference ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Pricing, discounts, taxes, UOM conversion, receivable amount, and balances are previewed by backend only." title="Invoice Lines">
                <SalesLineEditor errors={fieldErrors} lines={values.lines} loadItemUoms={loadItemUoms} loadItems={loadItems} loadingItems={loading.items} lookups={lookups} onChange={(lines) => setField('lines', lines)} quantityLabel="Invoice quantity" />
                {preview ? <PreviewPanel rows={[{ label: 'Subtotal', value: preview.subtotal }, { label: 'Discount', value: preview.discountTotal }, { label: 'Tax', value: preview.taxTotal }, { label: 'Grand total', value: preview.grandTotal }, { label: 'UOM', value: preview.uomConversion }]} status="Backend Preview" title="Invoice Calculation Preview" /> : null}
                <div className="mt-4 flex flex-wrap justify-end gap-3">
                    <Button disabled={isSaving} onClick={() => void previewInvoice()} type="button" variant="blue">Preview Calculation</Button>
                    <Button disabled={isSaving || isLoading} type="submit">{mode === 'edit' ? 'Update Invoice' : 'Create Invoice'}</Button>
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
                { label: 'Source', value: 'Use the create/edit invoice form to request /api/sales/calculate-invoice with current line input.' },
            ]}
            status="Backend endpoint"
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
    const navigate = useNavigate();
    const { errors: lookupError, globalError, isLoading, loadCustomers, loading, lookups } = useSalesLookups();
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [isSaving, setIsSaving] = useState(false);
    const [preview, setPreview] = useState<Record<string, unknown>>();
    const [submitError, setSubmitError] = useState('');
    const [values, setValues] = useState<SalesPaymentFormInput>(() => ({ amount: '', customerId: '', method: 'bank_transfer', paymentDate: today(), reference: '', sourceId: '', sourceType: 'sales_invoice' }));

    function setField<K extends keyof SalesPaymentFormInput>(field: K, value: SalesPaymentFormInput[K]): void {
        setValues((current) => ({ ...current, [field]: value }));
    }

    async function previewAllocation(): Promise<void> {
        setSubmitError('');
        try {
            const response = await salesApi.payments.previewAllocation({ amount: Number(values.amount), customer_id: Number(values.customerId), source_id: values.sourceId ? Number(values.sourceId) : undefined, source_type: values.sourceType });
            setPreview(response.calculated);
        } catch (caught) {
            setFieldErrors(extractFieldErrors(caught));
            setSubmitError(errorMessage(caught, 'Unable to preview allocation.'));
        }
    }

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setIsSaving(true);
        setFieldErrors({});
        setSubmitError('');
        try {
            const response = await salesApi.payments.create(values);
            const created = asComponentRecord(response.data);
            navigate(`/sales/payments/${String(created.id ?? values.sourceId)}`);
        } catch (caught) {
            setFieldErrors(extractFieldErrors(caught));
            setSubmitError(errorMessage(caught, 'Unable to save payment.'));
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void submit(event)}>
            {globalError || lookupError || submitError ? <FormError message={submitError || globalError || lookupError} /> : null}
            <FormSection description="Payment is routed through Payment module. Backend validates receivable invoices, allocations, balances, and posting." title="Customer Payment">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={fieldErrors.party_id ?? fieldErrors.customer_id} label="Customer"><LookupSelect disabled={isLoading} isLoading={loading.customers} onOpen={loadCustomers} onChange={(value) => setField('customerId', value)} options={lookups.customers} placeholder="Select customer" value={values.customerId} /></Field>
                    <Field error={fieldErrors.payment_date} label="Payment date"><Input onChange={(event) => setField('paymentDate', event.target.value)} type="date" value={values.paymentDate} /></Field>
                    <Field error={fieldErrors.payment_method} label="Payment method"><Select onChange={(event) => setField('method', event.target.value)} value={values.method}><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="check">Check</option><option value="card">Card</option></Select></Field>
                    <Field error={fieldErrors.amount} label="Amount"><Input onChange={(event) => setField('amount', event.target.value)} placeholder="Input amount only" type="number" value={values.amount} /></Field>
                    <Field error={fieldErrors.reference_number} label="Reference"><Input onChange={(event) => setField('reference', event.target.value)} placeholder="Bank/check/reference number" value={values.reference ?? ''} /></Field>
                    <Field error={fieldErrors.source_id} label="Source invoice optional"><Input onChange={(event) => setField('sourceId', event.target.value)} placeholder="Persisted invoice id" value={values.sourceId ?? ''} /></Field>
                </div>
            </FormSection>
            <SalesPaymentAllocationPanel preview={preview} />
            <div className="flex justify-end gap-3"><Button disabled={isSaving} onClick={() => void previewAllocation()} type="button" variant="blue">Preview Allocation</Button><Button disabled={isSaving || isLoading} type="submit">Create Payment</Button></div>
        </form>
    );
}

export function SalesPaymentAllocationPanel({ allocations = [], preview }: { allocations?: SalesPaymentAllocation[]; preview?: Record<string, unknown> }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Allocated amount', value: displayPreviewValue(preview, ['allocated_amount', 'allocatedAmount'], 'Run backend preview') },
                { label: 'Unallocated amount', value: displayPreviewValue(preview, ['unallocated_amount', 'unallocatedAmount'], 'Run backend preview') },
                { label: 'Invoice balance after allocation', value: displayPreviewValue(preview, ['document_balance_after', 'documentBalanceAfter'], 'Run backend preview') },
            ]}
            status="Backend endpoint"
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
    const navigate = useNavigate();
    const { errors: lookupError, globalError, isLoading, loadCustomers, loadItemUoms, loadItems, loading, lookups } = useSalesLookups();
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [isSaving, setIsSaving] = useState(false);
    const [preview, setPreview] = useState<Record<string, unknown>>();
    const [submitError, setSubmitError] = useState('');
    const [values, setValues] = useState<SalesReturnFormInput>(() => ({ customerId: '', lines: [emptyLine()], notes: '', returnDate: today(), returnNumber: `SRET-${Date.now()}`, returnReason: '', sourceId: '', sourceType: 'gdn_header', status: 'draft' }));

    function setField<K extends keyof SalesReturnFormInput>(field: K, value: SalesReturnFormInput[K]): void {
        setValues((current) => ({ ...current, [field]: value }));
    }

    async function previewReturn(): Promise<void> {
        setSubmitError('');
        try {
            const response = await salesApi.returns.previewEffect({ source_id: values.sourceId ? Number(values.sourceId) : undefined, source_type: values.sourceType });
            setPreview(response.calculated);
        } catch (caught) {
            setFieldErrors(extractFieldErrors(caught));
            setSubmitError(errorMessage(caught, 'Unable to preview return effect.'));
        }
    }

    async function submit(event: FormEvent): Promise<void> {
        event.preventDefault();
        setIsSaving(true);
        setFieldErrors({});
        setSubmitError('');
        try {
            const response = await salesApi.returns.createWithLines(values);
            navigate(`/sales/returns/${response.data.id}`);
        } catch (caught) {
            setFieldErrors(extractFieldErrors(caught));
            setSubmitError(errorMessage(caught, 'Unable to save return.'));
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={(event) => void submit(event)}>
            {globalError || lookupError || submitError ? <FormError message={submitError || globalError || lookupError} /> : null}
            <FormSection description="Returnable quantity, stock reversal, AR adjustment, and refund eligibility are backend-owned." title="Sales Return">
                <div className="grid gap-4 md:grid-cols-3">
                    <Field error={fieldErrors.customer_id} label="Customer"><LookupSelect disabled={isLoading} isLoading={loading.customers} onOpen={loadCustomers} onChange={(value) => setField('customerId', value)} options={lookups.customers} placeholder="Select customer" value={values.customerId} /></Field>
                    <Field label="Source type"><Select onChange={(event) => setField('sourceType', event.target.value)} value={values.sourceType}><option value="gdn_header">GDN</option><option value="sales_order">Sales Order</option><option value="document">Invoice Document</option></Select></Field>
                    <Field label="Source id"><Input onChange={(event) => setField('sourceId', event.target.value)} placeholder="Persisted source id" value={values.sourceId ?? ''} /></Field>
                    <Field error={fieldErrors.return_number} label="Return number"><Input onChange={(event) => setField('returnNumber', event.target.value)} value={values.returnNumber} /></Field>
                    <Field error={fieldErrors.return_date} label="Return date"><Input onChange={(event) => setField('returnDate', event.target.value)} type="date" value={values.returnDate} /></Field>
                    <Field error={fieldErrors.return_reason} label="Reason"><Input onChange={(event) => setField('returnReason', event.target.value)} placeholder="Damage, wrong item, customer return..." value={values.returnReason ?? ''} /></Field>
                    <Field error={fieldErrors.notes} label="Notes"><Textarea onChange={(event) => setField('notes', event.target.value)} placeholder="Return notes" value={values.notes ?? ''} /></Field>
                </div>
            </FormSection>
            <FormSection description="Backend validates returnable quantities and previews inventory/AR effects." title="Return Lines">
                <SalesLineEditor errors={fieldErrors} lines={values.lines} loadItemUoms={loadItemUoms} loadItems={loadItems} loadingItems={loading.items} lookups={lookups} onChange={(lines) => setField('lines', lines)} quantityLabel="Return quantity" />
                {preview ? <PreviewPanel rows={[{ label: 'Returnable lines', value: displayPreviewValue(preview, ['line_count'], 'Backend returned') }]} status="Backend Preview" title="Return Effect Preview" /> : null}
                <div className="mt-4 flex justify-end gap-3"><Button disabled={isSaving} onClick={() => void previewReturn()} type="button" variant="blue">Preview Return Effect</Button><Button disabled={isSaving || isLoading} type="submit">Create Return</Button></div>
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
                { label: 'Available quantity', value: preview?.calculated.availableQuantity ?? 'Run stock availability endpoint' },
                { label: 'Reserved quantity', value: preview?.calculated.reservedQuantity ?? 'Run stock availability endpoint' },
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
                { label: 'Current exposure', value: result?.calculated.currentExposure ?? 'Requires customer credit endpoint' },
                { label: 'Projected exposure', value: result?.calculated.projectedExposure ?? 'Requires customer credit endpoint' },
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
    const [message, setMessage] = useState('');
    const [isSaving, setIsSaving] = useState(false);

    async function initialize(): Promise<void> {
        setIsSaving(true);
        setMessage('');
        try {
            await salesApi.settings.initialize();
            setMessage('Sales defaults initialized by backend.');
        } catch (caught) {
            setMessage(errorMessage(caught, 'Unable to initialize Sales settings.'));
        } finally {
            setIsSaving(false);
        }
    }

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
                {message ? <p className="mt-4 text-sm font-semibold text-slate-600">{message}</p> : null}
                <div className="mt-4 flex justify-end gap-3">
                    <Button disabled={isSaving} onClick={() => void initialize()} type="button" variant="secondary">Initialize Defaults</Button>
                    <Button disabled title="Editable settings require a dedicated Sales settings editor contract; current screen displays persisted backend defaults." type="button" variant="blue">Save Settings unavailable</Button>
                </div>
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

function Field({ children, error, label }: { children: ReactNode; error?: string; label: string }) {
    return (
        <label className="space-y-2 text-sm">
            <span className="font-semibold text-slate-700">{label}</span>
            {children}
            {error ? <span className="block text-xs font-semibold text-red-600">{error}</span> : null}
        </label>
    );
}

function FormError({ message }: { message: string }) {
    return <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{message}</div>;
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
    options: SalesLookupOption[];
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

function SalesLineEditor({
    errors,
    lines,
    loadItemUoms,
    loadItems,
    loadingItems,
    lookups,
    onChange,
    quantityLabel,
}: {
    errors: Record<string, string>;
    lines: SalesLineFormInput[];
    loadItemUoms: (itemId: string) => Promise<void>;
    loadItems: () => Promise<void>;
    loadingItems: boolean;
    lookups: SalesLookupState;
    onChange: (lines: SalesLineFormInput[]) => void;
    quantityLabel: string;
}) {
    function updateLine(index: number, field: keyof SalesLineFormInput, value: string): void {
        onChange(lines.map((line, lineIndex) => {
            if (lineIndex !== index) return line;
            if (field === 'itemId') {
                void loadItemUoms(value);
                return { ...line, itemId: value, uomId: '' };
            }
            return { ...line, [field]: value };
        }));
    }

    useEffect(() => {
        lines.forEach((line) => {
            if (line.itemId) {
                void loadItemUoms(line.itemId);
            }
        });
    }, [lines, loadItemUoms]);

    return (
        <div className="space-y-4">
            {lines.map((line, index) => {
                const itemUoms = lookups.itemUoms[line.itemId] ?? [];
                return (
                    <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-3 md:grid-cols-5" key={line.clientKey}>
                        <Field error={errors[`lines.${index}.item_id`] ?? errors.item_id} label="Item">
                            <LookupSelect isLoading={loadingItems} onOpen={loadItems} onChange={(value) => updateLine(index, 'itemId', value)} options={lookups.items} placeholder="Select item" value={line.itemId} />
                        </Field>
                        <Field error={errors[`lines.${index}.uom_id`] ?? errors.uom_id} label="UOM">
                            <LookupSelect disabled={!line.itemId} onOpen={() => loadItemUoms(line.itemId)} onChange={(value) => updateLine(index, 'uomId', value)} options={itemUoms} placeholder={line.itemId ? 'Select item UOM' : 'Select item first'} value={line.uomId} />
                        </Field>
                        <Field error={errors[`lines.${index}.quantity`] ?? errors.quantity} label={quantityLabel}>
                            <Input min="0" onChange={(event) => updateLine(index, 'quantity', event.target.value)} type="number" value={line.quantity} />
                        </Field>
                        <Field error={errors[`lines.${index}.unit_price`] ?? errors.unit_price} label="Unit price input">
                            <Input min="0" onChange={(event) => updateLine(index, 'unitPrice', event.target.value)} type="number" value={line.unitPrice} />
                        </Field>
                        <div className="flex items-end gap-2">
                            <Button disabled={lines.length === 1} onClick={() => onChange(lines.filter((_, lineIndex) => lineIndex !== index))} type="button" variant="ghost">Remove</Button>
                        </div>
                    </div>
                );
            })}
            <Button onClick={() => onChange([...lines, emptyLine()])} type="button" variant="secondary">Add Line</Button>
        </div>
    );
}

type SalesLookupState = {
    customers: SalesLookupOption[];
    itemUoms: Record<string, SalesLookupOption[]>;
    items: SalesLookupOption[];
    warehouses: SalesLookupOption[];
};

function useSalesLookups(): {
    errors: string;
    globalError: string;
    isLoading: boolean;
    loadCustomers: () => Promise<void>;
    loadItemUoms: (itemId: string) => Promise<void>;
    loadItems: () => Promise<void>;
    loadWarehouses: () => Promise<void>;
    loading: { customers: boolean; items: boolean; warehouses: boolean };
    lookups: SalesLookupState;
} {
    const loadedLookupRef = useRef(new Set<string>());
    const loadingLookupRef = useRef(new Set<string>());
    const loadedItemUomsRef = useRef(new Set<string>());
    const loadingItemUomsRef = useRef(new Set<string>());
    const mountedRef = useRef(true);
    const [errors, setErrors] = useState('');
    const [loading, setLoading] = useState({ customers: false, items: false, warehouses: false });
    const [lookups, setLookups] = useState<SalesLookupState>({ customers: [], itemUoms: {}, items: [], warehouses: [] });

    useEffect(() => {
        return () => {
            mountedRef.current = false;
        };
    }, []);

    const loadLookup = useCallback(async (
        key: keyof Omit<SalesLookupState, 'itemUoms'>,
        loader: () => Promise<{ data: SalesLookupOption[] }>,
    ): Promise<void> => {
        if (loadedLookupRef.current.has(key) || loadingLookupRef.current.has(key)) {
            return;
        }

        loadingLookupRef.current.add(key);
        setLoading((current) => ({ ...current, [key]: true }));
        setErrors('');

        try {
            const response = await loader();
            loadedLookupRef.current.add(key);

            if (mountedRef.current) {
                setLookups((current) => ({ ...current, [key]: response.data }));
            }
        } catch (caught) {
            if (mountedRef.current) setErrors(errorMessage(caught, 'Unable to load Sales lookup options.'));
        } finally {
            loadingLookupRef.current.delete(key);
            if (mountedRef.current) {
                setLoading((current) => ({ ...current, [key]: false }));
            }
        }
    }, []);

    const loadCustomers = useCallback(() => loadLookup('customers', salesApi.lookups.customers), [loadLookup]);
    const loadItems = useCallback(() => loadLookup('items', salesApi.lookups.items), [loadLookup]);
    const loadWarehouses = useCallback(() => loadLookup('warehouses', salesApi.lookups.warehouses), [loadLookup]);

    const loadItemUoms = useCallback(async (itemId: string): Promise<void> => {
        if (!itemId || loadedItemUomsRef.current.has(itemId) || loadingItemUomsRef.current.has(itemId)) {
            return;
        }

        loadingItemUomsRef.current.add(itemId);

        try {
            const response = await salesApi.lookups.itemUoms(itemId);
            loadedItemUomsRef.current.add(itemId);

            if (!mountedRef.current) {
                return;
            }

            setLookups((current) => current.itemUoms[itemId]
                ? current
                : { ...current, itemUoms: { ...current.itemUoms, [itemId]: response.data } });
        } catch {
            // Keep the editor responsive; backend validation reports invalid item/UOM combinations.
        } finally {
            loadingItemUomsRef.current.delete(itemId);
        }
    }, []);

    return { errors, globalError: errors, isLoading: false, loadCustomers, loadItemUoms, loadItems, loadWarehouses, loading, lookups };
}

let salesLineKeySequence = 0;

function nextSalesLineClientKey(): string {
    salesLineKeySequence += 1;
    return `sales-line-${salesLineKeySequence}`;
}

function emptyLine(line?: Partial<SalesLineFormInput>): SalesLineFormInput {
    return {
        clientKey: line?.clientKey ?? nextSalesLineClientKey(),
        discountType: line?.discountType ?? '',
        discountValue: line?.discountValue ?? '',
        itemId: line?.itemId ?? '',
        quantity: line?.quantity ?? '1',
        unitPrice: line?.unitPrice ?? '0',
        uomId: line?.uomId ?? '',
    };
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

function extractFieldErrors(caught: unknown): Record<string, string> {
    if (!(caught instanceof ApiError)) return {};
    return Object.fromEntries(Object.entries(caught.errors).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.']));
}

function errorMessage(caught: unknown, fallback: string): string {
    return caught instanceof Error ? caught.message : fallback;
}

function asComponentRecord(value: unknown): Record<string, unknown> {
    return value !== null && typeof value === 'object' && !Array.isArray(value) ? value as Record<string, unknown> : {};
}

function displayPreviewValue(preview: Record<string, unknown> | undefined, keys: string[], fallback: string): string {
    if (!preview) return fallback;
    for (const key of keys) {
        const value = preview[key];
        if (value !== null && value !== undefined && value !== '') return String(value);
    }
    return fallback;
}

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
            <p className="mt-1 font-semibold text-slate-900">{value}</p>
        </div>
    );
}
