import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { createManualSupplierReturn, type ManualPurchaseReturnPayload } from '../purchaseApi';
import { decimalOr, todayDate } from '../purchaseFormUtils';
import { ItemLookupSelect, SupplierLookupSelect, UomLookupSelect, WarehouseLocationLookupSelect, WarehouseLookupSelect } from './PurchaseLookups';

interface ManualReturnLineState {
    clientLineKey: string;
    item: NamedResource | null;
    uom: NamedResource | null;
    quantity: string;
    costBasis: string;
    reason: string;
}

function newLine(): ManualReturnLineState {
    return {
        clientLineKey: `manual-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`,
        item: null,
        uom: null,
        quantity: '1.000000',
        costBasis: '0.000000',
        reason: '',
    };
}

export function ManualSupplierReturnForm() {
    const navigate = useNavigate();
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [returnDate, setReturnDate] = useState(todayDate());
    const [lines, setLines] = useState<ManualReturnLineState[]>(() => [newLine()]);
    const [reason, setReason] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const errorFor = (field: string) => fieldError(error, field);

    const payload = (): ManualPurchaseReturnPayload => ({
        return_date: returnDate,
        warehouse_id: warehouse?.id ?? 0,
        warehouse_location_id: warehouseLocation?.id,
        supplier_id: supplier?.id,
        reason,
        return_type: 'manual_supplier_return',
        cost_basis: lines[0]?.costBasis ? decimalOr(lines[0].costBasis) : undefined,
        lines: lines.map((line) => ({
            client_line_key: line.clientLineKey,
            item_id: line.item?.id,
            uom_id: line.uom?.id,
            returned_quantity: decimalOr(line.quantity),
            unit_price: decimalOr(line.costBasis),
            cost_basis: decimalOr(line.costBasis),
            reason: line.reason || reason || undefined,
        })),
    });

    const updateLine = (clientLineKey: string, patch: Partial<ManualReturnLineState>) => {
        setLines((current) => current.map((line) => (
            line.clientLineKey === clientLineKey ? { ...line, ...patch } : line
        )));
    };

    const removeLine = (clientLineKey: string) => {
        setLines((current) => current.length <= 1
            ? current
            : current.filter((line) => line.clientLineKey !== clientLineKey));
    };

    return (
        <form className="space-y-5" onSubmit={async (event) => {
            event.preventDefault();
            if (busy) return;
            setBusy(true);
            setError(null);
            try {
                const saved = await createManualSupplierReturn(payload());
                navigate(`/purchase/returns/${saved.id}`);
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setBusy(false);
            }
        }}>
            <ErrorAlert error={error} />
            <Panel title="Manual supplier return">
                <div className="grid gap-4 md:grid-cols-3">
                    <SupplierLookupSelect value={supplier} onChange={setSupplier} error={errorFor('supplier_id')} />
                    <WarehouseLookupSelect value={warehouse} onChange={(value) => { setWarehouse(value); setWarehouseLocation(null); }} error={errorFor('warehouse_id')} />
                    <WarehouseLocationLookupSelect warehouseId={warehouse?.id ?? null} value={warehouseLocation} onChange={setWarehouseLocation} error={errorFor('warehouse_location_id')} />
                    <Input label="Return date" type="date" value={returnDate} error={errorFor('return_date')} onChange={(event) => setReturnDate(event.target.value)} />
                </div>
                <div className="mt-4">
                    <Textarea label="Reason" value={reason} error={errorFor('reason')} onChange={(event) => setReason(event.target.value)} />
                </div>
            </Panel>
            <Panel title="Return lines">
                <div className="space-y-4">
                    {lines.map((line, index) => (
                        <div key={line.clientLineKey} className="grid gap-4 rounded-lg border border-slate-200 p-4 md:grid-cols-5">
                            <ItemLookupSelect value={line.item} onChange={(value) => updateLine(line.clientLineKey, { item: value })} error={errorFor(`lines.${index}.item_id`)} />
                            <UomLookupSelect value={line.uom} onChange={(value) => updateLine(line.clientLineKey, { uom: value })} error={errorFor(`lines.${index}.uom_id`)} />
                            <DecimalInput label="Quantity" value={line.quantity} error={errorFor(`lines.${index}.returned_quantity`)} onChange={(event) => updateLine(line.clientLineKey, { quantity: event.target.value })} />
                            <DecimalInput label="Cost basis" value={line.costBasis} error={errorFor(`lines.${index}.cost_basis`)} onChange={(event) => updateLine(line.clientLineKey, { costBasis: event.target.value })} />
                            <div className="flex items-end">
                                <Button type="button" variant="ghost" disabled={lines.length <= 1 || busy} onClick={() => removeLine(line.clientLineKey)}>Remove</Button>
                            </div>
                            <div className="md:col-span-5">
                                <Textarea label="Line reason" value={line.reason} error={errorFor(`lines.${index}.reason`) ?? errorFor(`lines.${index}.client_line_key`)} onChange={(event) => updateLine(line.clientLineKey, { reason: event.target.value })} />
                            </div>
                        </div>
                    ))}
                    <Button type="button" variant="secondary" disabled={busy} onClick={() => setLines((current) => [...current, newLine()])}>Add line</Button>
                </div>
            </Panel>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate('/purchase/returns')}>Cancel</Button>
                <Button type="submit" loading={busy}>Save manual return</Button>
            </div>
        </form>
    );
}
