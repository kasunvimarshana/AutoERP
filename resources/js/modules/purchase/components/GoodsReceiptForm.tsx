import { useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import { createGoodsReceipt, getPurchaseOrder, getReceivablePurchaseOrderLines, type GoodsReceiptPayload, type PurchaseOrder, type PurchaseOrderLine } from '../purchaseApi';
import { decimalOr, todayDate } from '../purchaseFormUtils';
import { normalizeSourceId, sourceKey } from '../sourceIdentity';
import { useInitialSourceParam, type InitialSourceCommand, type InitialSourceParamDefinition } from '../hooks/useInitialSourceParam';
import { PurchaseOrderLookupSelect, WarehouseLocationLookupSelect } from './PurchaseLookups';
import { batchAllocationIssue, GoodsReceiptLineEditor, type EditableGoodsReceiptLine } from './GoodsReceiptLineEditor';

const initialSourceParams: Array<InitialSourceParamDefinition<'purchase_order'>> = [
    { sourceType: 'purchase_order', paramNames: ['purchase_order_id', 'source_id'] },
];

function orderLabel(order: PurchaseOrder): NamedResource {
    return {
        id: order.id,
        code: order.purchase_order_number,
        name: `${order.purchase_order_number ?? 'Purchase order'}${order.supplier?.name ? ` - ${order.supplier.name}` : ''}`,
    };
}

function editableLine(line: PurchaseOrderLine): EditableGoodsReceiptLine | null {
    if (normalizeSourceId(line.id) === null) return null;

    return { source: line, include: false, received_quantity: '0.000000', accepted_quantity: '0.000000', rejected_quantity: '0.000000', batch_allocations: [] };
}

export function GoodsReceiptForm() {
    const { confirm, confirmDialog } = useConfirmDialog();
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    const [purchaseOrder, setPurchaseOrderState] = useState<NamedResource | null>(null);
    const [sourceOrder, setSourceOrder] = useState<PurchaseOrder | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [receivedDate, setReceivedDate] = useState(todayDate());
    const [notes, setNotes] = useState('');
    const [lines, setLines] = useState<EditableGoodsReceiptLine[]>([]);
    const [loadingLines, setLoadingLines] = useState(false);
    const [loadingKey, setLoadingKey] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const selectedKeyRef = useRef<string | null>(null);
    const loadingKeyRef = useRef<string | null>(null);
    const requestRef = useRef<{ key: string; controller: AbortController; generation: number } | null>(null);
    const generationRef = useRef(0);
    const mountedRef = useRef(true);
    const unmountCancelTimerRef = useRef<number | null>(null);
    const errorFor = (field: string) => fieldError(error, field);
    const hasEnteredLines = lines.some((line) => (
        line.include ||
        isPositiveDecimal(line.received_quantity)
        || isPositiveDecimal(line.accepted_quantity)
        || isPositiveDecimal(line.rejected_quantity)
    ));
    const incompleteBatchLines = lines.filter((line) => (
        line.include
        && isPositiveDecimal(line.received_quantity)
        && batchAllocationIssue(line) !== null
    ));

    const setPurchaseOrder = useCallback((next: NamedResource | null) => {
        selectedKeyRef.current = next?.id ? sourceKey('purchase_order', next.id) : null;
        setPurchaseOrderState(next);
    }, []);

    const cancelSourceRequest = useCallback((resetLoading = true) => {
        generationRef.current += 1;
        requestRef.current?.controller.abort();
        requestRef.current = null;
        loadingKeyRef.current = null;
        if (resetLoading && mountedRef.current) {
            setLoadingKey(null);
            setLoadingLines(false);
        }
    }, []);

    const clearSource = useCallback(() => {
        cancelSourceRequest();
        setPurchaseOrder(null);
        setSourceOrder(null);
        setWarehouseLocation(null);
        setLines([]);
    }, [cancelSourceRequest, setPurchaseOrder]);

    const loadPurchaseOrderSource = useCallback(async (rawSourceId: number, _fallbackLabel: string): Promise<boolean> => {
        const sourceId = normalizeSourceId(rawSourceId);
        const key = sourceKey('purchase_order', sourceId);
        if (sourceId === null || !key || loadingKeyRef.current === key) return false;

        cancelSourceRequest();

        const controller = new AbortController();
        const generation = generationRef.current;
        requestRef.current = { key, controller, generation };
        loadingKeyRef.current = key;
        setLoadingKey(key);
        setLoadingLines(true);
        setError(null);
        setSourceOrder(null);
        setLines([]);

        try {
            const [order, receivableLines] = await Promise.all([
                getPurchaseOrder(sourceId, controller.signal),
                getReceivablePurchaseOrderLines(sourceId, controller.signal),
            ]);
            if (!mountedRef.current || isStaleSourceRequest(requestRef.current, key, controller, generation, generationRef.current)) return false;

            setSourceOrder(order);
            setPurchaseOrder(orderLabel(order));
            setWarehouseLocation(order.warehouse_location ?? null);
            setLines(dedupeReceiptLines(receivableLines.map(editableLine).filter(isReceiptLine)));

            return true;
        } catch (requestError) {
            if (!mountedRef.current || isStaleSourceRequest(requestRef.current, key, controller, generation, generationRef.current)) return false;
            setError(toApiError(requestError));
            setPurchaseOrder(null);
            setSourceOrder(null);
            setWarehouseLocation(null);
            setLines([]);
            return false;
        } finally {
            if (requestRef.current?.controller === controller) {
                requestRef.current = null;
                loadingKeyRef.current = null;
                if (mountedRef.current) {
                    setLoadingKey(null);
                    setLoadingLines(false);
                }
            }
        }
    }, [cancelSourceRequest, setPurchaseOrder]);

    const processInitialSource = useCallback(async (command: InitialSourceCommand<'purchase_order'>) => {
        await loadPurchaseOrderSource(command.sourceId, 'Selected purchase order');
    }, [loadPurchaseOrderSource]);

    useInitialSourceParam({
        searchParams,
        setSearchParams,
        definitions: initialSourceParams,
        isUnavailable: (key) => selectedKeyRef.current === key || loadingKeyRef.current === key,
        onProcess: processInitialSource,
    });

    useEffect(() => {
        mountedRef.current = true;
        if (unmountCancelTimerRef.current !== null) {
            window.clearTimeout(unmountCancelTimerRef.current);
            unmountCancelTimerRef.current = null;
        }

        return () => {
            mountedRef.current = false;
            unmountCancelTimerRef.current = window.setTimeout(() => cancelSourceRequest(false), 0);
        };
    }, [cancelSourceRequest]);

    const payload = (): GoodsReceiptPayload => ({
        received_date: receivedDate,
        purchase_order_id: sourceOrder?.id,
        warehouse_id: sourceOrder?.warehouse?.id ?? sourceOrder?.warehouse_id ?? 0,
        warehouse_location_id: warehouseLocation?.id,
        supplier_type: 'supplier',
        supplier_id: sourceOrder?.supplier?.id ?? sourceOrder?.supplier_id ?? undefined,
        notes: notes || undefined,
        lines: lines.filter((line) => line.include && isPositiveDecimal(line.received_quantity)).map((line) => ({
            item_id: line.source.item?.id ?? line.source.item_id ?? 0,
            item_variant_id: line.source.item_variant?.id ?? line.source.item_variant_id ?? undefined,
            uom_id: line.source.uom?.id ?? line.source.uom_id ?? undefined,
            ordered_uom_id: line.source.uom?.id ?? line.source.uom_id ?? undefined,
            purchase_order_line_id: line.source.id,
            ordered_quantity: line.source.ordered_quantity,
            received_quantity: decimalOr(line.received_quantity),
            accepted_quantity: decimalOr(line.accepted_quantity),
            rejected_quantity: decimalOr(line.rejected_quantity),
            unit_price: decimalOr(line.source.unit_price),
            batch_allocations: line.batch_allocations.map((allocation) => ({
                batch_id: allocation.batch?.id,
                batch_number: allocation.batch ? undefined : allocation.batch_number.trim() || undefined,
                lot_number: allocation.batch ? undefined : allocation.lot_number.trim() || undefined,
                manufacture_date: allocation.batch ? undefined : allocation.manufacture_date || undefined,
                expiry_date: allocation.batch ? undefined : allocation.expiry_date || undefined,
                quantity: decimalOr(allocation.quantity),
            })),
        })),
    });
    const changePurchaseOrder = async (next: NamedResource | null) => {
        if (purchaseOrder?.id && next?.id !== purchaseOrder.id && hasEnteredLines && !await confirm({
            title: 'Change purchase order?',
            message: 'Changing the purchase order clears all entered receipt quantities.',
            confirmLabel: 'Change purchase order',
        })) return;

        if (!next?.id) {
            clearSource();
            return;
        }

        setPurchaseOrder(next);
        setSourceOrder(null);
        setWarehouseLocation(null);
        setLines([]);
        void loadPurchaseOrderSource(next.id, next.name);
    };

    const excludedPurchaseOrderIds = [
        ...(purchaseOrder?.id ? [purchaseOrder.id] : []),
        ...(loadingKey ? loadingKeyId(loadingKey, 'purchase_order') : []),
    ];

    return (
        <form className="space-y-5" onSubmit={async (event) => {
            event.preventDefault();
            if (submitting || loadingLines) return;
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
                    <PurchaseOrderLookupSelect eligibility="receivable" value={purchaseOrder} onChange={(value) => void changePurchaseOrder(value)} excludeIds={excludedPurchaseOrderIds} error={errorFor('purchase_order_id')} />
                    <Input label="Received date" type="date" value={receivedDate} error={errorFor('received_date')} onChange={(event) => setReceivedDate(event.target.value)} />
                    <WarehouseLocationLookupSelect warehouseId={sourceOrder?.warehouse?.id ?? sourceOrder?.warehouse_id ?? null} value={warehouseLocation} onChange={setWarehouseLocation} error={errorFor('warehouse_location_id')} />
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
                {incompleteBatchLines.length > 0 && (
                    <div className="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        {incompleteBatchLines.length} batch/lot tracked line{incompleteBatchLines.length === 1 ? '' : 's'} need a complete allocation. Open <strong>Edit line</strong> and allocate the full accepted quantity before creating the GRN.
                    </div>
                )}
                {loadingLines ? <div className="text-sm text-slate-500">Loading source lines...</div> : <GoodsReceiptLineEditor lines={lines} currencyCode={sourceOrder?.currency?.code ?? undefined} onChange={setLines} errorFor={errorFor} />}
            </Panel>
            <Panel title="Notes">
                <Textarea label="Notes" value={notes} error={errorFor('notes')} onChange={(event) => setNotes(event.target.value)} />
            </Panel>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate('/purchase/goods-receipts')}>Cancel</Button>
                <Button type="submit" loading={submitting} disabled={loadingLines || incompleteBatchLines.length > 0}>Create GRN</Button>
            </div>
            {confirmDialog}
        </form>
    );
}

function dedupeReceiptLines(lines: EditableGoodsReceiptLine[]): EditableGoodsReceiptLine[] {
    const seen = new Set<number>();

    return lines.filter((line) => {
        const lineId = normalizeSourceId(line.source.id);
        if (lineId === null || seen.has(lineId)) return false;
        seen.add(lineId);
        return true;
    });
}

function isReceiptLine(line: EditableGoodsReceiptLine | null): line is EditableGoodsReceiptLine {
    return line !== null;
}

function loadingKeyId(key: string, type: string): number[] {
    const [sourceType, rawId] = key.split(':');
    const id = normalizeSourceId(rawId);
    return sourceType === type && id !== null ? [id] : [];
}

function isStaleSourceRequest(
    current: { key: string; controller: AbortController; generation: number } | null,
    key: string,
    controller: AbortController,
    generation: number,
    currentGeneration: number,
): boolean {
    return controller.signal.aborted
        || current?.key !== key
        || current.controller !== controller
        || current.generation !== generation
        || currentGeneration !== generation;
}
