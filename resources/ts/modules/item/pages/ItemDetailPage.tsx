import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { ItemSummaryCard } from '../components/ItemSummaryCard';
import { ItemActivityTimeline, ItemAttributesTable, ItemComboComponentsTable, ItemIdentifiersTable, ItemInventorySummaryPanel, ItemPricingReferencesPanel, ItemUnitsPanel, ItemUsagePanel, ItemVariantsTable } from '../components/ItemPanels';
import { itemApi } from '../services/itemApi';
import type { Item, ItemAttribute, ItemAuditEntry, ItemComboComponent, ItemIdentifier, ItemInventorySummary, ItemPricingReference, ItemUnit, ItemUsageSummary, ItemVariant } from '../types/item.types';

const tabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Units / UOM', value: 'units' },
    { label: 'Attributes', value: 'attributes' },
    { label: 'Variants', value: 'variants' },
    { label: 'Combo / Bundle Components', value: 'combo' },
    { label: 'Identifiers / Barcodes', value: 'identifiers' },
    { label: 'Pricing References', value: 'pricing' },
    { label: 'Inventory Summary', value: 'inventory' },
    { label: 'Usage / Activity', value: 'usage' },
    { label: 'Audit / History', value: 'audit' },
];

type ItemDetailState = {
    activity: ItemAuditEntry[];
    attributes: ItemAttribute[];
    comboComponents: ItemComboComponent[];
    identifiers: ItemIdentifier[];
    inventorySummary: ItemInventorySummary;
    item: Item;
    pricingReferences: ItemPricingReference[];
    units: ItemUnit[];
    usage: ItemUsageSummary;
    variants: ItemVariant[];
};

export function ItemDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [detail, setDetail] = useState<ItemDetailState | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isChangingStatus, setIsChangingStatus] = useState(false);

    useEffect(() => {
        let mounted = true;
        const itemId = id ?? '';
        Promise.all([
            itemApi.getItem(itemId),
            itemApi.getItemUnits(itemId),
            itemApi.listAttributes(),
            itemApi.listVariants(itemId),
            itemApi.listComboComponents(itemId),
            itemApi.listIdentifiers(itemId),
            itemApi.getPricingReferences(itemId),
            itemApi.getInventorySummary(itemId),
            itemApi.getItemUsage(itemId),
            itemApi.getItemActivity(itemId),
        ])
            .then(([item, units, attributes, variants, comboComponents, identifiers, pricingReferences, inventorySummary, usage, activity]) => {
                if (mounted) setDetail({ activity: activity.data, attributes: attributes.data, comboComponents: comboComponents.data, identifiers: identifiers.data, inventorySummary: inventorySummary.data, item: item.data, pricingReferences: pricingReferences.data, units: units.data, usage: usage.data, variants: variants.data });
            })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load item detail.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading item setup, backend preview panels, and audit..." title="Loading item detail" />;
    if (!detail) return <EmptyState description={error || 'Item was not found.'} title="Unable to load item" />;

    const { activity, attributes, comboComponents, identifiers, inventorySummary, item, pricingReferences, units, usage, variants } = detail;

    async function changeStatus(action: 'activate' | 'deactivate') {
        if (!detail) {
            return;
        }

        setIsChangingStatus(true);
        setError('');

        try {
            const response = action === 'activate'
                ? await itemApi.activateItem(detail.item.id)
                : await itemApi.deactivateItem(detail.item.id);
            setDetail({ ...detail, item: response.data });
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to change item status.');
        } finally {
            setIsChangingStatus(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/items"><Button variant="secondary">Back</Button></Link>{item.status === 'inactive' ? <Button disabled={isChangingStatus} onClick={() => void changeStatus('activate')} variant="secondary">Activate</Button> : <Button disabled={isChangingStatus} onClick={() => void changeStatus('deactivate')} variant="secondary">Deactivate</Button>}<Link to={`/items/${item.id}/edit`}><Button>Edit Item</Button></Link></>} eyebrow="Item" subtitle="Item detail separates UOM, variants, combo components, pricing references, inventory previews, usage, and audit." title={item.name} />
            {error ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{error}</div> : null}
            <ItemSummaryCard item={item} />
            <Card className="p-5"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_340px]">
                    <Card className="p-5">
                        <h2 className="text-base font-bold text-slate-950">Overview</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            {[
                                ['SKU', item.code],
                                ['Display name', item.displayName],
                                ['Category', item.category],
                                ['Brand', item.brand || 'Not provided'],
                                ['Base UOM', item.baseUom],
                                ['Stock behavior', item.stockBehavior === 'stock_tracked' ? 'Affects stock' : item.stockBehavior === 'reference_only' ? 'Reference only' : 'No stock impact'],
                            ].map(([label, value]) => <div className="rounded-lg border border-slate-200 bg-slate-50 p-3" key={label}><p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p><p className="mt-1 text-sm font-semibold text-slate-800">{value}</p></div>)}
                        </div>
                    </Card>
                    <PreviewPanel rows={[
                        { label: 'Stock availability', value: 'Backend-owned' },
                        { label: 'Pricing', value: 'Backend price resolver' },
                        { label: 'UOM conversion', value: 'Backend conversion rules' },
                        { label: 'Combo expansion', value: 'Backend validation' },
                    ]} title="Backend ownership" />
                </div>
            ) : null}
            {activeTab === 'units' ? <ItemUnitsPanel units={units} /> : null}
            {activeTab === 'attributes' ? <ItemAttributesTable attributes={attributes} /> : null}
            {activeTab === 'variants' ? <ItemVariantsTable variants={variants} /> : null}
            {activeTab === 'combo' ? <ItemComboComponentsTable components={comboComponents} /> : null}
            {activeTab === 'identifiers' ? <ItemIdentifiersTable identifiers={identifiers} /> : null}
            {activeTab === 'pricing' ? <ItemPricingReferencesPanel references={pricingReferences} /> : null}
            {activeTab === 'inventory' ? <ItemInventorySummaryPanel summary={inventorySummary} /> : null}
            {activeTab === 'usage' ? <ItemUsagePanel summary={usage} /> : null}
            {activeTab === 'audit' ? <ItemActivityTimeline entries={activity} /> : null}
        </div>
    );
}
