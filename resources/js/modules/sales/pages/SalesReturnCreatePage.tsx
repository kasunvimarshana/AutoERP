import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { readableRelation } from '@/shared/utils/object';
import { createSalesReturn, getReturnableSalesDeliveryLines, type ReturnableSalesLine } from '../salesApi';
import type { SalesReturnPayload, SalesReturnType } from '../salesTypes';
import {
    CustomerLookupSelect,
    SalesDeliveryLookupSelect,
    SalesItemLookupSelect,
    SalesOrderLookupSelect,
    SalesUomLookupSelect,
    SalesWarehouseLocationLookupSelect,
    SalesWarehouseLookupSelect,
} from '../components/SalesLookups';

interface ReturnLineDraft {
    key: string;
    source_line_type?: 'sales_delivery_line';
    source_line_id?: number;
    item: NamedResource | null;
    uom: NamedResource | null;
    available?: string;
    quantity: string;
    unit_price: string;
    cost_basis: string;
    condition_status: 'sellable' | 'damaged' | 'quarantine' | 'scrap';
    reason: string;
}

const returnTypes: Array<{ value: SalesReturnType; label: string }> = [
    { value: 'referenced_customer_return', label: 'Referenced customer return' },
    { value: 'manual_customer_return', label: 'Manual customer return' },
    { value: 'credit_note_only', label: 'Credit note only' },
    { value: 'inventory_adjustment_only', label: 'Inventory adjustment only' },
    { value: 'warranty_replacement', label: 'Warranty / replacement' },
    { value: 'exchange_return', label: 'Exchange return' },
    { value: 'opening_imported_return', label: 'Opening / imported return' },
];

export default function SalesReturnCreatePage() {
    const navigate = useNavigate();
    const [type, setType] = useState<SalesReturnType>('referenced_customer_return');
    const [customer, setCustomer] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [location, setLocation] = useState<NamedResource | null>(null);
    const [delivery, setDelivery] = useState<NamedResource | null>(null);
    const [replacementOrder, setReplacementOrder] = useState<NamedResource | null>(null);
    const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
    const [reason, setReason] = useState('');
    const [headerCostBasis, setHeaderCostBasis] = useState('0.000000');
    const [lines, setLines] = useState<ReturnLineDraft[]>([]);
    const [manualItem, setManualItem] = useState<NamedResource | null>(null);
    const [manualUom, setManualUom] = useState<NamedResource | null>(null);
    const [manualQuantity, setManualQuantity] = useState('1.000000');
    const [manualPrice, setManualPrice] = useState('0.000000');
    const [manualCost, setManualCost] = useState('0.000000');
    const [condition, setCondition] = useState<ReturnLineDraft['condition_status']>('sellable');
    const [sourceLoading, setSourceLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const errorFor = (name: string) => fieldError(error, name);
    const referenced = type === 'referenced_customer_return';
    const replacement = type === 'warranty_replacement' || type === 'exchange_return';
    const approvalRequired = ['manual_customer_return', 'inventory_adjustment_only', 'warranty_replacement', 'exchange_return', 'opening_imported_return'].includes(type);
    const inventoryAffected = type !== 'credit_note_only';

    const loadDelivery = async (source: NamedResource | null) => {
        setDelivery(source);
        setLines([]);
        if (!source) return;
        setSourceLoading(true);
        setError(null);
        try {
            const returnable = await getReturnableSalesDeliveryLines(source.id);
            setLines(returnable.map(fromReturnableLine));
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSourceLoading(false);
        }
    };

    const addManualLine = () => {
        if (!manualItem || !manualUom) return;
        setLines((current) => [...current, {
            key: `manual:${Date.now()}`,
            item: manualItem,
            uom: manualUom,
            quantity: manualQuantity,
            unit_price: manualPrice,
            cost_basis: manualCost,
            condition_status: condition,
            reason: '',
        }]);
        setManualItem(null);
        setManualUom(null);
        setManualQuantity('1.000000');
    };

    const columns: DataColumn<ReturnLineDraft>[] = [
        { key: 'item', header: 'Item', render: (row) => readableRelation(row.item) },
        { key: 'available', header: 'Available', render: (row) => row.available ?? '-' },
        { key: 'quantity', header: 'Return qty', render: (row) => <DecimalInput aria-label={`Return quantity for ${row.item?.name ?? 'item'}`} value={row.quantity} onChange={(event) => setLines((current) => current.map((line) => line.key === row.key ? { ...line, quantity: event.target.value } : line))} /> },
        { key: 'condition', header: 'Condition', render: (row) => row.condition_status },
        { key: 'actions', header: 'Actions', render: (row) => <Button type="button" variant="danger" onClick={() => setLines((current) => current.filter((line) => line.key !== row.key))}>Remove</Button> },
    ];

    return (
        <>
            <ContentHeader title="Create sales return" description="Select the business scenario first; backend services decide Inventory and customer-balance effects." />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                if (!customer || lines.length === 0 || submitting || sourceLoading) return;
                setSubmitting(true);
                setError(null);
                const payload: SalesReturnPayload = {
                    return_date: date,
                    customer_id: customer.id,
                    return_type: type,
                    warehouse_id: inventoryAffected ? warehouse?.id : undefined,
                    warehouse_location_id: inventoryAffected ? location?.id : undefined,
                    replacement_sales_order_id: replacementOrder?.id,
                    approval_required: approvalRequired,
                    cost_basis: approvalRequired ? headerCostBasis : undefined,
                    reason: reason || undefined,
                    audit_metadata: type === 'opening_imported_return' ? { source: 'frontend_opening_import' } : undefined,
                    lines: lines.map((line) => ({
                        source_line_type: line.source_line_type,
                        source_line_id: line.source_line_id,
                        item_id: line.source_line_id ? undefined : line.item?.id,
                        uom_id: line.source_line_id ? undefined : line.uom?.id,
                        returned_quantity: line.quantity,
                        unit_price: line.unit_price,
                        cost_basis: line.cost_basis || undefined,
                        condition_status: line.condition_status,
                        reason: line.reason || undefined,
                    })),
                };
                try {
                    await createSalesReturn(payload);
                    navigate('/sales/returns');
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Return scenario">
                    <div className="grid gap-4 md:grid-cols-4">
                        <Select label="Return type" value={type} options={returnTypes} onChange={(event) => { setType(event.target.value as SalesReturnType); setLines([]); setDelivery(null); }} />
                        <CustomerLookupSelect value={customer} onChange={setCustomer} error={errorFor('customer_id')} />
                        <Input type="date" label="Return date" value={date} error={errorFor('return_date')} onChange={(event) => setDate(event.target.value)} />
                        {referenced && <SalesDeliveryLookupSelect value={delivery} onChange={(value) => void loadDelivery(value)} />}
                        {replacement && <SalesOrderLookupSelect value={replacementOrder} onChange={setReplacementOrder} error={errorFor('replacement_sales_order_id')} />}
                        {inventoryAffected && <SalesWarehouseLookupSelect value={warehouse} onChange={(value) => { setWarehouse(value); setLocation(null); }} error={errorFor('warehouse_id')} />}
                        {inventoryAffected && <SalesWarehouseLocationLookupSelect warehouseId={warehouse?.id} value={location} onChange={setLocation} error={errorFor('warehouse_location_id')} />}
                        {approvalRequired && <DecimalInput label="Cost basis" value={headerCostBasis} error={errorFor('cost_basis')} onChange={(event) => setHeaderCostBasis(event.target.value)} />}
                    </div>
                    <div className="mt-4"><Textarea label="Reason / audit note" value={reason} error={errorFor('reason')} onChange={(event) => setReason(event.target.value)} /></div>
                </Panel>
                {!referenced && (
                    <Panel title="Add return line">
                        <div className="grid gap-4 md:grid-cols-4">
                            <SalesItemLookupSelect value={manualItem} onChange={setManualItem} />
                            <SalesUomLookupSelect value={manualUom} onChange={setManualUom} />
                            <DecimalInput label="Quantity" value={manualQuantity} onChange={(event) => setManualQuantity(event.target.value)} />
                            <DecimalInput label="Unit price" value={manualPrice} onChange={(event) => setManualPrice(event.target.value)} />
                            {approvalRequired && <DecimalInput label="Line cost basis" value={manualCost} onChange={(event) => setManualCost(event.target.value)} />}
                            {inventoryAffected && <Select label="Condition" value={condition} options={['sellable', 'damaged', 'quarantine', 'scrap'].map((value) => ({ value, label: value }))} onChange={(event) => setCondition(event.target.value as ReturnLineDraft['condition_status'])} />}
                        </div>
                        <div className="mt-4"><Button type="button" variant="secondary" disabled={!manualItem || !manualUom} onClick={addManualLine}>Add line</Button></div>
                    </Panel>
                )}
                <Panel title="Return lines">
                    <DataTable rows={lines} columns={columns} rowKey={(row) => row.key} emptyMessage={sourceLoading ? 'Loading returnable lines...' : referenced ? 'Select a delivery to load returnable lines.' : 'Add at least one return line.'} />
                </Panel>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate('/sales/returns')}>Cancel</Button>
                    <Button type="submit" loading={submitting} disabled={!customer || lines.length === 0 || sourceLoading}>Create return</Button>
                </div>
            </form>
        </>
    );
}

function fromReturnableLine(line: ReturnableSalesLine): ReturnLineDraft {
    return {
        key: `delivery:${line.source_line_id}`,
        source_line_type: line.source_line_type,
        source_line_id: line.source_line_id,
        item: line.item ?? null,
        uom: line.uom ?? null,
        available: line.returnable_quantity,
        quantity: line.returnable_quantity,
        unit_price: line.unit_price,
        cost_basis: '0.000000',
        condition_status: 'sellable',
        reason: '',
    };
}
