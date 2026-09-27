import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { searchCustomers } from '@/modules/customer/customerApi';
import { ItemLookupSelect } from '@/modules/item/components/ItemLookupSelect';
import { InventoryDimensionFields, emptyInventoryDimensions, type InventoryDimensionValue } from '@/modules/inventory/components/InventoryDimensionFields';
import type { ItemSummary } from '@/modules/item/itemTypes';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import type { NamedResource } from '@/shared/types/common';
import { searchSellingWarehouses, createSale, createSaleReturn, getSale, listSales, type SaleDocument, type SalePayload } from '../sellingApi';

interface SaleDraftLine {
    key: string;
    item: ItemSummary | null;
    quantity: string;
    dimensions: InventoryDimensionValue;
}

const today = businessDateInputValue();

export default function SellingWorkspacePage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [saleDate, setSaleDate] = useState(today);
    const [dueDate, setDueDate] = useState('');
    const [lines, setLines] = useState<SaleDraftLine[]>([{ key: crypto.randomUUID(), item: null, quantity: '1', dimensions: emptyInventoryDimensions() }]);
    const [sales, setSales] = useState<SaleDocument[]>([]);
    const [sale, setSale] = useState<SaleDocument | null>(null);
    const [returnQuantities, setReturnQuantities] = useState<Record<number, string>>({});
    const [returnReason, setReturnReason] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        const controller = new AbortController();
        if (id) {
            void getSale(Number(id), controller.signal).then(setSale).catch((failure: unknown) => setError(toApiError(failure)));
        } else {
            void listSales(controller.signal).then(setSales).catch((failure: unknown) => setError(toApiError(failure)));
        }
        return () => controller.abort();
    }, [id]);

    const quantityTotal = useMemo(() => Object.values(returnQuantities).filter((value) => Number(value) > 0).length, [returnQuantities]);

    async function submitSale(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!customer || !warehouse || lines.some((line) => !line.item || Number(line.quantity) <= 0)) return;
        const payload: SalePayload = {
            customer_id: customer.id,
            warehouse_id: warehouse.id,
            sale_date: saleDate,
            ...(dueDate ? { due_date: dueDate } : {}),
            lines: lines.map((line) => ({
                item_id: line.item!.id,
                uom_id: line.item!.base_uom?.id ?? 0,
                quantity: line.quantity,
                ...(line.dimensions.itemVariant ? { item_variant_id: line.dimensions.itemVariant.id } : {}),
                ...(line.dimensions.batch ? { batch_id: line.dimensions.batch.id } : {}),
                ...(line.dimensions.serial ? { serial_number_id: line.dimensions.serial.id } : {}),
            })),
        };
        setBusy(true);
        setError(null);
        try {
            const created = await createSale(payload);
            navigate(`/selling/sales/${created.id}`);
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setBusy(false);
        }
    }

    async function submitReturn(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!sale || quantityTotal === 0 || returnReason.trim() === '') return;
        setBusy(true);
        setError(null);
        try {
            const updated = await createSaleReturn(sale, {
                return_date: today,
                reason: returnReason,
                lines: Object.entries(returnQuantities)
                    .filter(([, quantity]) => Number(quantity) > 0)
                    .map(([saleLineId, quantity]) => ({ sale_line_id: Number(saleLineId), quantity })),
            });
            setSale(updated);
            setReturnQuantities({});
            setReturnReason('');
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setBusy(false);
        }
    }

    if (id && !sale) {
        return <><ContentHeader title="Sales invoice" /><ErrorAlert error={error} /></>;
    }

    if (sale) {
        return (
            <>
                <ContentHeader title={`Sale ${sale.sale_number}`} description="Posted sale and its linked customer invoice." actions={<Link className="text-sm font-medium text-sky-700" to="/selling">Back to Selling</Link>} />
                <ErrorAlert error={error} />
                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-5">
                        <Panel title="Invoice">
                            <p className="text-sm text-slate-600">Customer: <span className="font-medium text-slate-900">{sale.customer?.name}</span></p>
                            <p className="mt-1 text-sm text-slate-600">Warehouse: <span className="font-medium text-slate-900">{sale.warehouse?.name}</span></p>
                            <DataTable rows={sale.lines} rowKey={(line) => line.id} columns={[
                                { key: 'item', header: 'Item', render: (line) => `${line.item.code} · ${line.item.name}${line.variant ? ` · ${line.variant.name}` : ''}${line.batch ? ` · Batch ${line.batch.name}` : ''}${line.serial_number ? ` · Serial ${line.serial_number}` : ''}` },
                                { key: 'quantity', header: 'Quantity', render: (line) => `${line.quantity} ${line.uom.code}` },
                                { key: 'price', header: 'Unit price', render: (line) => line.unit_price },
                                { key: 'total', header: 'Line total', render: (line) => line.line_total },
                            ]} />
                            {sale.invoice && <div className="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-md bg-slate-50 px-4 py-3 text-sm">
                                <div><span className="font-semibold">Invoice {sale.invoice.number}</span><span className="ml-3 text-slate-600">Balance due {sale.invoice.balance_due}</span></div>
                                <Link className="font-semibold text-sky-700" to={`/invoices/${sale.invoice.id}`}>Open invoice</Link>
                            </div>}
                        </Panel>
                        {sale.returns?.length ? <Panel title="Returns and credit notes">
                            <DataTable rows={sale.returns} rowKey={(row) => row.id} columns={[
                                { key: 'number', header: 'Credit note', render: (row) => row.number },
                                { key: 'date', header: 'Date', render: (row) => row.date },
                                { key: 'reason', header: 'Reason', render: (row) => row.reason },
                                { key: 'credit', header: 'Credit', render: (row) => row.credit_amount },
                                { key: 'available', header: 'Unapplied credit', render: (row) => row.credit_available_amount },
                            ]} />
                        </Panel> : null}
                    </div>
                    <Panel title="Create a return">
                        <form className="space-y-4" onSubmit={(event) => void submitReturn(event)}>
                            <p className="text-sm leading-5 text-slate-600">Returned stock is received into this sale’s warehouse. The credit note is applied to the invoice balance where an amount remains.</p>
                            {sale.lines.map((line) => <DecimalInput key={line.id} label={`${line.item.name} · max ${line.quantity} ${line.uom.code}`} value={returnQuantities[line.id] ?? ''} onChange={(event) => setReturnQuantities((current) => ({ ...current, [line.id]: event.target.value }))} />)}
                            <Textarea label="Return reason" required value={returnReason} onChange={(event) => setReturnReason(event.target.value)} />
                            <Button type="submit" loading={busy} disabled={quantityTotal === 0 || !returnReason.trim()}>Post return and credit note</Button>
                        </form>
                    </Panel>
                </div>
            </>
        );
    }

    return (
        <>
            <ContentHeader title="Selling" description="Sell stocked items, issue inventory, and post the customer invoice in one step." />
            <ErrorAlert error={error} />
            <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <form className="space-y-5" onSubmit={(event) => void submitSale(event)}>
                    <Panel title="Sale details">
                        <div className="grid gap-4 md:grid-cols-2">
                            <LookupSelect<CustomerSummary> label="Customer" value={customer} onChange={setCustomer} search={searchCustomers} placeholder="Search customers..." required />
                            <LookupSelect<NamedResource> label="Warehouse" value={warehouse} onChange={setWarehouse} search={searchSellingWarehouses} placeholder="Choose a warehouse..." required loadOnOpen minSearchLength={0} />
                            <Input label="Sale date" type="date" value={saleDate} onChange={(event) => setSaleDate(event.target.value)} required />
                            <Input label="Due date" type="date" hint="Set a due date to record this invoice on credit." value={dueDate} onChange={(event) => setDueDate(event.target.value)} min={saleDate} />
                        </div>
                    </Panel>
                    <Panel title="Stocked items">
                        <div className="space-y-4">
                            {lines.map((line, index) => <div key={line.key} className="grid gap-3 rounded-md border border-slate-200 p-3 sm:grid-cols-[minmax(0,1fr)_150px_auto]">
                                <ItemLookupSelect label="Item" value={line.item} onChange={(item) => setLines((current) => current.map((row) => row.key === line.key ? { ...row, item, dimensions: emptyInventoryDimensions() } : row))} />
                                <DecimalInput label="Quantity" value={line.quantity} onChange={(event) => setLines((current) => current.map((row) => row.key === line.key ? { ...row, quantity: event.target.value } : row))} />
                                <Button type="button" variant="secondary" disabled={lines.length === 1} aria-label={`Remove item ${index + 1}`} onClick={() => setLines((current) => current.filter((row) => row.key !== line.key))}>Remove</Button>
                                {line.item && <InventoryDimensionFields item={line.item} warehouse={warehouse} value={line.dimensions} includeLocation={false} includeSerial onChange={(dimensions) => setLines((current) => current.map((row) => row.key === line.key ? { ...row, dimensions } : row))} />}
                            </div>)}
                            <Button type="button" variant="secondary" onClick={() => setLines((current) => [...current, { key: crypto.randomUUID(), item: null, quantity: '1', dimensions: emptyInventoryDimensions() }])}>Add item</Button>
                        </div>
                    </Panel>
                    <Button type="submit" loading={busy} disabled={!customer || !warehouse || lines.some((line) => !line.item || !line.item.base_uom || Number(line.quantity) <= 0)}>Post sale and invoice</Button>
                </form>
                <Panel title="Recent sales">
                    {sales.length === 0 ? <p className="text-sm text-slate-500">No sales have been recorded yet.</p> : <DataTable rows={sales} rowKey={(row) => row.id} columns={[
                        { key: 'sale', header: 'Sale', render: (row) => <Link className="font-medium text-sky-700" to={`/selling/sales/${row.id}`}>{row.sale_number}</Link> },
                        { key: 'customer', header: 'Customer', render: (row) => row.customer?.name ?? 'Customer' },
                        { key: 'invoice', header: 'Invoice', render: (row) => row.invoice?.number ?? 'Pending' },
                    ]} />}
                </Panel>
            </div>
        </>
    );
}
