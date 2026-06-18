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
import { createManualSupplierReturn, type PurchaseReturnPayload } from '../purchaseApi';
import { decimalOr, todayDate } from '../purchaseFormUtils';
import { ItemLookupSelect, SupplierLookupSelect, UomLookupSelect, WarehouseLocationLookupSelect, WarehouseLookupSelect } from './PurchaseLookups';

export function ManualSupplierReturnForm() {
    const navigate = useNavigate();
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [item, setItem] = useState<NamedResource | null>(null);
    const [uom, setUom] = useState<NamedResource | null>(null);
    const [returnDate, setReturnDate] = useState(todayDate());
    const [quantity, setQuantity] = useState('1.000000');
    const [costBasis, setCostBasis] = useState('0.000000');
    const [reason, setReason] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const errorFor = (field: string) => fieldError(error, field);

    const payload = (): PurchaseReturnPayload => ({
        return_date: returnDate,
        warehouse_id: warehouse?.id ?? 0,
        warehouse_location_id: warehouseLocation?.id,
        supplier_type: 'supplier',
        supplier_id: supplier?.id,
        reason,
        return_type: 'manual_supplier_return',
        cost_basis: decimalOr(costBasis),
        lines: [{
            source_line_type: 'manual_supplier_return',
            source_line_id: 0,
            item_id: item?.id,
            uom_id: uom?.id,
            returned_quantity: decimalOr(quantity),
            unit_price: decimalOr(costBasis),
            cost_basis: decimalOr(costBasis),
            reason,
        }],
    });

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
                    <ItemLookupSelect value={item} onChange={setItem} error={errorFor('lines.0.item_id')} />
                    <UomLookupSelect value={uom} onChange={setUom} error={errorFor('lines.0.uom_id')} />
                    <DecimalInput label="Quantity" value={quantity} error={errorFor('lines.0.returned_quantity')} onChange={(event) => setQuantity(event.target.value)} />
                    <DecimalInput label="Cost basis" value={costBasis} error={errorFor('cost_basis') ?? errorFor('lines.0.cost_basis')} onChange={(event) => setCostBasis(event.target.value)} />
                </div>
                <div className="mt-4">
                    <Textarea label="Reason" value={reason} error={errorFor('reason')} onChange={(event) => setReason(event.target.value)} />
                </div>
            </Panel>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate('/purchase/returns')}>Cancel</Button>
                <Button type="submit" loading={busy}>Save manual return</Button>
            </div>
        </form>
    );
}
