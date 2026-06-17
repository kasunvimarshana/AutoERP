import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import type { NamedResource } from '@/shared/types/common';
import { useAuth } from '@/modules/auth/AuthProvider';
import { createItemWithRelations } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';
import type { ItemPayload, ItemWithRelationsPayload } from './itemTypes';
import { ItemForm } from './components/ItemForm';
import { emptyOneShotDraft, ItemOneShotBuilder, type OneShotDraft } from './components/ItemOneShotBuilder';

type Tab = 'basic' | 'units' | 'variants' | 'bundles' | 'prices' | 'codes' | 'usage_rules';
const tabs = [
    ['basic', 'Basic'], ['units', 'Units'], ['variants', 'Variants'], ['bundles', 'Bundles'],
    ['prices', 'Pricing'], ['codes', 'Codes'], ['usage_rules', 'Usage'],
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
    default_tax_group_id: null,
    purchase_tax_group_id: null,
    sales_tax_group_id: null,
    sku: null,
    barcode: null,
    description: null,
    is_stockable: true,
    is_combo: false,
    is_tax_exempt: false,
    is_active: true,
};

export default function ItemCreatePage() {
    const auth = useAuth();
    const canCreate = hasItemPermission(auth.permissions, itemPermissions.create);
    const navigate = useNavigate();
    const [item, setItem] = useState(initialItem);
    const [category, setCategory] = useState<NamedResource | null>(null);
    const [brand, setBrand] = useState<NamedResource | null>(null);
    const [baseUom, setBaseUom] = useState<NamedResource | null>(null);
    const [activeTab, setActiveTab] = useState<Tab>('basic');
    const [draft, setDraft] = useState<OneShotDraft>(emptyOneShotDraft);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const visibleTabs = useMemo(
        () => tabs.filter((entry) => entry.id !== 'bundles' || ['combo', 'package'].includes(item.item_type)),
        [item.item_type],
    );

    useEffect(() => {
        if (!visibleTabs.some((entry) => entry.id === activeTab)) {
            setActiveTab('basic');
        }
    }, [activeTab, visibleTabs]);

    return <>
        <ContentHeader title="Create Item" description="Capture catalog setup, units, variants, pricing, codes, and usage rules from one screen." />
        {!canCreate && <CapabilityNotice>You do not have permission to create items.</CapabilityNotice>}
        <ErrorAlert error={error} />
        {canCreate && <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
            <Panel className="p-0"><Tabs tabs={visibleTabs} active={activeTab} onChange={setActiveTab} /></Panel>
            {activeTab === 'basic' && <ItemForm value={item} onChange={setItem} category={category} onCategoryChange={setCategory} brand={brand} onBrandChange={setBrand} baseUom={baseUom} onBaseUomChange={setBaseUom} error={error} />}
            {activeTab !== 'basic' && <Panel><ItemOneShotBuilder section={activeTab} value={draft} onChange={setDraft} /></Panel>}
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                <Button type="submit" loading={submitting}>Create Item</Button>
            </div>
        </form>}
    </>;

    async function save() {
        setSubmitting(true);
        setError(null);
        try {
            const saved = await createItemWithRelations(toPayload(item, draft));
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
