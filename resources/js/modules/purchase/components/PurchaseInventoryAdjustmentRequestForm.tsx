import { useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { createPurchaseInventoryAdjustmentRequest } from '../purchaseApi';
import { todayDate } from '../purchaseFormUtils';
import { ItemLookupSelect, WarehouseLocationLookupSelect, WarehouseLookupSelect } from './PurchaseLookups';

export function PurchaseInventoryAdjustmentRequestForm() {
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [item, setItem] = useState<NamedResource | null>(null);
    const [date, setDate] = useState(todayDate());
    const [adjustmentType, setAdjustmentType] = useState('decrease');
    const [systemQuantity, setSystemQuantity] = useState('0.000000');
    const [countedQuantity, setCountedQuantity] = useState('0.000000');
    const [adjustmentQuantity, setAdjustmentQuantity] = useState('0.000000');
    const [unitCost, setUnitCost] = useState('0.000000');
    const [reason, setReason] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const [created, setCreated] = useState<Record<string, unknown> | null>(null);
    const errorFor = (field: string) => fieldError(error, field);

    return (
        <form className="space-y-5" onSubmit={async (event) => {
            event.preventDefault();
            if (busy) return;
            setBusy(true);
            setError(null);
            setCreated(null);
            try {
                setCreated(await createPurchaseInventoryAdjustmentRequest({
                    adjustment_date: date,
                    adjustment_type: adjustmentType as 'decrease',
                    warehouse_id: warehouse?.id ?? 0,
                    warehouse_location_id: warehouseLocation?.id,
                    reason,
                    lines: [{
                        item_id: item?.id ?? 0,
                        system_quantity: systemQuantity || '0.000000',
                        counted_quantity: countedQuantity || '0.000000',
                        adjustment_quantity: adjustmentQuantity || '0.000000',
                        unit_cost: unitCost || '0.000000',
                        reason,
                    }],
                }));
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setBusy(false);
            }
        }}>
            <ErrorAlert error={error} />
            {created && <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Inventory adjustment request created.</div>}
            <Panel title="Inventory adjustment only">
                <div className="grid gap-4 md:grid-cols-3">
                    <WarehouseLookupSelect value={warehouse} onChange={(value) => { setWarehouse(value); setWarehouseLocation(null); }} error={errorFor('warehouse_id')} />
                    <WarehouseLocationLookupSelect warehouseId={warehouse?.id ?? null} value={warehouseLocation} onChange={setWarehouseLocation} error={errorFor('warehouse_location_id')} />
                    <ItemLookupSelect value={item} onChange={setItem} error={errorFor('lines.0.item_id')} />
                    <Input label="Adjustment date" type="date" value={date} error={errorFor('adjustment_date')} onChange={(event) => setDate(event.target.value)} />
                    <Select label="Type" value={adjustmentType} options={['increase', 'decrease', 'recount', 'damage', 'expiry', 'opening_balance'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))} error={errorFor('adjustment_type')} onChange={(event) => setAdjustmentType(event.target.value)} />
                    <DecimalInput label="Unit cost" value={unitCost} error={errorFor('lines.0.unit_cost')} onChange={(event) => setUnitCost(event.target.value)} />
                    <DecimalInput label="System qty" value={systemQuantity} error={errorFor('lines.0.system_quantity')} onChange={(event) => setSystemQuantity(event.target.value)} />
                    <DecimalInput label="Counted qty" value={countedQuantity} error={errorFor('lines.0.counted_quantity')} onChange={(event) => setCountedQuantity(event.target.value)} />
                    <DecimalInput label="Adjustment qty" value={adjustmentQuantity} error={errorFor('lines.0.adjustment_quantity')} onChange={(event) => setAdjustmentQuantity(event.target.value)} />
                </div>
                <div className="mt-4">
                    <Textarea label="Reason" value={reason} error={errorFor('reason')} onChange={(event) => setReason(event.target.value)} />
                </div>
            </Panel>
            <div className="flex justify-end"><Button type="submit" loading={busy}>Create adjustment request</Button></div>
        </form>
    );
}
