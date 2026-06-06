import { lazy, Suspense } from 'react';
import { Link, useParams } from 'react-router-dom';
import { getItem } from '../itemApi';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button } from '@/shared/components/Button';
import { Tabs } from '@/shared/components/Tabs';
import { Panel } from '@/shared/components/Panel';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { RecordTable } from '@/shared/components/RecordTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { readableRelation } from '@/shared/utils/object';

const LazyNotice = lazy(() => import('../relations/ItemRelationNotice'));
type Tab = 'summary' | 'units' | 'variants' | 'bundle_lines' | 'prices' | 'codes' | 'usage_rules';
const tabs = [['summary', 'Summary'], ['units', 'Units'], ['variants', 'Variants'], ['bundle_lines', 'Bundles'], ['prices', 'Prices'], ['codes', 'Codes'], ['usage_rules', 'Usage Rules']].map(([id, label]) => ({ id: id as Tab, label }));
const fields: Record<Exclude<Tab, 'summary'>, string[]> = {
    units: ['uom', 'unit_role', 'conversion_factor', 'is_default', 'is_active'],
    variants: ['code', 'name', 'sku', 'barcode', 'is_active'],
    bundle_lines: ['child_item', 'quantity', 'line_type', 'uom', 'is_required'],
    prices: ['price_type', 'amount', 'currency', 'uom', 'effective_from', 'effective_to', 'is_active'],
    codes: ['code_type', 'code', 'party_type', 'is_primary'],
    usage_rules: ['module_code', 'is_enabled'],
};

export default function ItemDetailPage() {
    const id = Number(useParams().id);
    const result = useApi((signal) => getItem(id, signal), [id], Number.isFinite(id));
    const tabState = useOnDemandTab<Tab>('summary');
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const item = result.data;
    return (
        <>
            <ContentHeader title={item.name} description={item.code ?? undefined} actions={<Link to={`/items/${id}/edit`}><Button variant="secondary">Edit item</Button></Link>} />
            <Panel className="p-0">
                <Tabs tabs={tabs} active={tabState.activeTab} onChange={tabState.openTab} />
                <div className="p-5">
                    {tabState.activeTab === 'summary' ? (
                        <DetailGrid items={[
                            { label: 'Status', value: <StatusBadge status={item.is_active ? 'active' : 'inactive'} /> },
                            { label: 'Type', value: item.item_type },
                            { label: 'Tracking', value: item.tracking_type },
                            { label: 'Costing', value: item.costing_method },
                            { label: 'Category', value: readableRelation(item.category) },
                            { label: 'Brand', value: readableRelation(item.brand) },
                            { label: 'Base UOM', value: readableRelation(item.base_uom) },
                            { label: 'SKU', value: item.sku },
                            { label: 'Barcode', value: item.barcode },
                            { label: 'Stockable', value: item.is_stockable ? 'Yes' : 'No' },
                            { label: 'Description', value: item.description },
                        ]} />
                    ) : (
                        <Suspense fallback={<LoadingState />}>
                            <LazyNotice />
                            <div className="mt-4"><RecordTable rows={(item[tabState.activeTab] ?? []) as Record<string, unknown>[]} fields={fields[tabState.activeTab]} /></div>
                        </Suspense>
                    )}
                </div>
            </Panel>
        </>
    );
}
