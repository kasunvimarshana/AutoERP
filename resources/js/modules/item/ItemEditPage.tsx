import { lazy, Suspense, useEffect, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { TENANT_MODULE_CODE } from '@/app/access/tenantModules';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { vehicleServicePermissions } from '@/modules/vehicle-service/vehicleServicePermissions';
import { inventoryPermissions } from '@/modules/inventory/inventoryPermissions';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import type { NamedResource } from '@/shared/types/common';
import { getBaseUomUsageAudit, getItem, updateItem } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';
import type { BaseUomUsageAudit, ItemPayload } from './itemTypes';
import { ItemForm } from './components/ItemForm';

const ItemUnitTab = lazy(() => import('./components/ItemUnitTab'));
const ItemVariantTab = lazy(() => import('./components/ItemVariantTab'));
const ItemBundleTab = lazy(() => import('./components/ItemBundleTab'));
const ItemPriceTab = lazy(() => import('./components/ItemPriceTab'));
const ItemCodeTab = lazy(() => import('./components/ItemCodeTab'));
const ItemUsageRuleTab = lazy(() => import('./components/ItemUsageRuleTab'));
const BaseUomChangeWizard = lazy(() => import('./components/BaseUomChangeWizard'));
const LaborItemCommissionEditor = lazy(
    () => import('@/modules/vehicle-service/components/LaborItemCommissionEditor'),
);

type Tab = 'basic' | 'units' | 'variants' | 'bundles' | 'prices' | 'codes' | 'usage_rules' | 'commission';
const tabs = [
    ['basic', 'Basic'], ['units', 'Units'], ['variants', 'Variants'], ['bundles', 'Bundle'],
    ['prices', 'Pricing'], ['codes', 'Codes'], ['usage_rules', 'Usage'], ['commission', 'Commission'],
].map(([id, label]) => ({ id: id as Tab, label }));

export default function ItemEditPage() {
    const itemId = Number(useParams().id);
    const auth = useAuth();
    const canUpdate = hasItemPermission(auth, itemPermissions.update);
    const canManageUnits = hasItemPermission(auth, itemPermissions.manageUnits);
    const canChangeBaseUom = hasItemPermission(auth, itemPermissions.changeBaseUom);
    const canManageVariants = hasItemPermission(auth, itemPermissions.manageVariants);
    const canManageBundles = hasItemPermission(auth, itemPermissions.manageBundles);
    const canManagePrices = hasItemPermission(auth, itemPermissions.managePrices);
    const canViewBatchPrices = hasPermission(auth, inventoryPermissions.trackingView);
    const canManageBatchPrices = hasPermission(auth, inventoryPermissions.trackingManage);
    const canManageCodes = hasItemPermission(auth, itemPermissions.manageCodes);
    const canManageUsageRules = hasItemPermission(auth, itemPermissions.manageUsageRules);
    const vehicleServiceEnabled = auth.enabledModules?.includes(TENANT_MODULE_CODE.VEHICLE_SERVICE) === true;
    const canViewLaborCommission = vehicleServiceEnabled
        && hasPermission(auth, vehicleServicePermissions.commissionsView);
    const canManageLaborCommission = vehicleServiceEnabled
        && hasPermission(auth, vehicleServicePermissions.commissionsManage);
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const requestedTab = tabs.some((entry) => entry.id === searchParams.get('tab')) ? searchParams.get('tab') as Tab : 'basic';
    const tab = useOnDemandTab<Tab>(requestedTab);
    const [form, setForm] = useState<ItemPayload | null>(null);
    const [category, setCategory] = useState<NamedResource | null>(null);
    const [brand, setBrand] = useState<NamedResource | null>(null);
    const [baseUom, setBaseUom] = useState<NamedResource | null>(null);
    const [priceDefaults, setPriceDefaults] = useState<{ currency: NamedResource | null; uom: NamedResource | null }>({ currency: null, uom: null });
    const [baseUomAudit, setBaseUomAudit] = useState<BaseUomUsageAudit | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);
    const visibleTabs = tabs.filter((entry) => entry.id !== 'commission'
        || (form?.item_type === 'labour' && canViewLaborCommission));
    const displayedTab = visibleTabs.some((entry) => entry.id === tab.activeTab)
        ? tab.activeTab
        : 'basic';

    useEffect(() => {
        const controller = new AbortController();
        Promise.all([getItem(itemId, controller.signal), getBaseUomUsageAudit(itemId, controller.signal)])
            .then(([item, audit]) => {
                if (controller.signal.aborted) return;
                setForm({
                    code: item.code,
                    name: item.name,
                    item_type: item.item_type,
                    tracking_type: item.tracking_type,
                    costing_method: item.costing_method,
                    item_category_id: item.category ? Number(item.category.id) : null,
                    item_brand_id: item.brand ? Number(item.brand.id) : null,
                    base_uom_id: item.base_uom ? Number(item.base_uom.id) : null,
                    default_tax_group_id: item.default_tax_group_id ?? null,
                    purchase_tax_group_id: item.purchase_tax_group_id ?? null,
                    sales_tax_group_id: item.sales_tax_group_id ?? null,
                    sku: item.sku ?? null,
                    barcode: item.barcode ?? null,
                    description: item.description ?? null,
                    is_stockable: item.is_stockable,
                    is_combo: item.is_combo,
                    is_tax_exempt: item.is_tax_exempt ?? false,
                    is_active: item.is_active,
                });
                setCategory(item.category ?? null);
                setBrand(item.brand ?? null);
                setBaseUom(item.base_uom ?? null);
                setPriceDefaults({ currency: item.tenant_base_currency ?? null, uom: item.base_uom ?? null });
                setBaseUomAudit(audit);
            })
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [itemId]);

    if (loading) return <LoadingState />;
    if (!form) return <ErrorAlert error={error} />;
    return <>
        <ContentHeader title="Edit Item" description="Update item fields, related item data, and Vehicle Service labor commission defaults." />
        {!canUpdate && displayedTab === 'basic' && <CapabilityNotice>You do not have permission to update item fields.</CapabilityNotice>}
        <ErrorAlert error={error} />
        <Panel className="p-0">
            <Tabs tabs={visibleTabs} active={displayedTab} onChange={tab.openTab} />
            <div className="p-5">
                {displayedTab === 'basic' && canUpdate && (
                    <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                        <ItemForm
                            value={form}
                            onChange={(next) => { formGuard.markDirty(); setForm(next); }}
                            category={category}
                            onCategoryChange={(next) => { formGuard.markDirty(); setCategory(next); }}
                            brand={brand}
                            onBrandChange={(next) => { formGuard.markDirty(); setBrand(next); }}
                            baseUom={baseUom}
                            onBaseUomChange={(next) => { formGuard.markDirty(); setBaseUom(next); }}

                            error={error}
                            baseUomLocked={baseUomAudit?.has_usage === true}
                            baseUomChangeHref={`/items/${itemId}/edit?tab=units`}
                        />
                        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button><Button type="submit" loading={submitting}>Save Item</Button></div>
                    </form>
                )}
                <Suspense fallback={<LoadingState />}>
                    {tab.openedTabs.has('units') && <div hidden={displayedTab !== 'units'} className="space-y-5">
                        {canChangeBaseUom && <BaseUomChangeWizard itemId={itemId} onApplied={() => navigate(0)} />}
                        <ItemUnitTab itemId={itemId} readOnly={!canManageUnits} />
                    </div>}
                    {tab.openedTabs.has('variants') && <div hidden={displayedTab !== 'variants'}><ItemVariantTab itemId={itemId} readOnly={!canManageVariants} /></div>}
                    {tab.openedTabs.has('bundles') && <div hidden={displayedTab !== 'bundles'}><ItemBundleTab itemId={itemId} canBundle={['combo', 'package'].includes(form.item_type)} readOnly={!canManageBundles} /></div>}
                    {tab.openedTabs.has('prices') && <div hidden={displayedTab !== 'prices'}><ItemPriceTab itemId={itemId} trackingType={form.tracking_type} defaultCurrency={priceDefaults.currency} defaultUom={priceDefaults.uom} readOnly={!canManagePrices} canViewBatchPrices={canViewBatchPrices} canManageBatchPrices={canManageBatchPrices} /></div>}
                    {tab.openedTabs.has('codes') && <div hidden={displayedTab !== 'codes'}><ItemCodeTab itemId={itemId} readOnly={!canManageCodes} /></div>}
                    {tab.openedTabs.has('usage_rules') && <div hidden={displayedTab !== 'usage_rules'}><ItemUsageRuleTab itemId={itemId} readOnly={!canManageUsageRules} /></div>}
                    {tab.openedTabs.has('commission') && canViewLaborCommission && form.item_type === 'labour' && (
                        <div hidden={displayedTab !== 'commission'}>
                            <LaborItemCommissionEditor
                                itemId={itemId}
                                canManage={canManageLaborCommission}
                            />
                        </div>
                    )}
                </Suspense>
            </div>
        </Panel>
    </>;

    async function save() {
        if (!form) return;
        setSubmitting(true);
        setError(null);
        try {
            const saved = await updateItem(itemId, form);
            formGuard.markSaved();
            navigate(`/items/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }
}
