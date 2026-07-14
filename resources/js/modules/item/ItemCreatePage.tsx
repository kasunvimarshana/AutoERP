import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { TENANT_MODULE_CODE } from '@/app/access/tenantModules';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import type { NamedResource } from '@/shared/types/common';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import {
    emptyLaborItemCommissionDraft,
    LaborItemCommissionPanel,
    type LaborItemCommissionDraft,
} from '@/modules/vehicle-service/components/LaborItemCommissionPanel';
import { saveLaborItemCommissionRule } from '@/modules/vehicle-service/vehicleServiceApi';
import { vehicleServicePermissions } from '@/modules/vehicle-service/vehicleServicePermissions';
import { createItemWithRelations } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';
import type { ItemPayload, ItemWithRelationsPayload } from './itemTypes';
import { ItemForm } from './components/ItemForm';
import { emptyOneShotDraft, ItemOneShotBuilder, type OneShotDraft } from './components/ItemOneShotBuilder';

const ZERO_AMOUNT = '0.000000';
type Tab = 'basic' | 'units' | 'variants' | 'bundles' | 'prices' | 'codes' | 'usage_rules' | 'commission';
const tabs = [
    ['basic', 'Basic'], ['units', 'Units'], ['variants', 'Variants'], ['bundles', 'Bundles'],
    ['prices', 'Pricing'], ['codes', 'Codes'], ['usage_rules', 'Usage'], ['commission', 'Commission'],
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
    const canCreate = hasItemPermission(auth, itemPermissions.create);
    const canManageLaborCommission = auth.enabledModules?.includes(TENANT_MODULE_CODE.VEHICLE_SERVICE) === true
        && hasPermission(auth, vehicleServicePermissions.commissionsManage);
    const navigate = useNavigate();
    const [item, setItem] = useState(initialItem);
    const [category, setCategory] = useState<NamedResource | null>(null);
    const [brand, setBrand] = useState<NamedResource | null>(null);
    const [baseUom, setBaseUom] = useState<NamedResource | null>(null);
    const [activeTab, setActiveTab] = useState<Tab>('basic');
    const [draft, setDraft] = useState<OneShotDraft>(emptyOneShotDraft);
    const [laborCommission, setLaborCommission] = useState<LaborItemCommissionDraft>(emptyLaborItemCommissionDraft);
    const [createdItemId, setCreatedItemId] = useState<number | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);
    const visibleTabs = useMemo(
        () => tabs.filter((entry) => {
            if (entry.id === 'bundles') return ['combo', 'package'].includes(item.item_type);
            if (entry.id === 'commission') return item.item_type === 'labour' && canManageLaborCommission;
            return true;
        }),
        [canManageLaborCommission, item.item_type],
    );

    const displayedTab = createdItemId !== null
        ? 'commission'
        : visibleTabs.some((entry) => entry.id === activeTab) ? activeTab : 'basic';

    return <>
        <ContentHeader title="Create Item" description="Capture catalog setup, units, variants, pricing, codes, usage rules, and optional Vehicle Service labor defaults from one screen." />
        {!canCreate && <CapabilityNotice>You do not have permission to create items.</CapabilityNotice>}
        {createdItemId !== null && (
            <CapabilityNotice>
                The labor item was created successfully. Its commission rule was not saved; retrying will update only the Vehicle Service rule and will not create another item.
            </CapabilityNotice>
        )}
        <ErrorAlert error={error} />
        {canCreate && <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
            {createdItemId === null && <Panel className="p-0"><Tabs tabs={visibleTabs} active={displayedTab} onChange={setActiveTab} /></Panel>}
            {createdItemId === null && displayedTab === 'basic' && <ItemForm value={item} onChange={(next) => { formGuard.markDirty(); setItem(next); }} category={category} onCategoryChange={(next) => { formGuard.markDirty(); setCategory(next); }} brand={brand} onBrandChange={(next) => { formGuard.markDirty(); setBrand(next); }} baseUom={baseUom} onBaseUomChange={(next) => { formGuard.markDirty(); setBaseUom(next); }} error={error} />}
            {createdItemId === null && displayedTab !== 'basic' && displayedTab !== 'commission' && <Panel><ItemOneShotBuilder section={displayedTab} value={draft} onChange={(next) => { formGuard.markDirty(); setDraft(next); }} /></Panel>}
            {displayedTab === 'commission' && <LaborItemCommissionPanel value={laborCommission} onChange={(next) => { formGuard.markDirty(); setLaborCommission(next); }} disabled={submitting} />}
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => createdItemId === null ? navigate(-1) : navigate(`/items/${createdItemId}`)}>Cancel</Button>
                <Button type="submit" loading={submitting}>{createdItemId === null ? 'Create Item' : 'Retry commission rule'}</Button>
            </div>
        </form>}
    </>;

    async function save() {
        setSubmitting(true);
        setError(null);
        let itemId = createdItemId;
        try {
            if (itemId === null) {
                const saved = await createItemWithRelations(toPayload(item, draft));
                itemId = saved.id;
                setCreatedItemId(itemId);
            }

            if (item.item_type === 'labour'
                && canManageLaborCommission
                && laborCommission.commission_type !== 'none') {
                await saveLaborItemCommissionRule(itemId, laborCommission.role_type, {
                    commission_type: laborCommission.commission_type,
                    commission_value: laborCommission.commission_value.trim() || ZERO_AMOUNT,
                    is_active: laborCommission.is_active,
                });
            }

            formGuard.markSaved();
            navigate(`/items/${itemId}`);
        } catch (requestError) {
            if (itemId !== null) {
                setCreatedItemId(itemId);
                setActiveTab('commission');
            }
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
        prices: draft.prices.map(({ currency: _currency, uom: _uom, ...row }) => row),
        codes: draft.codes,
        usage_rules: draft.usageRules,
    };
}
