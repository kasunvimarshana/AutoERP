import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { createGoodsReceipt, getPurchaseOrder, getReceivablePurchaseOrderLines, type GoodsReceiptPayload, type PurchaseOrder, type PurchaseOrderLine } from '../purchaseApi';
import { PurchaseOrderLookupSelect, WarehouseLocationLookupSelect } from './PurchaseLookups';
import { GoodsReceiptLineEditor, type EditableGoodsReceiptLine } from './GoodsReceiptLineEditor';

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

function decimal(value: string | undefined, fallback = '0.000000'): string {
    return value && value.trim() !== '' ? value : fallback;
}

function orderLabel(order: PurchaseOrder): NamedResource {
    return {
        id: order.id,
        code: order.purchase_order_number ?? `PO-${order.id}`,
        name: `${order.purchase_order_number ?? `PO #${order.id}`}${order.supplier?.name ? ` - ${order.supplier.name}` : ''}`,
    };
}

function editableLine(line: PurchaseOrderLine): EditableGoodsReceiptLine {
    const remaining = line.remaining_receivable_quantity ?? line.remaining_quantity ?? '0.000000';
    return { source: line, received_quantity: remaining, accepted_quantity: remaining, rejected_quantity: '0.000000' };
}

export function GoodsReceiptForm() {
    const navigate = useNavigate();
    const [purchaseOrder, setPurchaseOrder] = useState<NamedResource | null>(null);
    const [sourceOrder, setSourceOrder] = useState<PurchaseOrder | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [receivedDate, setReceivedDate] = useState(today());
    const [notes, setNotes] = useState('');
    const [lines, setLines] = useState<EditableGoodsReceiptLine[]>([]);
    const [loadingLines, setLoadingLines] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const errorFor = (field: string) => fieldError(error, field);

    useEffect(() => {
        if (!purchaseOrder?.id) {
            setSourceOrder(null);
            setLines([]);
            return;
        }

        const controller = new AbortController();
        setLoadingLines(true);
        setError(null);
        Promise.all([
            getPurchaseOrder(Number(purchaseOrder.id), controller.signal),
            getReceivablePurchaseOrderLines(Number(purchaseOrder.id), controller.signal),
        ])
            .then(([order, receivableLines]) => {
                if (controller.signal.aborted) return;
                setSourceOrder(order);
                setPurchaseOrder(orderLabel(order));
                setWarehouseLocation(order.warehouse_location ?? null);
                setLines(receivableLines.map(editableLine));
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoadingLines(false);
            });

        return () => controller.abort();
    }, [purchaseOrder?.id]);

    const payload = (): GoodsReceiptPayload => ({
        received_date: receivedDate,
        purchase_order_id: sourceOrder?.id,
        warehouse_id: sourceOrder?.warehouse?.id ?? sourceOrder?.warehouse_id ?? 0,
        warehouse_location_id: warehouseLocation?.id,
        supplier_type: 'supplier',
        supplier_id: sourceOrder?.supplier?.id ?? sourceOrder?.supplier_id ?? undefined,
        notes: notes || undefined,
        lines: lines.map((line) => ({
            item_id: line.source.item?.id ?? line.source.item_id ?? 0,
            item_variant_id: line.source.item_variant?.id ?? line.source.item_variant_id ?? undefined,
            uom_id: line.source.uom?.id ?? line.source.uom_id ?? undefined,
            ordered_uom_id: line.source.uom?.id ?? line.source.uom_id ?? undefined,
            purchase_order_line_id: line.source.id,
            ordered_quantity: line.source.ordered_quantity,
            received_quantity: decimal(line.received_quantity),
            accepted_quantity: decimal(line.accepted_quantity),
            rejected_quantity: decimal(line.rejected_quantity),
            unit_price: decimal(line.source.unit_price),
        })),
    });

    return (
        <form className="space-y-5" onSubmit={async (event) => {
            event.preventDefault();
            if (submitting) return;
            setSubmitting(true);
            setError(null);
            try {
                const saved = await createGoodsReceipt(payload());
                navigate(`/purchase/goods-receipts/${saved.id}`);
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setSubmitting(false);
            }
        }}>
            <ErrorAlert error={error} />
            <Panel title="Source">
                <div className="grid gap-4 md:grid-cols-3">
                    <PurchaseOrderLookupSelect value={purchaseOrder} onChange={setPurchaseOrder} error={errorFor('purchase_order_id')} />
                    <Input label="Received date" type="date" value={receivedDate} error={errorFor('received_date')} onChange={(event) => setReceivedDate(event.target.value)} />
                    <WarehouseLocationLookupSelect value={warehouseLocation} onChange={setWarehouseLocation} error={errorFor('warehouse_location_id')} />
                </div>
                {sourceOrder && (
                    <div className="mt-4 grid gap-3 rounded-lg bg-slate-50 p-4 text-sm text-slate-700 md:grid-cols-3">
                        <div><span className="font-medium">Supplier</span><br />{sourceOrder.supplier?.name ?? '-'}</div>
                        <div><span className="font-medium">Warehouse</span><br />{sourceOrder.warehouse?.name ?? '-'}</div>
                        <div><span className="font-medium">Status</span><br />{sourceOrder.status ?? '-'}</div>
                    </div>
                )}
            </Panel>
            <Panel title="Receivable lines">
                {loadingLines ? <div className="text-sm text-slate-500">Loading source lines...</div> : <GoodsReceiptLineEditor lines={lines} onChange={setLines} errorFor={errorFor} />}
            </Panel>
            <Panel title="Notes">
                <Textarea label="Notes" value={notes} error={errorFor('notes')} onChange={(event) => setNotes(event.target.value)} />
            </Panel>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate('/purchase/goods-receipts')}>Cancel</Button>
                <Button type="submit" loading={submitting}>Create GRN</Button>
            </div>
        </form>
    );
}
