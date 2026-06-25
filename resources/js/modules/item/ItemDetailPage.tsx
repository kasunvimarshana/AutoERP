import { lazy, Suspense } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { getItem } from './itemApi';
import { ItemSummaryCard } from './components/ItemSummaryCard';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasItemPermission, itemPermissions } from './itemPermissions';

const ItemUnitTab = lazy(() => import('./components/ItemUnitTab'));
const ItemVariantTab = lazy(() => import('./components/ItemVariantTab'));
const ItemBundleTab = lazy(() => import('./components/ItemBundleTab'));
const ItemPriceTab = lazy(() => import('./components/ItemPriceTab'));
const ItemCodeTab = lazy(() => import('./components/ItemCodeTab'));
const ItemUsageRuleTab = lazy(() => import('./components/ItemUsageRuleTab'));
const BaseUomRevisionHistoryTab = lazy(() => import('./components/BaseUomRevisionHistoryTab'));

type Tab = 'summary' | 'units' | 'variants' | 'bundles' | 'prices' | 'codes' | 'usage_rules' | 'base_uom_history';
const tabs = [
    ['summary', 'Summary'], ['units', 'Units'], ['variants', 'Variants'], ['bundles', 'Bundle'],
    ['prices', 'Pricing'], ['codes', 'Codes'], ['usage_rules', 'Usage'], ['base_uom_history', 'Base UOM History'],
].map(([id, label]) => ({ id: id as Tab, label }));

export default function ItemDetailPage() {
    const itemId = Number(useParams().id);
    const [searchParams] = useSearchParams();
    const auth = useAuth();
    const requestedTab = tabs.some((entry) => entry.id === searchParams.get('tab')) ? searchParams.get('tab') as Tab : 'summary';
    const item = useApi((signal) => getItem(itemId, signal), [itemId], Number.isFinite(itemId));
    const tab = useOnDemandTab<Tab>(requestedTab);
    const canEdit = hasItemPermission(auth.permissions, itemPermissions.update);
    if (item.loading) return <LoadingState />;
    if (!item.data) return <ErrorAlert error={item.error} />;

    return <>
        <ContentHeader title={`${item.data.code} - ${item.data.name}`} description="Read-only item master data and related records." actions={canEdit ? <LinkButton to={`/items/${itemId}/edit`} variant="secondary">Edit</LinkButton> : undefined} />
        <Panel className="p-0">
            <Tabs tabs={tabs} active={tab.activeTab} onChange={tab.openTab} />
            <div className="p-5">
                {tab.activeTab === 'summary' && <ItemSummaryCard item={item.data} />}
                <Suspense fallback={<LoadingState />}>
                    {tab.openedTabs.has('units') && <div hidden={tab.activeTab !== 'units'}><ItemUnitTab itemId={itemId} readOnly /></div>}
                    {tab.openedTabs.has('variants') && <div hidden={tab.activeTab !== 'variants'}><ItemVariantTab itemId={itemId} readOnly /></div>}
                    {tab.openedTabs.has('bundles') && <div hidden={tab.activeTab !== 'bundles'}><ItemBundleTab itemId={itemId} canBundle={['combo', 'package'].includes(item.data.item_type)} readOnly /></div>}
                    {tab.openedTabs.has('prices') && <div hidden={tab.activeTab !== 'prices'}><ItemPriceTab itemId={itemId} readOnly /></div>}
                    {tab.openedTabs.has('codes') && <div hidden={tab.activeTab !== 'codes'}><ItemCodeTab itemId={itemId} readOnly /></div>}
                    {tab.openedTabs.has('usage_rules') && <div hidden={tab.activeTab !== 'usage_rules'}><ItemUsageRuleTab itemId={itemId} readOnly /></div>}
                    {tab.openedTabs.has('base_uom_history') && <div hidden={tab.activeTab !== 'base_uom_history'}><BaseUomRevisionHistoryTab itemId={itemId} /></div>}
                </Suspense>
            </div>
        </Panel>
    </>;
}
