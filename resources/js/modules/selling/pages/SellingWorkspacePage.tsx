import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
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
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import { InventoryDimensionFields, emptyInventoryDimensions, type InventoryDimensionValue } from '@/modules/inventory/components/InventoryDimensionFields';
import { multiplyDecimal } from '@/shared/utils/decimal';
import type { ItemLookupResource } from '@/shared/api/lookupApi';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import type { NamedResource } from '@/shared/types/common';
import { searchSellingWarehouses, searchSellingLocations, getSellingWarehouseSource, createSale, createSaleReturn, getSale, listSales, type SaleDocument, type SalePayload } from '../sellingApi';
import { SellingItemLookup } from '../components/SellingItemLookup';

interface SaleDraftLine {
    key: string;
    item: ItemLookupResource;
    quantity: string;
    dimensions: InventoryDimensionValue;
}

const today = businessDateInputValue();

export default function SellingWorkspacePage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [resolvingSource, setResolvingSource] = useState(false);
    const [saleDate, setSaleDate] = useState(today);
    const [dueDate, setDueDate] = useState('');
    const [lines, setLines] = useState<SaleDraftLine[]>([]);
    const [sales, setSales] = useState<SaleDocument[]>([]);
    const [sale, setSale] = useState<SaleDocument | null>(null);
    const [returnQuantities, setReturnQuantities] = useState<Record<number, string>>({});
    const [returnReason, setReturnReason] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const sourceRequest = useRef<AbortController | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        if (id) {
            void getSale(Number(id), controller.signal).then(setSale).catch((failure: unknown) => setError(toApiError(failure)));
        } else {
            void listSales(controller.signal).then(setSales).catch((failure: unknown) => setError(toApiError(failure)));
        }
        return () => controller.abort();
    }, [id]);

    useEffect(() => {
        if (id) return;

        const controller = new AbortController();
        sourceRequest.current = controller;
        void getSellingWarehouseSource(undefined, controller.signal)
            .then((source) => {
                if (controller.signal.aborted) return;
                setWarehouse(source.warehouse);
                setWarehouseLocation(source.location);
            })
            .catch((failure: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(failure));
            })
            .finally(() => {
                if (!controller.signal.aborted) setResolvingSource(false);
            });

        return () => controller.abort();
    }, [id]);

    const quantityTotal = useMemo(() => Object.values(returnQuantities).filter((value) => Number(value) > 0).length, [returnQuantities]);
    const locationSearch = useCallback(
        (params: Parameters<typeof searchSellingLocations>[0]) => searchSellingLocations(params, warehouse?.id),
        [warehouse?.id],
    );

    async function submitSale(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!customer || !warehouse || lines.length === 0 || lines.some((line) => Number(line.quantity) <= 0)) return;
        const payload: SalePayload = {
            customer_id: customer.id,
            warehouse_id: warehouse.id,
            ...(warehouseLocation ? { warehouse_location_id: warehouseLocation.id } : {}),
            sale_date: saleDate,
            ...(dueDate ? { due_date: dueDate } : {}),
            lines: lines.map((line) => ({
                item_id: line.item.id,
                uom_id: line.item.base_uom?.id ?? 0,
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

    function changeWarehouse(nextWarehouse: NamedResource | null) {
        sourceRequest.current?.abort();
        setWarehouse(nextWarehouse);
        setWarehouseLocation(null);
        setLines((current) => current.map((line) => ({ ...line, dimensions: emptyInventoryDimensions() })));

        if (!nextWarehouse) {
            setResolvingSource(false);
            return;
        }

        const controller = new AbortController();
        sourceRequest.current = controller;
        setResolvingSource(true);
        void getSellingWarehouseSource(nextWarehouse.id, controller.signal)
            .then((source) => {
                if (!controller.signal.aborted) setWarehouseLocation(source.location);
            })
            .catch((failure: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(failure));
            })
            .finally(() => {
                if (!controller.signal.aborted) setResolvingSource(false);
            });
    }

    function addItem(item: ItemLookupResource | null) {
        if (!item) return;
        setLines((current) => [...current, {
            key: crypto.randomUUID(),
            item,
            quantity: '1',
            dimensions: emptyInventoryDimensions(),
        }]);
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
                            <p className="mt-1 text-sm text-slate-600">Warehouse: <span className="font-medium text-slate-900">{sale.warehouse?.name}</span>{sale.warehouse_location && <> · Location: <span className="font-medium text-slate-900">{sale.warehouse_location.name}</span></>}</p>
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
                            <CustomerLookupSelect value={customer} onChange={setCustomer} placeholder="Search customers..." required />
                            <LookupSelect<NamedResource> label="Warehouse" value={warehouse} onChange={changeWarehouse} search={searchSellingWarehouses} placeholder="Choose a warehouse..." required loadOnOpen minSearchLength={0} />
                            <LookupSelect<NamedResource> label="Location" value={warehouseLocation} onChange={setWarehouseLocation} search={locationSearch} placeholder={warehouse ? 'Choose a location...' : 'Choose a warehouse first'} disabled={!warehouse || resolvingSource} loadOnOpen minSearchLength={0} />
                            <Input label="Sale date" type="date" value={saleDate} onChange={(event) => setSaleDate(event.target.value)} required />
                            <Input label="Due date" type="date" hint="Set a due date to record this invoice on credit." value={dueDate} onChange={(event) => setDueDate(event.target.value)} min={saleDate} />
                        </div>
                    </Panel>
                    <Panel title="Stocked items">
                        <div className="space-y-4">
                            <SellingItemLookup key={`${warehouse?.id ?? 'no-warehouse'}-${warehouseLocation?.id ?? 'no-location'}-${saleDate}`} value={null} warehouseId={warehouse?.id ?? null} warehouseLocationId={warehouseLocation?.id ?? null} saleDate={saleDate} onChange={addItem} />
                            <DataTable rows={lines} rowKey={(line) => line.key} emptyMessage="Search and select an item to add it to this sale." columns={[
                                { key: 'item', header: 'Item', render: (line) => <div className="min-w-64 space-y-2">
                                    <div><div className="font-medium text-slate-900">{line.item.name}</div><div className="text-xs text-slate-500">{line.item.code}</div></div>
                                    <InventoryDimensionFields item={line.item} warehouse={warehouse} value={line.dimensions} includeLocation={false} includeSerial includeUom={false} onChange={(dimensions) => setLines((current) => current.map((row) => row.key === line.key ? { ...row, dimensions } : row))} />
                                </div> },
                                { key: 'quantity', header: 'Quantity', render: (line) => <DecimalInput aria-label={`Quantity for ${line.item.name}`} className="w-28" maxFractionDigits={3} value={line.quantity} onChange={(event) => setLines((current) => current.map((row) => row.key === line.key ? { ...row, quantity: event.target.value } : row))} /> },
                                { key: 'price', header: 'Unit price', render: (line) => `${line.item.tenant_base_currency?.symbol ?? line.item.tenant_base_currency?.code ?? ''} ${line.item.resolved_sales_unit_price ?? 'Not configured'}` },
                                { key: 'subtotal', header: 'Subtotal', render: (line) => line.item.resolved_sales_unit_price === null || line.item.resolved_sales_unit_price === undefined ? '—' : `${line.item.tenant_base_currency?.symbol ?? line.item.tenant_base_currency?.code ?? ''} ${multiplyDecimal(line.quantity, line.item.resolved_sales_unit_price)}` },
                                { key: 'actions', header: 'Actions', className: 'text-right', render: (line) => <Button type="button" variant="secondary" aria-label={`Remove ${line.item.name}`} onClick={() => setLines((current) => current.filter((row) => row.key !== line.key))}>Remove</Button> },
                            ]} />
                        </div>
                    </Panel>
                    <Button type="submit" loading={busy} disabled={!customer || !warehouse || lines.length === 0 || lines.some((line) => !line.item.base_uom || Number(line.quantity) <= 0)}>Post sale and invoice</Button>
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
