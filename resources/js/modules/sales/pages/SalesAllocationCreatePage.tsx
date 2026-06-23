import { useEffect, useState } from 'react';
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
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { parsePositiveInteger } from '@/shared/utils/routeParams';
import { readableRelation } from '@/shared/utils/object';
import { createSalesAllocation, getAllocatableSalesOrderLines, getSalesOrder, type SalesLineSummary } from '../salesApi';
import {
    SalesOrderLookupSelect,
    SalesWarehouseLocationLookupSelect,
    SalesWarehouseLookupSelect,
} from '../components/SalesLookups';
import type { SalesOrder } from '../salesTypes';

interface AllocationDraftLine extends SalesLineSummary {
    quantity: string;
}

export default function SalesAllocationCreatePage() {
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const [orderRef, setOrderRef] = useState<NamedResource | null>(null);
    const [order, setOrder] = useState<SalesOrder | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [location, setLocation] = useState<NamedResource | null>(null);
    const [date, setDate] = useState(businessDateInputValue());
    const [notes, setNotes] = useState('');
    const [lines, setLines] = useState<AllocationDraftLine[]>([]);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const errorFor = (name: string) => fieldError(error, name);

    const loadOrder = async (selected: NamedResource | null) => {
        setOrderRef(selected);
        setOrder(null);
        setLines([]);
        if (!selected) return;
        try {
            const [document, allocatable] = await Promise.all([
                getSalesOrder(selected.id),
                getAllocatableSalesOrderLines(selected.id),
            ]);
            setOrderRef({
                id: document.id,
                code: document.sales_order_number,
                name: `${document.sales_order_number ?? 'Sales order'}${document.customer?.name ? ` - ${document.customer.name}` : ''}`,
            });
            setOrder(document);
            setWarehouse(document.warehouse ?? null);
            setLines(allocatable.map((line) => ({
                ...line,
                quantity: line.remaining_allocatable_quantity ?? line.remaining_quantity ?? '0.000000',
            })));
        } catch (requestError) {
            setError(toApiError(requestError));
        }
    };

    useEffect(() => {
        const id = parsePositiveInteger(params.get('order_id'));
        if (id !== null) void loadOrder({ id, name: 'Loading sales order...' });
    }, []);

    const columns: DataColumn<AllocationDraftLine>[] = [
        { key: 'item', header: 'Item', render: (row) => readableRelation(row.item) },
        { key: 'available', header: 'Allocatable', render: (row) => row.remaining_allocatable_quantity ?? row.remaining_quantity ?? '-' },
        { key: 'uom', header: 'UOM', render: (row) => readableRelation(row.uom) },
        {
            key: 'quantity',
            header: 'Reserve',
            render: (row) => <DecimalInput aria-label={`Allocation quantity for ${row.item?.name ?? 'item'}`} value={row.quantity} onChange={(event) => setLines((current) => current.map((line) => line.id === row.id ? { ...line, quantity: event.target.value } : line))} />,
        },
    ];

    return (
        <>
            <ContentHeader title="Create sales allocation" description="Reserve backend-approved quantities for an approved sales order." />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                if (!order || !warehouse || submitting) return;
                setSubmitting(true);
                setError(null);
                try {
                    const allocation = await createSalesAllocation({
                        allocation_date: date,
                        sales_order_id: order.id,
                        warehouse_id: warehouse.id,
                        warehouse_location_id: location?.id,
                        notes: notes || undefined,
                        lines: lines.filter((line) => line.quantity !== '0' && line.quantity !== '0.000000').map((line) => ({
                            sales_order_line_id: line.sales_order_line_id ?? line.id,
                            quantity: line.quantity,
                        })),
                    });
                    navigate(`/sales/allocations/${allocation.id}`);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Source and warehouse">
                    <div className="grid gap-4 md:grid-cols-4">
                        <SalesOrderLookupSelect value={orderRef} onChange={(value) => void loadOrder(value)} error={errorFor('sales_order_id')} />
                        <SalesWarehouseLookupSelect value={warehouse} onChange={(value) => { setWarehouse(value); setLocation(null); }} error={errorFor('warehouse_id')} />
                        <SalesWarehouseLocationLookupSelect warehouseId={warehouse?.id} value={location} onChange={setLocation} error={errorFor('warehouse_location_id')} />
                        <Input type="date" label="Allocation date" value={date} error={errorFor('allocation_date')} onChange={(event) => setDate(event.target.value)} />
                    </div>
                    <div className="mt-4"><Textarea label="Notes" value={notes} onChange={(event) => setNotes(event.target.value)} /></div>
                </Panel>
                <Panel title="Allocatable lines">
                    <DataTable rows={lines} columns={columns} rowKey={(row) => row.id} emptyMessage="Select an approved sales order to load allocatable lines." />
                </Panel>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate('/sales/allocations')}>Cancel</Button>
                    <Button type="submit" loading={submitting} disabled={!order || lines.length === 0}>Create allocation</Button>
                </div>
            </form>
        </>
    );
}
