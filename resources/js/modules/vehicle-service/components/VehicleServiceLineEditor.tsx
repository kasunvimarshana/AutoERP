import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { ItemLookupSelect } from '@/modules/item/components/ItemLookupSelect';
import type { ItemSummary } from '@/modules/item/itemTypes';
import { useApi } from '@/shared/hooks/useApi';
import { createVehicleServiceLine, deleteVehicleServiceLine, listVehicleServiceLines } from '../vehicleServiceApi';
import type { VehicleServiceJobLine, VehicleServiceLinePayload, VehicleServiceLineSourceType } from '../vehicleServiceTypes';

const empty = () => ({
    source: 'inventory_item' as VehicleServiceLineSourceType,
    item: null as ItemSummary | null,
    description: '',
    quantity: '1.000000',
    unit_cost: '0.000000',
    unit_price: '0.000000',
    discount_type: 'fixed' as 'fixed' | 'percentage',
    discount_value: '0.000000',
    tax_type: 'fixed' as 'fixed' | 'percentage',
    tax_value: '0.000000',
    charge_type: 'fixed' as 'fixed' | 'percentage',
    charge_value: '0.000000',
    customer_supplied: false,
    billable: true,
});

export default function VehicleServiceLineEditor({ jobId }: { jobId: number }) {
    const result = useApi((signal) => listVehicleServiceLines(jobId, signal), [jobId]);
    const [form, setForm] = useState(empty);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const external = form.source === 'external_item';

    const columns: DataColumn<VehicleServiceJobLine>[] = [
        { key: 'line', header: '#', render: (line) => line.line_number },
        { key: 'type', header: 'Type', render: (line) => line.line_source_type.replaceAll('_', ' ') },
        { key: 'description', header: 'Description', render: (line) => <div><div>{line.description}</div>{line.children?.map((child) => <div key={child.id} className="ml-3 text-xs text-slate-500">{child.line_number}. {child.description} x {child.quantity}</div>)}</div> },
        { key: 'quantity', header: 'Qty', render: (line) => line.quantity },
        { key: 'price', header: 'Unit price', render: (line) => line.unit_price },
        { key: 'total', header: 'Total', render: (line) => line.line_total },
        { key: 'flags', header: 'Flags', render: (line) => [line.is_billable && 'Billable', line.is_customer_supplied && 'Customer supplied', line.is_inventory_tracked && 'Inventory', line.is_employee_assignable && 'Workforce'].filter(Boolean).join(', ') || '-' },
        { key: 'actions', header: '', render: (line) => <Button type="button" variant="danger" onClick={async () => { await deleteVehicleServiceLine(jobId, line.id); result.reload(); }}>Delete</Button> },
    ];

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? result.error} />
            <form className="rounded-xl border border-slate-200 bg-slate-50 p-4" onSubmit={async (event) => {
                event.preventDefault();
                setSaving(true);
                setError(null);
                const value = (type: 'fixed' | 'percentage', amount: string) => type === 'percentage'
                    ? { rate: amount, amount: '0.000000' }
                    : { rate: '0.000000', amount };
                const discount = value(form.discount_type, form.discount_value);
                const tax = value(form.tax_type, form.tax_value);
                const charge = value(form.charge_type, form.charge_value);
                const payload: VehicleServiceLinePayload = {
                    line_source_type: form.source,
                    item_id: external ? undefined : form.item?.id,
                    description: form.description || form.item?.name || '',
                    quantity: form.quantity,
                    unit_cost: form.unit_cost,
                    unit_price: form.unit_price,
                    discount_calculation_type: form.discount_type,
                    discount_rate: discount.rate,
                    discount_amount: discount.amount,
                    tax_calculation_type: form.tax_type,
                    tax_rate: tax.rate,
                    tax_amount: tax.amount,
                    charge_calculation_type: form.charge_type,
                    charge_rate: charge.rate,
                    charge_amount: charge.amount,
                    is_customer_supplied: external && form.customer_supplied,
                    is_billable: form.billable,
                    expand_combo: true,
                };
                try {
                    await createVehicleServiceLine(jobId, payload);
                    setForm(empty());
                    result.reload();
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSaving(false);
                }
            }}>
                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <Select label="Line type" value={form.source} options={[
                        { value: 'inventory_item', label: 'Inventory item' },
                        { value: 'external_item', label: 'External / customer supplied' },
                        { value: 'service_item', label: 'Service item' },
                        { value: 'labour_item', label: 'Labour item' },
                        { value: 'combo_parent', label: 'Combo / package' },
                    ]} onChange={(event) => setForm({ ...form, source: event.target.value as VehicleServiceLineSourceType, item: null, customer_supplied: false })} />
                    {!external && <ItemLookupSelect value={form.item} onChange={set => setForm({ ...form, item: set, description: set?.name ?? form.description })} />}
                    <Input label="Description" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} />
                    <Input label="Quantity" type="number" min="0.000001" step="0.000001" value={form.quantity} onChange={(event) => setForm({ ...form, quantity: event.target.value })} />
                    <Input label="Unit cost" type="number" min="0" step="0.000001" value={form.unit_cost} onChange={(event) => setForm({ ...form, unit_cost: event.target.value })} />
                    <Input label="Unit price" type="number" min="0" step="0.000001" value={form.unit_price} onChange={(event) => setForm({ ...form, unit_price: event.target.value })} />
                    <Select label="Discount type" value={form.discount_type} options={[{ value: 'fixed', label: 'Fixed' }, { value: 'percentage', label: 'Percentage' }]} onChange={(event) => setForm({ ...form, discount_type: event.target.value as 'fixed' | 'percentage' })} />
                    <Input label="Discount value" type="number" min="0" step="0.000001" value={form.discount_value} onChange={(event) => setForm({ ...form, discount_value: event.target.value })} />
                    <Select label="Tax type" value={form.tax_type} options={[{ value: 'fixed', label: 'Fixed' }, { value: 'percentage', label: 'Percentage' }]} onChange={(event) => setForm({ ...form, tax_type: event.target.value as 'fixed' | 'percentage' })} />
                    <Input label="Tax value" type="number" min="0" step="0.000001" value={form.tax_value} onChange={(event) => setForm({ ...form, tax_value: event.target.value })} />
                    <Select label="Charge type" value={form.charge_type} options={[{ value: 'fixed', label: 'Fixed' }, { value: 'percentage', label: 'Percentage' }]} onChange={(event) => setForm({ ...form, charge_type: event.target.value as 'fixed' | 'percentage' })} />
                    <Input label="Charge value" type="number" min="0" step="0.000001" value={form.charge_value} onChange={(event) => setForm({ ...form, charge_value: event.target.value })} />
                </div>
                <div className="mt-3 flex flex-wrap items-center gap-5 text-sm">
                    {external && <label><input type="checkbox" checked={form.customer_supplied} onChange={(event) => setForm({ ...form, customer_supplied: event.target.checked, billable: event.target.checked ? false : form.billable })} /> <span className="ml-2">Customer supplied</span></label>}
                    <label><input type="checkbox" checked={form.billable} onChange={(event) => setForm({ ...form, billable: event.target.checked })} /> <span className="ml-2">Billable</span></label>
                    <Button type="submit" loading={saving}>Add line</Button>
                </div>
            </form>
            {result.loading ? <LoadingState /> : <DataTable rows={result.data ?? []} columns={columns} rowKey={(line) => line.id} />}
        </div>
    );
}
