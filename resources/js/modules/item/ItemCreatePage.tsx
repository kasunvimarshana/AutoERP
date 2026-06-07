import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import type { NamedResource } from '@/shared/types/common';
import { createItem, createItemWithRelations } from './itemApi';
import type { ItemPayload, ItemWithRelationsPayload } from './itemTypes';
import { ItemForm } from './components/ItemForm';
import { emptyOneShotDraft, ItemOneShotBuilder, type OneShotDraft } from './components/ItemOneShotBuilder';

type Tab = 'basic' | 'units' | 'variants' | 'bundles' | 'prices' | 'codes' | 'usage_rules' | 'review';
const tabs = [
    ['basic', 'Basic'], ['units', 'Units'], ['variants', 'Variants'], ['bundles', 'Bundles'],
    ['prices', 'Prices'], ['codes', 'Codes'], ['usage_rules', 'Usage Rules'], ['review', 'Review'],
].map(([id, label]) => ({ id: id as Tab, label }));

const initialItem: ItemPayload = {
    code: '',
    name: '',
    item_type: 'stock',
    tracking_type: 'none',
    costing_method: 'fifo',
    item_category_id: null,
    item_brand_id: null,
    base_uom_id: null,
    sku: null,
    barcode: null,
    description: null,
    is_stockable: true,
    is_combo: false,
    is_active: true,
};

export default function ItemCreatePage() {
    const navigate = useNavigate();
    const [item, setItem] = useState(initialItem);
    const [category, setCategory] = useState<NamedResource | null>(null);
    const [brand, setBrand] = useState<NamedResource | null>(null);
    const [baseUom, setBaseUom] = useState<NamedResource | null>(null);
    const [oneShot, setOneShot] = useState(true);
    const [activeTab, setActiveTab] = useState<Tab>('basic');
    const [draft, setDraft] = useState<OneShotDraft>(emptyOneShotDraft);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    return <>
        <ContentHeader title="New item" description="Capture catalog setup, units, variants, pricing, codes, and usage rules from one screen." />
        <div className="mb-4 flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4">
            <input id="one-shot" type="checkbox" checked={oneShot} onChange={(event) => { setOneShot(event.target.checked); setActiveTab('basic'); }} />
            <label htmlFor="one-shot" className="text-sm font-medium">Save related details together</label>
        </div>
        <ErrorAlert error={error} />
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
            {oneShot && <Panel className="p-0"><Tabs tabs={tabs} active={activeTab} onChange={setActiveTab} /></Panel>}
            {(!oneShot || activeTab === 'basic') && <ItemForm value={item} onChange={setItem} category={category} onCategoryChange={setCategory} brand={brand} onBrandChange={setBrand} baseUom={baseUom} onBaseUomChange={setBaseUom} error={error} />}
            {oneShot && activeTab !== 'basic' && <Panel><ItemOneShotBuilder section={activeTab} value={draft} onChange={setDraft} /></Panel>}
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                <Button type="submit" loading={submitting}>{oneShot ? 'Save everything' : 'Create item'}</Button>
            </div>
        </form>
    </>;

    async function save() {
        setSubmitting(true);
        setError(null);
        try {
            const saved = oneShot
                ? await createItemWithRelations(toPayload(item, draft))
                : await createItem(item);
            navigate(`/items/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }
}

function toPayload(item: ItemPayload, draft: OneShotDraft): ItemWithRelationsPayload {
    return {
        item,
        units: draft.units.map(({ uom: _uom, ...row }) => row),
        variants: draft.variants,
        bundles: draft.bundles.map(({ child_item: _child, uom: _uom, ...row }) => row),
        prices: draft.prices.map(({ uom: _uom, ...row }) => row),
        codes: draft.codes,
        usage_rules: draft.usageRules,
    };
}
