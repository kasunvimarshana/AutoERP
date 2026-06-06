import { FormEvent, useMemo, useState, type ReactNode } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { FormSection } from '../../../shared/components/erp/ErpUi';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import type { UomLookup } from '../../uom/types/uom.types';
import type { ItemInput } from '../types/item.types';

const emptyInput: ItemInput = {
    costPrice: '0',
    isServiceItem: false,
    isStockItem: true,
    itemCode: '',
    itemType: 'inventory',
    name: '',
    reorderLevel: '0',
    reorderQuantity: '0',
    salesPrice: '0',
    status: 'active',
    trackInventory: true,
};

export function ItemForm({
    initialValue = emptyInput,
    onCancel,
    onSubmit,
    submitLabel,
    uoms,
}: {
    initialValue?: ItemInput;
    onCancel: () => void;
    onSubmit: (input: ItemInput) => Promise<void>;
    submitLabel: string;
    uoms: UomLookup[];
}) {
    const [value, setValue] = useState<ItemInput>(initialValue);
    const [uomSearch, setUomSearch] = useState('');
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const filteredUoms = useMemo(() => {
        const search = uomSearch.trim().toLowerCase();
        if (!search) return uoms;
        return uoms.filter((entry) => `${entry.uomCode} ${entry.name} ${entry.symbol ?? ''}`.toLowerCase().includes(search));
    }, [uomSearch, uoms]);

    function update<K extends keyof ItemInput>(key: K, nextValue: ItemInput[K]) {
        setValue((current) => ({ ...current, [key]: nextValue }));
    }

    function validate() {
        const nextErrors: Record<string, string[]> = {};
        if (!value.itemCode.trim()) nextErrors.item_code = ['Item code is required.'];
        if (!value.name.trim()) nextErrors.name = ['Name is required.'];
        if (!value.baseUomId) nextErrors.base_uom_id = ['Base UOM is required.'];
        for (const [field, amount] of Object.entries({
            cost_price: value.costPrice,
            reorder_level: value.reorderLevel,
            reorder_quantity: value.reorderQuantity,
            sales_price: value.salesPrice,
        })) {
            if (!Number.isFinite(Number(amount)) || Number(amount) < 0) nextErrors[field] = ['Value must be zero or greater.'];
        }
        setErrors(nextErrors);

        return Object.keys(nextErrors).length === 0;
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setFormError('');
        if (!validate()) return;
        setSubmitting(true);
        try {
            await onSubmit(value);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save this item.');
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <form className="space-y-6" onSubmit={handleSubmit}>
            {formError ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}
            <Section title="Basic Info"><div className="grid gap-4 md:grid-cols-2">
                <Field error={errors.item_code?.[0]} label="Item code"><Input value={value.itemCode} onChange={(event) => update('itemCode', event.target.value)} placeholder="e.g. ITEM-100" /></Field>
                <Field error={errors.name?.[0]} label="Name"><Input value={value.name} onChange={(event) => update('name', event.target.value)} /></Field>
                <Field label="Display name"><Input value={value.displayName ?? ''} onChange={(event) => update('displayName', event.target.value)} /></Field>
                <Field label="Status"><select className="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={value.status} onChange={(event) => update('status', event.target.value as ItemInput['status'])}><option value="active">Active</option><option value="inactive">Inactive</option></select></Field>
                <Field label="SKU"><Input value={value.sku ?? ''} onChange={(event) => update('sku', event.target.value)} /></Field>
                <Field error={errors.barcode?.[0]} label="Barcode"><Input value={value.barcode ?? ''} onChange={(event) => update('barcode', event.target.value)} /></Field>
            </div></Section>

            <Section title="UOM & Classification"><div className="grid gap-4 md:grid-cols-2">
                <Field label="Item type"><select className="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={value.itemType ?? ''} onChange={(event) => update('itemType', event.target.value ? event.target.value as ItemInput['itemType'] : undefined)}><option value="">Not specified</option><option value="inventory">Inventory</option><option value="service">Service</option><option value="non_inventory">Non-inventory</option></select></Field>
                <Field label="Filter UOM choices"><Input placeholder="Search code, name, or symbol" value={uomSearch} onChange={(event) => setUomSearch(event.target.value)} /></Field>
                <UomField error={errors.base_uom_id?.[0]} label="Base UOM" options={filteredUoms} required value={value.baseUomId} onChange={(id) => update('baseUomId', id)} />
                <UomField error={errors.purchase_uom_id?.[0]} label="Purchase UOM" options={filteredUoms} value={value.purchaseUomId} onChange={(id) => update('purchaseUomId', id)} />
                <UomField error={errors.sales_uom_id?.[0]} label="Sales UOM" options={filteredUoms} value={value.salesUomId} onChange={(id) => update('salesUomId', id)} />
            </div></Section>

            <Section title="Pricing"><div className="grid gap-4 md:grid-cols-2">
                <Field error={errors.cost_price?.[0]} label="Cost price"><Input inputMode="decimal" value={value.costPrice} onChange={(event) => update('costPrice', event.target.value)} /></Field>
                <Field error={errors.sales_price?.[0]} label="Sales price"><Input inputMode="decimal" value={value.salesPrice} onChange={(event) => update('salesPrice', event.target.value)} /></Field>
            </div></Section>

            <Section title="Inventory Settings"><div className="grid gap-4 md:grid-cols-2">
                <Check checked={value.trackInventory} label="Track inventory" onChange={(checked) => update('trackInventory', checked)} />
                <Check checked={value.isStockItem} label="Stock item" onChange={(checked) => update('isStockItem', checked)} />
                <Check checked={value.isServiceItem} label="Service item" onChange={(checked) => update('isServiceItem', checked)} />
                <div />
                <Field error={errors.reorder_level?.[0]} label="Reorder level"><Input inputMode="decimal" value={value.reorderLevel} onChange={(event) => update('reorderLevel', event.target.value)} /></Field>
                <Field error={errors.reorder_quantity?.[0]} label="Reorder quantity"><Input inputMode="decimal" value={value.reorderQuantity} onChange={(event) => update('reorderQuantity', event.target.value)} /></Field>
            </div></Section>

            <Section title="Notes"><div className="grid gap-4">
                <Field label="Description"><textarea className="min-h-24 w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm outline-none focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-100" value={value.description ?? ''} onChange={(event) => update('description', event.target.value)} /></Field>
                <Field label="Internal notes"><textarea className="min-h-24 w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm outline-none focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-100" value={value.notes ?? ''} onChange={(event) => update('notes', event.target.value)} /></Field>
            </div></Section>

            <div className="flex justify-end gap-3"><Button disabled={submitting} onClick={onCancel} variant="secondary">Cancel</Button><Button disabled={submitting} type="submit" variant="blue">{submitting ? 'Saving' : submitLabel}</Button></div>
        </form>
    );
}

function Section({ children, title }: { children: ReactNode; title: string }) {
    return <FormSection title={title}>{children}</FormSection>;
}

function Field({ children, error, label }: { children: ReactNode; error?: string; label: string }) {
    return <label className="space-y-2 text-sm font-semibold text-slate-700"><span>{label}</span>{children}{error ? <span className="block text-xs font-medium text-red-600">{error}</span> : null}</label>;
}

function Check({ checked, label, onChange }: { checked: boolean; label: string; onChange: (checked: boolean) => void }) {
    return <label className="flex h-11 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-700"><input checked={checked} onChange={(event) => onChange(event.target.checked)} type="checkbox" />{label}</label>;
}

function UomField({ error, label, onChange, options, required = false, value }: { error?: string; label: string; onChange: (id?: number) => void; options: UomLookup[]; required?: boolean; value?: number }) {
    const visible = value && !options.some((entry) => entry.id === value) ? [{ id: value, name: 'Selected unit', uomCode: `#${value}` }, ...options] : options;
    return <Field error={error} label={label}><select className="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={value ?? ''} onChange={(event) => onChange(event.target.value ? Number(event.target.value) : undefined)}><option value="">{required ? 'Select a unit' : 'Use base UOM'}</option>{visible.map((entry) => <option key={entry.id} value={entry.id}>{entry.uomCode} - {entry.name}{entry.symbol ? ` (${entry.symbol})` : ''}</option>)}</select></Field>;
}
