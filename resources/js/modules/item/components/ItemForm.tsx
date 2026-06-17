import type { ApiError } from '@/shared/api/apiError';
import { fieldError } from '@/shared/api/apiError';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { getTaxLookups } from '@/modules/tax/taxApi';
import { Link } from 'react-router-dom';
import { costingMethods, itemTypes, trackingTypes, type ItemPayload } from '../itemTypes';
import { ItemBrandSelect } from './ItemBrandSelect';
import { ItemCategorySelect } from './ItemCategorySelect';
import { ItemUomSelect } from './ItemUomSelect';

export function ItemForm({
    value,
    onChange,
    category,
    onCategoryChange,
    brand,
    onBrandChange,
    baseUom,
    onBaseUomChange,
    error,
    baseUomLocked = false,
    baseUomChangeHref,
}: {
    value: ItemPayload;
    onChange: (value: ItemPayload) => void;
    category: NamedResource | null;
    onCategoryChange: (value: NamedResource | null) => void;
    brand: NamedResource | null;
    onBrandChange: (value: NamedResource | null) => void;
    baseUom: NamedResource | null;
    onBaseUomChange: (value: NamedResource | null) => void;
    error: ApiError | null;
    baseUomLocked?: boolean;
    baseUomChangeHref?: string;
}) {
    const taxLookups = useApi((signal) => getTaxLookups(signal), []);
    const set = <K extends keyof ItemPayload>(key: K, next: ItemPayload[K]) => onChange({ ...value, [key]: next });
    const options = (entries: readonly string[]) => entries.map((entry) => ({ value: entry, label: entry.replaceAll('_', ' ') }));
    const taxGroupOptions = (taxLookups.data?.groups ?? []).map((group) => ({
        value: group.id,
        label: `${group.code ?? ''} ${group.name ?? ''}`.trim(),
    }));
    const comboType = ['combo', 'package'].includes(value.item_type);
    const nonInventoryType = ['service', 'labour', 'non_stock', 'combo', 'package'].includes(value.item_type);

    return (
        <Panel title="Item identity">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Input label="Code" value={value.code} onChange={(event) => set('code', event.target.value)} error={fieldError(error, 'code') ?? fieldError(error, 'item.code')} required />
                <Input label="Name" value={value.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name') ?? fieldError(error, 'item.name')} required />
                <Select label="Item type" value={value.item_type} onChange={(event) => {
                    const itemType = event.target.value;
                    const nextIsCombo = ['combo', 'package'].includes(itemType);
                    const nextNonInventory = ['service', 'labour', 'non_stock', 'combo', 'package'].includes(itemType);
                    onChange({
                        ...value,
                        item_type: itemType,
                        is_combo: nextIsCombo,
                        is_stockable: nextNonInventory ? false : true,
                        tracking_type: nextNonInventory ? 'none' : value.tracking_type,
                        costing_method: nextNonInventory ? 'none' : value.costing_method,
                    });
                }} options={options(itemTypes)} error={fieldError(error, 'item_type') ?? fieldError(error, 'item.item_type')} />
                <Select label="Tracking type" value={value.tracking_type} onChange={(event) => set('tracking_type', event.target.value)} options={options(trackingTypes)} error={fieldError(error, 'tracking_type') ?? fieldError(error, 'item.tracking_type')} />
                <Select label="Costing method" value={value.costing_method} onChange={(event) => set('costing_method', event.target.value)} options={options(costingMethods)} error={fieldError(error, 'costing_method') ?? fieldError(error, 'item.costing_method')} />
                <Input label="SKU" value={value.sku ?? ''} onChange={(event) => set('sku', event.target.value || null)} error={fieldError(error, 'sku') ?? fieldError(error, 'item.sku')} />
                <Input label="Barcode" value={value.barcode ?? ''} onChange={(event) => set('barcode', event.target.value || null)} error={fieldError(error, 'barcode') ?? fieldError(error, 'item.barcode')} />
                <ItemCategorySelect value={category} onChange={(next) => { onCategoryChange(next); set('item_category_id', next ? Number(next.id) : null); }} error={fieldError(error, 'item_category_id') ?? fieldError(error, 'item.item_category_id')} />
                <ItemBrandSelect value={brand} onChange={(next) => { onBrandChange(next); set('item_brand_id', next ? Number(next.id) : null); }} error={fieldError(error, 'item_brand_id') ?? fieldError(error, 'item.item_brand_id')} />
                {baseUomLocked ? (
                    <div>
                        <span className="mb-1 block text-sm font-medium text-slate-700">Base UOM</span>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            <span className="font-semibold">{baseUom ? `${baseUom.code ?? ''} - ${baseUom.name}` : 'Not set'}</span>
                            <p className="mt-1 text-xs text-slate-500">Locked because this item has operational or document usage.</p>
                            {baseUomChangeHref && <Link className="mt-2 inline-block font-semibold text-sky-700 hover:underline" to={baseUomChangeHref}>Change via conversion wizard</Link>}
                        </div>
                    </div>
                ) : (
                    <ItemUomSelect label="Base UOM" value={baseUom} onChange={(next) => { onBaseUomChange(next); set('base_uom_id', next ? Number(next.id) : null); }} error={fieldError(error, 'base_uom_id') ?? fieldError(error, 'item.base_uom_id')} />
                )}
            </div>
            <div className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Select
                    label="Default tax group"
                    value={value.default_tax_group_id ?? ''}
                    onChange={(event) => set('default_tax_group_id', event.target.value ? Number(event.target.value) : null)}
                    options={taxGroupOptions}
                    error={fieldError(error, 'default_tax_group_id') ?? fieldError(error, 'item.default_tax_group_id')}
                    disabled={taxLookups.loading}
                />
                <Select
                    label="Purchase tax group"
                    value={value.purchase_tax_group_id ?? ''}
                    onChange={(event) => set('purchase_tax_group_id', event.target.value ? Number(event.target.value) : null)}
                    options={taxGroupOptions}
                    error={fieldError(error, 'purchase_tax_group_id') ?? fieldError(error, 'item.purchase_tax_group_id')}
                    disabled={taxLookups.loading}
                />
                <Select
                    label="Sales tax group"
                    value={value.sales_tax_group_id ?? ''}
                    onChange={(event) => set('sales_tax_group_id', event.target.value ? Number(event.target.value) : null)}
                    options={taxGroupOptions}
                    error={fieldError(error, 'sales_tax_group_id') ?? fieldError(error, 'item.sales_tax_group_id')}
                    disabled={taxLookups.loading}
                />
            </div>
            <div className="mt-4">
                <Textarea label="Description" value={value.description ?? ''} onChange={(event) => set('description', event.target.value || null)} />
            </div>
            <div className="mt-4 flex flex-wrap gap-6 text-sm text-slate-700">
                <label><input className="mr-2" type="checkbox" checked={value.is_stockable} disabled={nonInventoryType} onChange={(event) => set('is_stockable', event.target.checked)} />Stockable</label>
                <span className="text-slate-500">{comboType ? 'Bundle composition item' : 'Single item'}</span>
                <label><input className="mr-2" type="checkbox" checked={value.is_tax_exempt} onChange={(event) => set('is_tax_exempt', event.target.checked)} />Tax exempt</label>
                <label><input className="mr-2" type="checkbox" checked={value.is_active} onChange={(event) => set('is_active', event.target.checked)} />Active</label>
            </div>
        </Panel>
    );
}
