import { lazy, Suspense, useState } from 'react';
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

const ItemUnitTab = lazy(() => import('./components/ItemUnitTab'));
const ItemVariantTab = lazy(() => import('./components/ItemVariantTab'));
const ItemBundleTab = lazy(() => import('./components/ItemBundleTab'));
const ItemPriceTab = lazy(() => import('./components/ItemPriceTab'));
const ItemCodeTab = lazy(() => import('./components/ItemCodeTab'));
const ItemUsageRuleTab = lazy(() => import('./components/ItemUsageRuleTab'));
const BaseUomChangeWizard = lazy(() => import('./components/BaseUomChangeWizard'));
const BaseUomRevisionHistoryTab = lazy(() => import('./components/BaseUomRevisionHistoryTab'));

type Tab = 'summary' | 'base_uom' | 'units' | 'variants' | 'bundles' | 'prices' | 'codes' | 'usage_rules';
const tabs = [
    ['summary', 'Summary'], ['base_uom', 'Base UOM'], ['units', 'Units'], ['variants', 'Variants'], ['bundles', 'Bundles'],
    ['prices', 'Prices'], ['codes', 'Codes'], ['usage_rules', 'Usage Rules'],
].map(([id, label]) => ({ id: id as Tab, label }));

export default function ItemDetailPage() {
    const itemId = Number(useParams().id);
    const [searchParams] = useSearchParams();
    const item = useApi((signal) => getItem(itemId, signal), [itemId], Number.isFinite(itemId));
    const tab = useOnDemandTab<Tab>(searchParams.get('tab') === 'base_uom' ? 'base_uom' : 'summary');
    const [revisionVersion, setRevisionVersion] = useState(0);
    if (item.loading) return <LoadingState />;
    if (!item.data) return <ErrorAlert error={item.error} />;

    return <>
        <ContentHeader title={`${item.data.code} - ${item.data.name}`} description="Item master data and relation-aware CRUD." actions={<LinkButton to={`/items/${itemId}/edit`} variant="secondary">Edit item</LinkButton>} />
        <Panel className="p-0">
            <Tabs tabs={tabs} active={tab.activeTab} onChange={tab.openTab} />
            <div className="p-5">
                {tab.activeTab === 'summary' && <ItemSummaryCard item={item.data} />}
                <Suspense fallback={<LoadingState />}>
                    {tab.openedTabs.has('base_uom') && <div hidden={tab.activeTab !== 'base_uom'} className="space-y-5">
                        <BaseUomChangeWizard itemId={itemId} onApplied={() => { item.reload(); setRevisionVersion((value) => value + 1); }} />
                        <BaseUomRevisionHistoryTab itemId={itemId} refreshKey={revisionVersion} />
                    </div>}
                    {tab.openedTabs.has('units') && <div hidden={tab.activeTab !== 'units'}><ItemUnitTab itemId={itemId} /></div>}
                    {tab.openedTabs.has('variants') && <div hidden={tab.activeTab !== 'variants'}><ItemVariantTab itemId={itemId} /></div>}
                    {tab.openedTabs.has('bundles') && <div hidden={tab.activeTab !== 'bundles'}><ItemBundleTab itemId={itemId} canBundle={['combo', 'package'].includes(item.data.item_type)} /></div>}
                    {tab.openedTabs.has('prices') && <div hidden={tab.activeTab !== 'prices'}><ItemPriceTab itemId={itemId} /></div>}
                    {tab.openedTabs.has('codes') && <div hidden={tab.activeTab !== 'codes'}><ItemCodeTab itemId={itemId} /></div>}
                    {tab.openedTabs.has('usage_rules') && <div hidden={tab.activeTab !== 'usage_rules'}><ItemUsageRuleTab itemId={itemId} /></div>}
                </Suspense>
            </div>
        </Panel>
    </>;
}
