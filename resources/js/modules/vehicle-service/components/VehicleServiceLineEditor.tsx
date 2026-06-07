import { useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';
import type { NamedResource } from '@/shared/types/common';
import { addDecimal, multiplyDecimal, nonNegativeDecimal, percentageOfDecimal, subtractDecimal } from '@/shared/utils/decimal';
import { useApi } from '@/shared/hooks/useApi';
import { createVehicleServiceLine, deleteVehicleServiceLine, listVehicleServiceLines, updateVehicleServiceLine } from '../vehicleServiceApi';
import type { VehicleServiceJobLine, VehicleServiceLinePayload, VehicleServiceLineSourceType } from '../vehicleServiceTypes';

interface VehicleServiceLineFormValue {
    source: VehicleServiceLineSourceType;
    item: NamedResource | null;
    description: string;
    quantity: string;
    unit_cost: string;
    unit_price: string;
    discount_type: 'fixed' | 'percentage';
    discount_value: string;
    tax_type: 'fixed' | 'percentage';
    tax_value: string;
    charge_type: 'fixed' | 'percentage';
    charge_value: string;
    customer_supplied: boolean;
    billable: boolean;
}

type LineDialog =
    | { mode: 'create'; value: VehicleServiceLineFormValue }
    | { mode: 'edit'; lineId: number; value: VehicleServiceLineFormValue };

const calculationOptions = [{ value: 'fixed', label: 'Fixed' }, { value: 'percentage', label: 'Percentage' }];
const lineTypeOptions = [
    { value: 'inventory_item', label: 'Inventory item' },
    { value: 'external_item', label: 'External / customer supplied' },
    { value: 'service_item', label: 'Service item' },
    { value: 'labour_item', label: 'Labour item' },
    { value: 'combo_parent', label: 'Combo / package' },
    { value: 'combo_child', label: 'Combo child' },
];

export default function VehicleServiceLineEditor({ jobId }: { jobId: number }) {
    const result = useApi((signal) => listVehicleServiceLines(jobId, signal), [jobId]);
    const [dialog, setDialog] = useState<LineDialog | null>(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const saveLine = async (value: VehicleServiceLineFormValue) => {
        if (!dialog) return;
        setSaving(true);
        setError(null);
        try {
            const payload = lineFormToPayload(value);
            if (dialog.mode === 'edit') {
                await updateVehicleServiceLine(jobId, dialog.lineId, payload);
            } else {
                await createVehicleServiceLine(jobId, payload);
            }
            setDialog(null);
            result.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };
    const removeLine = async (line: VehicleServiceJobLine) => {
        if (!window.confirm('Remove this line?')) return;
        setError(null);
        try {
            await deleteVehicleServiceLine(jobId, line.id);
            result.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        }
    };

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? result.error} />
            <VehicleServiceLineTable
                lines={result.data ?? []}
                loading={result.loading}
                onAdd={() => setDialog({ mode: 'create', value: emptyLineForm() })}
                onEdit={(line) => setDialog({ mode: 'edit', lineId: line.id, value: lineToForm(line) })}
                onRemove={(line) => void removeLine(line)}
            />
            <Modal open={Boolean(dialog)} title={dialog?.mode === 'edit' ? 'Edit line' : 'Add line'} onClose={() => !saving && setDialog(null)}>
                {dialog && (
                    <VehicleServiceLineForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.lineId}` : 'create'}
                        value={dialog.value}
                        mode={dialog.mode}
                        error={error}
                        saving={saving}
                        onCancel={() => setDialog(null)}
                        onSave={(value) => void saveLine(value)}
                    />
                )}
            </Modal>
        </div>
    );
}

function VehicleServiceLineTable({ lines, loading, onAdd, onEdit, onRemove }: {
    lines: VehicleServiceJobLine[];
    loading: boolean;
    onAdd: () => void;
    onEdit: (line: VehicleServiceJobLine) => void;
    onRemove: (line: VehicleServiceJobLine) => void;
}) {
    const columns: DataColumn<VehicleServiceJobLine>[] = [
        { key: 'item', header: 'Item', render: formatLineItem },
        { key: 'quantity', header: 'Qty', render: (line) => line.quantity, className: 'tabular-nums' },
        { key: 'uom', header: 'UOM', render: (line) => line.uom?.code ?? '-' },
        { key: 'price', header: 'Unit price', render: (line) => line.unit_price, className: 'tabular-nums' },
        { key: 'discount', header: 'Discount', render: formatDiscountSummary },
        { key: 'tax', header: 'Tax', render: formatTaxSummary },
        { key: 'total', header: 'Total', render: (line) => line.line_total, className: 'tabular-nums font-semibold' },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (line) => <LineActions onEdit={() => onEdit(line)} onRemove={() => onRemove(line)} /> },
    ];

    return (
        <div className="space-y-3">
            <div className="flex justify-end"><Button type="button" onClick={onAdd}>Add line</Button></div>
            {loading ? <LoadingState /> : <DataTable rows={lines} columns={columns} rowKey={(line) => line.id} emptyMessage="No lines added yet. Click Add line to start." />}
        </div>
    );
}

function VehicleServiceLineForm({ value, mode, error, saving, onSave, onCancel }: {
    value: VehicleServiceLineFormValue;
    mode: 'create' | 'edit';
    error: ApiError | null;
    saving: boolean;
    onSave: (value: VehicleServiceLineFormValue) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(value);
    const external = draft.source === 'external_item';
    const set = <K extends keyof VehicleServiceLineFormValue>(key: K, next: VehicleServiceLineFormValue[K]) => setDraft((current) => ({ ...current, [key]: next }));
    const preview = calculateLinePreview(draft);

    return (
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); onSave(draft); }}>
            <ErrorAlert error={error} />
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">Choose the line type, item or description, quantity, and price.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Select label="Line type" value={draft.source} options={lineTypeOptions} error={fieldError(error, 'line_source_type')} onChange={(event) => setDraft({ ...draft, source: event.target.value as VehicleServiceLineSourceType, item: null, customer_supplied: false })} />
                    {!external && <LookupSelect label="Item" value={draft.item} onChange={(item) => setDraft({ ...draft, item, description: item?.name ?? draft.description })} search={lookupApi.items} />}
                    <Input label="Quantity" type="number" min="0.000001" step="0.000001" value={draft.quantity} error={fieldError(error, 'quantity')} onChange={(event) => set('quantity', event.target.value)} />
                    <Input label="Unit price" type="number" min="0" step="0.000001" value={draft.unit_price} error={fieldError(error, 'unit_price')} onChange={(event) => set('unit_price', event.target.value)} />
                    <Input label="Description" value={draft.description} error={fieldError(error, 'description')} onChange={(event) => set('description', event.target.value)} />
                </div>
            </section>

            <details className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <summary className="cursor-pointer font-semibold text-slate-800">Advanced pricing</summary>
                <p className="mt-1 text-sm text-slate-500">Advanced pricing is optional.</p>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <Input label="Unit cost" type="number" min="0" step="0.000001" value={draft.unit_cost} error={fieldError(error, 'unit_cost')} onChange={(event) => set('unit_cost', event.target.value)} />
                    <Select label="Discount type" value={draft.discount_type} options={calculationOptions} error={fieldError(error, 'discount_calculation_type')} onChange={(event) => set('discount_type', event.target.value as 'fixed' | 'percentage')} />
                    <Input label="Discount value" type="number" min="0" step="0.000001" value={draft.discount_value} onChange={(event) => set('discount_value', event.target.value)} />
                    <Select label="Tax type" value={draft.tax_type} options={calculationOptions} error={fieldError(error, 'tax_calculation_type')} onChange={(event) => set('tax_type', event.target.value as 'fixed' | 'percentage')} />
                    <Input label="Tax value" type="number" min="0" step="0.000001" value={draft.tax_value} onChange={(event) => set('tax_value', event.target.value)} />
                    <Select label="Charge type" value={draft.charge_type} options={calculationOptions} error={fieldError(error, 'charge_calculation_type')} onChange={(event) => set('charge_type', event.target.value as 'fixed' | 'percentage')} />
                    <Input label="Charge value" type="number" min="0" step="0.000001" value={draft.charge_value} onChange={(event) => set('charge_value', event.target.value)} />
                </div>
                <div className="mt-4 flex flex-wrap gap-5 text-sm">
                    {external && <label><input type="checkbox" checked={draft.customer_supplied} onChange={(event) => setDraft({ ...draft, customer_supplied: event.target.checked, billable: event.target.checked ? false : draft.billable })} /> <span className="ml-2">Customer supplied</span></label>}
                    <label><input type="checkbox" checked={draft.billable} onChange={(event) => set('billable', event.target.checked)} /> <span className="ml-2">Billable</span></label>
                </div>
            </details>

            <div className="rounded-lg border border-slate-200 p-4 text-sm">
                <h3 className="font-semibold text-slate-900">Line Preview</h3>
                <div className="mt-3 grid gap-2 sm:grid-cols-5">
                    <Preview label="Subtotal" value={preview.subtotal} />
                    <Preview label="Discount" value={preview.discount} />
                    <Preview label="Tax" value={preview.tax} />
                    <Preview label="Charge" value={preview.charge} />
                    <Preview label="Total" value={preview.total} />
                </div>
            </div>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={saving}>{mode === 'edit' ? 'Save line' : 'Add line'}</Button>
            </div>
        </form>
    );
}

function emptyLineForm(): VehicleServiceLineFormValue {
    return {
        source: 'inventory_item',
        item: null,
        description: '',
        quantity: '1.000000',
        unit_cost: '0.000000',
        unit_price: '0.000000',
        discount_type: 'fixed',
        discount_value: '0.000000',
        tax_type: 'fixed',
        tax_value: '0.000000',
        charge_type: 'fixed',
        charge_value: '0.000000',
        customer_supplied: false,
        billable: true,
    };
}

function lineToForm(line: VehicleServiceJobLine): VehicleServiceLineFormValue {
    return {
        source: line.line_source_type,
        item: line.item ?? null,
        description: line.description,
        quantity: line.quantity,
        unit_cost: line.unit_cost,
        unit_price: line.unit_price,
        discount_type: line.discount_calculation_type ?? 'fixed',
        discount_value: line.discount_calculation_type === 'percentage' ? line.discount_rate : line.discount_amount,
        tax_type: line.tax_calculation_type ?? 'fixed',
        tax_value: line.tax_calculation_type === 'percentage' ? line.tax_rate : line.tax_amount,
        charge_type: line.charge_calculation_type ?? 'fixed',
        charge_value: line.charge_calculation_type === 'percentage' ? line.charge_rate : line.charge_amount,
        customer_supplied: line.is_customer_supplied,
        billable: line.is_billable,
    };
}

function lineFormToPayload(form: VehicleServiceLineFormValue): VehicleServiceLinePayload {
    const discount = valueByType(form.discount_type, form.discount_value);
    const tax = valueByType(form.tax_type, form.tax_value);
    const charge = valueByType(form.charge_type, form.charge_value);
    const external = form.source === 'external_item';

    return {
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
}

function valueByType(type: 'fixed' | 'percentage', value: string): { rate: string; amount: string } {
    return type === 'percentage' ? { rate: value, amount: '0.000000' } : { rate: '0.000000', amount: value };
}

function calculateLinePreview(line: VehicleServiceLineFormValue) {
    const subtotal = multiplyDecimal(line.quantity, line.unit_price);
    const discount = line.discount_type === 'percentage' ? percentageOfDecimal(subtotal, line.discount_value) : line.discount_value;
    const taxBase = nonNegativeDecimal(subtractDecimal(subtotal, discount));
    const tax = line.tax_type === 'percentage' ? percentageOfDecimal(taxBase, line.tax_value) : line.tax_value;
    const charge = line.charge_type === 'percentage' ? percentageOfDecimal(subtotal, line.charge_value) : line.charge_value;
    return {
        subtotal,
        discount,
        tax,
        charge,
        total: nonNegativeDecimal(addDecimal(addDecimal(subtractDecimal(subtotal, discount), tax), charge)),
    };
}

function formatLineItem(line: VehicleServiceJobLine): string {
    return line.item ? [line.item.code, line.item.name].filter(Boolean).join(' - ') : line.description || line.line_source_type.replaceAll('_', ' ');
}

function formatDiscountSummary(line: VehicleServiceJobLine): string {
    return line.discount_calculation_type === 'percentage' ? `${line.discount_rate}%` : line.discount_amount;
}

function formatTaxSummary(line: VehicleServiceJobLine): string {
    return line.tax_calculation_type === 'percentage' ? `${line.tax_rate}%` : line.tax_amount;
}

function LineActions({ onEdit, onRemove }: { onEdit: () => void; onRemove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={onEdit}>Edit line</button><button type="button" className="font-semibold text-rose-600" onClick={onRemove}>Remove line</button></div>;
}

function Preview({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block tabular-nums text-slate-900">{value}</strong></div>;
}
