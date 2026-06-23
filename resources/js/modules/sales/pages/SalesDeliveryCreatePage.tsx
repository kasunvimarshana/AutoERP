import { useCallback, useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { readableRelation } from '@/shared/utils/object';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { parsePositiveInteger } from '@/shared/utils/routeParams';
import { createSalesDelivery, getDeliverableSalesOrderLines, getSalesOrder, type SalesLineSummary } from '../salesApi';
import type { SalesOrder } from '../salesTypes';
import {
    SalesOrderLookupSelect,
    SalesWarehouseLocationLookupSelect,
    SalesWarehouseLookupSelect,
} from '../components/SalesLookups';

interface DeliveryDraftLine extends SalesLineSummary {
    delivered_quantity: string;
}

export default function SalesDeliveryCreatePage() {
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const [initialOrderId] = useState(() => parsePositiveInteger(params.get('order_id')));
    const [orderRef, setOrderRef] = useState<NamedResource | null>(null);
    const [order, setOrder] = useState<SalesOrder | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [location, setLocation] = useState<NamedResource | null>(null);
    const [date, setDate] = useState(businessDateInputValue());
    const [notes, setNotes] = useState('');
    const [lines, setLines] = useState<DeliveryDraftLine[]>([]);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const errorFor = (name: string) => fieldError(error, name);

    const loadOrder = useCallback(async (selected: NamedResource | null) => {
        setOrderRef(selected);
        setOrder(null);
        setLines([]);
        if (!selected) return;
        try {
            const [document, deliverable] = await Promise.all([
                getSalesOrder(selected.id),
                getDeliverableSalesOrderLines(selected.id),
            ]);
            setOrderRef({
                id: document.id,
                code: document.sales_order_number,
                name: `${document.sales_order_number ?? 'Sales order'}${document.customer?.name ? ` - ${document.customer.name}` : ''}`,
            });
            setOrder(document);
            setWarehouse(document.warehouse ?? null);
            setLines(deliverable.map((line) => ({
                ...line,
                delivered_quantity: line.remaining_deliverable_quantity ?? line.remaining_quantity ?? '0.000000',
            })));
        } catch (requestError) {
            setError(toApiError(requestError));
        }
    }, []);

    useEffect(() => {
        const id = initialOrderId;
        if (id !== null) void Promise.resolve().then(() => loadOrder({ id, name: 'Loading sales order...' }));
        // The query-string source is only applied when the create page opens.
    }, [initialOrderId, loadOrder]);

    const columns: DataColumn<DeliveryDraftLine>[] = [
        { key: 'item', header: 'Item', render: (row) => readableRelation(row.item) },
        { key: 'available', header: 'Deliverable', render: (row) => row.remaining_deliverable_quantity ?? row.remaining_quantity ?? '-' },
        { key: 'uom', header: 'UOM', render: (row) => readableRelation(row.uom) },
        {
            key: 'quantity',
            header: 'Deliver now',
            render: (row) => <DecimalInput aria-label={`Delivery quantity for ${row.item?.name ?? 'item'}`} value={row.delivered_quantity} onChange={(event) => setLines((current) => current.map((line) => line.id === row.id ? { ...line, delivered_quantity: event.target.value } : line))} />,
        },
    ];

    return (
        <>
            <ContentHeader title="Create sales delivery" description="Choose an approved order; deliverable lines and quantities come from the backend." />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                if (!order || !warehouse || submitting) return;
                setSubmitting(true);
                setError(null);
                try {
                    const delivery = await createSalesDelivery({
                        delivery_date: date,
                        sales_order_id: order.id,
                        customer_id: order.customer_id ?? 0,
                        warehouse_id: warehouse.id,
                        warehouse_location_id: location?.id,
                        notes: notes || undefined,
                        lines: lines.filter((line) => line.delivered_quantity !== '0' && line.delivered_quantity !== '0.000000').map((line) => ({
                            sales_order_line_id: line.sales_order_line_id ?? line.id,
                            item_id: line.item?.id ?? 0,
                            uom_id: line.uom?.id,
                            ordered_quantity: line.remaining_deliverable_quantity,
                            delivered_quantity: line.delivered_quantity,
                            unit_price: line.unit_price,
                        })),
                    });
                    navigate('/sales/deliveries', { state: { createdId: delivery.id } });
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Source and dispatch">
                    <div className="grid gap-4 md:grid-cols-4">
                        <SalesOrderLookupSelect value={orderRef} onChange={(value) => void loadOrder(value)} error={errorFor('sales_order_id')} />
                        <SalesWarehouseLookupSelect value={warehouse} onChange={(value) => { setWarehouse(value); setLocation(null); }} error={errorFor('warehouse_id')} />
                        <SalesWarehouseLocationLookupSelect warehouseId={warehouse?.id} value={location} onChange={setLocation} error={errorFor('warehouse_location_id')} />
                        <Input type="date" label="Delivery date" value={date} error={errorFor('delivery_date')} onChange={(event) => setDate(event.target.value)} />
                    </div>
                    <div className="mt-4"><Textarea label="Notes" value={notes} onChange={(event) => setNotes(event.target.value)} /></div>
                </Panel>
                <Panel title="Deliverable lines">
                    <DataTable rows={lines} columns={columns} rowKey={(row) => row.id} emptyMessage="Select an approved sales order to load deliverable lines." />
                </Panel>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate('/sales/deliveries')}>Cancel</Button>
                    <Button type="submit" loading={submitting} disabled={!order || lines.length === 0}>Create delivery</Button>
                </div>
            </form>
        </>
    );
}
