import type { ApiError } from '@/shared/api/apiError';
import { fieldError } from '@/shared/api/apiError';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
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
    const set = <K extends keyof ItemPayload>(key: K, next: ItemPayload[K]) => onChange({ ...value, [key]: next });
    const options = (entries: readonly string[]) => entries.map((entry) => ({ value: entry, label: entry.replaceAll('_', ' ') }));

    return (
        <Panel title="Item identity">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Input label="Code" value={value.code} onChange={(event) => set('code', event.target.value)} error={fieldError(error, 'code') ?? fieldError(error, 'item.code')} required />
                <Input label="Name" value={value.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name') ?? fieldError(error, 'item.name')} required />
                <Select label="Item type" value={value.item_type} onChange={(event) => {
                    const itemType = event.target.value;
                    onChange({
                        ...value,
                        item_type: itemType,
                        is_combo: ['combo', 'package'].includes(itemType) ? true : value.is_combo,
                        is_stockable: ['service', 'labour'].includes(itemType) ? false : value.is_stockable,
                    });
                }} options={options(itemTypes)} error={fieldError(error, 'item_type') ?? fieldError(error, 'item.item_type')} />
                <Select label="Tracking type" value={value.tracking_type} onChange={(event) => set('tracking_type', event.target.value)} options={options(trackingTypes)} />
                <Select label="Costing method" value={value.costing_method} onChange={(event) => set('costing_method', event.target.value)} options={options(costingMethods)} />
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
            <div className="mt-4">
                <Textarea label="Description" value={value.description ?? ''} onChange={(event) => set('description', event.target.value || null)} />
            </div>
            <div className="mt-4 flex flex-wrap gap-6 text-sm text-slate-700">
                <label><input className="mr-2" type="checkbox" checked={value.is_stockable} onChange={(event) => set('is_stockable', event.target.checked)} />Stockable</label>
                <label><input className="mr-2" type="checkbox" checked={value.is_combo} onChange={(event) => set('is_combo', event.target.checked)} />Combo/package composition</label>
                <label><input className="mr-2" type="checkbox" checked={value.is_active} onChange={(event) => set('is_active', event.target.checked)} />Active</label>
            </div>
        </Panel>
    );
}
