import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { ItemSummaryCard } from '../components/ItemSummaryCard';
import { ItemActivityTimeline, ItemAttributesTable, ItemCapabilityPanel, ItemComboComponentsTable, ItemIdentifiersTable, ItemInventorySummaryPanel, ItemPricingReferencesPanel, ItemUnitsPanel, ItemUsagePanel, ItemVariantsTable } from '../components/ItemPanels';
import { ItemComboComponentCreateForm, ItemIdentifierCreateForm, ItemVariantCreateForm } from '../components/ItemSubResourceForms';
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

export function ItemDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [item, setItem] = useState<Item | null>(null);
    const [activity, setActivity] = useState<ItemAuditEntry[]>();
    const [attributes, setAttributes] = useState<ItemAttribute[]>();
    const [comboComponents, setComboComponents] = useState<ItemComboComponent[]>();
    const [identifiers, setIdentifiers] = useState<ItemIdentifier[]>();
    const [inventorySummary, setInventorySummary] = useState<ItemInventorySummary>();
    const [pricingReferences, setPricingReferences] = useState<ItemPricingReference[]>();
    const [units, setUnits] = useState<ItemUnit[]>();
    const [usage, setUsage] = useState<ItemUsageSummary>();
    const [variants, setVariants] = useState<ItemVariant[]>();
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isChangingStatus, setIsChangingStatus] = useState(false);
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        let mounted = true;
        const itemId = id ?? '';
        setIsLoading(true);
        setItem(null);
        setActivity(undefined);
        setAttributes(undefined);
        setComboComponents(undefined);
        setIdentifiers(undefined);
        setInventorySummary(undefined);
        setPricingReferences(undefined);
        setUnits(undefined);
        setUsage(undefined);
        setVariants(undefined);
        itemApi.getItem(itemId)
            .then((response) => {
                if (mounted) {
                    setItem(response.data);
                    setError('');
                }
            })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load item detail.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id, reloadKey]);

    useEffect(() => {
        if (!item) {
            return undefined;
        }

        let mounted = true;
        const itemId = item.id;

        if (activeTab === 'overview' && usage === undefined) {
            itemApi.getItemUsage(itemId).then((response) => mounted && setUsage(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load item usage.'));
        }

        if (activeTab === 'units' && units === undefined) {
            itemApi.getItemUnits(itemId).then((response) => mounted && setUnits(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load item units.'));
        }

        if (activeTab === 'attributes' && attributes === undefined) {
            itemApi.listAttributes().then((response) => mounted && setAttributes(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load item attributes.'));
        }

        if (activeTab === 'variants' && variants === undefined) {
            itemApi.listVariants(itemId).then((response) => mounted && setVariants(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load item variants.'));
        }

        if (activeTab === 'combo' && comboComponents === undefined) {
            itemApi.listComboComponents(itemId).then((response) => mounted && setComboComponents(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load combo components.'));
        }

        if (activeTab === 'identifiers' && identifiers === undefined) {
            itemApi.listIdentifiers(itemId).then((response) => mounted && setIdentifiers(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load item identifiers.'));
        }

        if (activeTab === 'pricing' && pricingReferences === undefined) {
            itemApi.getPricingReferences(itemId).then((response) => mounted && setPricingReferences(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load pricing references.'));
        }

        if (activeTab === 'inventory' && inventorySummary === undefined) {
            itemApi.getInventorySummary(itemId).then((response) => mounted && setInventorySummary(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load inventory summary.'));
        }

        if (activeTab === 'usage' && usage === undefined) {
            itemApi.getItemUsage(itemId).then((response) => mounted && setUsage(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load item usage.'));
        }

        if (activeTab === 'audit' && activity === undefined) {
            itemApi.getItemActivity(itemId).then((response) => mounted && setActivity(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load item activity.'));
        }

        return () => {
            mounted = false;
        };
    }, [activeTab, activity, attributes, comboComponents, identifiers, inventorySummary, item, pricingReferences, units, usage, variants]);

    if (isLoading) return <EmptyState description="Loading item header from backend..." title="Loading item detail" />;
    if (!item) return <EmptyState description={error || 'Item was not found.'} title="Unable to load item" />;

    async function changeStatus(action: 'activate' | 'deactivate') {
        if (!item) {
            return;
        }

        setIsChangingStatus(true);
        setError('');

        try {
            const response = action === 'activate'
                ? await itemApi.activateItem(item.id)
                : await itemApi.deactivateItem(item.id);
            setItem(response.data);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to change item status.');
        } finally {
            setIsChangingStatus(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/items"><Button variant="secondary">Back</Button></Link>{item.status === 'inactive' ? <Button disabled={isChangingStatus} onClick={() => void changeStatus('activate')} variant="secondary">Activate</Button> : <Button disabled={isChangingStatus} onClick={() => void changeStatus('deactivate')} variant="secondary">Deactivate</Button>}<Link to={`/items/${item.id}/edit`}><Button>Edit Item</Button></Link></>} eyebrow="Item" subtitle="Item detail separates UOM, variants, combo components, pricing references, inventory summary, usage, and audit." title={item.name} />
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
                    {usage ? <ItemCapabilityPanel capabilities={usage.capabilities} /> : <EmptyState description="Loading item capability summary..." title="Loading capabilities" />}
                </div>
            ) : null}
            {activeTab === 'units' ? units ? <ItemUnitsPanel units={units} /> : <EmptyState description="Loading item UOMs..." title="Loading units" /> : null}
            {activeTab === 'attributes' ? attributes ? <ItemAttributesTable attributes={attributes} /> : <EmptyState description="Loading item attributes..." title="Loading attributes" /> : null}
            {activeTab === 'variants' ? (
                <div className="space-y-5">
                    <ItemVariantCreateForm item={item} onSaved={() => setReloadKey((value) => value + 1)} />
                    {variants ? <ItemVariantsTable onDelete={async (row) => { await itemApi.deleteVariant(row.id); setReloadKey((value) => value + 1); }} variants={variants} /> : <EmptyState description="Loading item variants..." title="Loading variants" />}
                </div>
            ) : null}
            {activeTab === 'combo' ? (
                <div className="space-y-5">
                    <ItemComboComponentCreateForm item={item} onSaved={() => setReloadKey((value) => value + 1)} />
                    {comboComponents ? <ItemComboComponentsTable components={comboComponents} onDelete={async (row) => { await itemApi.deleteComboComponent(row.id); setReloadKey((value) => value + 1); }} /> : <EmptyState description="Loading combo components..." title="Loading components" />}
                </div>
            ) : null}
            {activeTab === 'identifiers' ? (
                <div className="space-y-5">
                    <ItemIdentifierCreateForm item={item} onSaved={() => setReloadKey((value) => value + 1)} />
                    {identifiers ? <ItemIdentifiersTable identifiers={identifiers} onDelete={async (row) => { await itemApi.deleteIdentifier(row.id); setReloadKey((value) => value + 1); }} /> : <EmptyState description="Loading item identifiers..." title="Loading identifiers" />}
                </div>
            ) : null}
            {activeTab === 'pricing' ? pricingReferences ? <ItemPricingReferencesPanel references={pricingReferences} /> : <EmptyState description="Loading pricing references..." title="Loading pricing" /> : null}
            {activeTab === 'inventory' ? inventorySummary ? <ItemInventorySummaryPanel summary={inventorySummary} /> : <EmptyState description="Loading inventory summary..." title="Loading inventory" /> : null}
            {activeTab === 'usage' ? usage ? <ItemUsagePanel summary={usage} /> : <EmptyState description="Loading usage summary..." title="Loading usage" /> : null}
            {activeTab === 'audit' ? activity ? <ItemActivityTimeline entries={activity} /> : <EmptyState description="Loading item activity..." title="Loading activity" /> : null}
        </div>
    );
}
