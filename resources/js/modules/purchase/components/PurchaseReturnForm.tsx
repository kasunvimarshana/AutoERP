import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import { createPurchaseReturn, getGoodsReceipt, getReturnableGoodsReceiptLines, type PurchaseReturnPayload, type ReturnableLine } from '../purchaseApi';
import { decimalOr, todayDate } from '../purchaseFormUtils';
import { GoodsReceiptLookupSelect, WarehouseLocationLookupSelect } from './PurchaseLookups';
import { PurchaseReturnLineEditor, type EditableReturnLine } from './PurchaseReturnLineEditor';

export function PurchaseReturnForm({ sourceGoodsReceiptId }: { sourceGoodsReceiptId?: number }) {
    const navigate = useNavigate();
    const [source, setSource] = useState<NamedResource | null>(null);
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [returnDate, setReturnDate] = useState(todayDate());
    const [reason, setReason] = useState('');
    const [lines, setLines] = useState<EditableReturnLine[]>([]);
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const errorFor = (field: string) => fieldError(error, field);
    const hasEnteredLines = lines.some((line) => isPositiveDecimal(line.returned_quantity));

    useEffect(() => {
        if (sourceGoodsReceiptId && !source) {
            setSource({ id: sourceGoodsReceiptId, name: `Goods Receipt #${sourceGoodsReceiptId}` });
        }
    }, [sourceGoodsReceiptId, source]);

    useEffect(() => {
        if (!source?.id) {
            setLines([]);
            return;
        }
        const controller = new AbortController();
        setBusy(true);
        setError(null);
        Promise.all([getGoodsReceipt(source.id, controller.signal), getReturnableGoodsReceiptLines(source.id, controller.signal)])
            .then(([grn, rows]) => {
                if (controller.signal.aborted) return;
                setSource({ id: grn.id, code: grn.grn_number, name: grn.grn_number ?? `Goods Receipt #${grn.id}` });
                setSupplier(grn.supplier ?? null);
                setWarehouse(grn.warehouse ?? null);
                setWarehouseLocation(grn.warehouse_location ?? null);
                setLines(rows.map((row: ReturnableLine) => ({ source: row, include: false, returned_quantity: '0.000000', reason: '' })));
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setBusy(false);
            });
        return () => controller.abort();
    }, [source?.id]);

    const payload = (): PurchaseReturnPayload => ({
        return_date: returnDate,
        warehouse_id: warehouse?.id ?? 0,
        warehouse_location_id: warehouseLocation?.id,
        reason: reason || undefined,
        return_type: 'referenced',
        source_id: source?.id,
        lines: lines.filter((line) => line.include && isPositiveDecimal(line.returned_quantity)).map((line) => ({
            source_line_type: line.source.source_line_type,
            source_line_id: line.source.source_line_id,
            returned_quantity: decimalOr(line.returned_quantity),
            reason: line.reason || undefined,
        })),
    });
    const changeSource = (next: NamedResource | null) => {
        if (source?.id && next?.id !== source.id && hasEnteredLines && !window.confirm('Changing the goods receipt clears entered return quantities.')) {
            return;
        }
        setSource(next);
    };

    return (
        <form className="space-y-5" onSubmit={async (event) => {
            event.preventDefault();
            if (busy) return;
            setBusy(true);
            setError(null);
            try {
                const saved = await createPurchaseReturn(payload());
                navigate(`/purchase/returns/${saved.id}`);
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setBusy(false);
            }
        }}>
            <ErrorAlert error={error} />
            <Panel title="Referenced source">
                <div className="grid gap-4 md:grid-cols-4">
                    <GoodsReceiptLookupSelect value={source} onChange={changeSource} error={errorFor('source_id')} />
                    <Input label="Return date" type="date" value={returnDate} error={errorFor('return_date')} onChange={(event) => setReturnDate(event.target.value)} />
                    <WarehouseLocationLookupSelect warehouseId={warehouse?.id ?? null} value={warehouseLocation} onChange={setWarehouseLocation} error={errorFor('warehouse_location_id')} />
                    <div className="rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span className="font-medium">Supplier</span><br />{supplier?.name ?? '-'}</div>
                </div>
            </Panel>
            <Panel title="Return lines">
                <PurchaseReturnLineEditor lines={lines} onChange={setLines} errorFor={errorFor} />
            </Panel>
            <Panel title="Reason">
                <Textarea label="Reason" value={reason} error={errorFor('reason')} onChange={(event) => setReason(event.target.value)} />
            </Panel>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate('/purchase/returns')}>Cancel</Button>
                <Button type="submit" loading={busy}>Save Draft</Button>
            </div>
        </form>
    );
}
