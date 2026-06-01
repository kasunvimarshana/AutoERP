import { useCallback, useEffect, useState } from 'react';
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

type ItemTabKey = typeof tabs[number]['value'];

type ItemTabData = {
    activity: ItemAuditEntry[];
    attributes: ItemAttribute[];
    comboComponents: ItemComboComponent[];
    identifiers: ItemIdentifier[];
    inventorySummary: ItemInventorySummary;
    pricingReferences: ItemPricingReference[];
    units: ItemUnit[];
    variants: ItemVariant[];
};

export function ItemDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState<ItemTabKey>('overview');
    const [item, setItem] = useState<Item | null>(null);
    const [usage, setUsage] = useState<ItemUsageSummary | null>(null);
    const [tabData, setTabData] = useState<Partial<ItemTabData>>({});
    const [tabLoading, setTabLoading] = useState<Partial<Record<ItemTabKey, boolean>>>({});
    const [tabErrors, setTabErrors] = useState<Partial<Record<ItemTabKey, string>>>({});
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isChangingStatus, setIsChangingStatus] = useState(false);

    useEffect(() => {
        let mounted = true;
        const itemId = id ?? '';
        setIsLoading(true);
        setError('');
        setTabData({});
        setTabErrors({});
        setTabLoading({});
        Promise.all([
            itemApi.getItem(itemId),
            itemApi.getItemUsage(itemId),
        ])
            .then(([itemResponse, usageResponse]) => {
                if (mounted) {
                    setItem(itemResponse.data);
                    setUsage(usageResponse.data);
                }
            })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load item detail.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    const loadActiveTab = useCallback((tab: ItemTabKey, force = false) => {
        const itemId = id ?? '';
        if (!itemId || tab === 'overview' || tab === 'usage') {
            return;
        }

        const hasData = (
            (tab === 'units' && tabData.units) ||
            (tab === 'attributes' && tabData.attributes) ||
            (tab === 'variants' && tabData.variants) ||
            (tab === 'combo' && tabData.comboComponents) ||
            (tab === 'identifiers' && tabData.identifiers) ||
            (tab === 'pricing' && tabData.pricingReferences) ||
            (tab === 'inventory' && tabData.inventorySummary) ||
            (tab === 'audit' && tabData.activity)
        );

        if (!force && (hasData || tabLoading[tab])) {
            return;
        }

        setTabLoading((current) => ({ ...current, [tab]: true }));
        setTabErrors((current) => ({ ...current, [tab]: '' }));

        const request = {
            audit: () => itemApi.getItemActivity(itemId).then((response) => ({ activity: response.data })),
            attributes: () => itemApi.listAttributes().then((response) => ({ attributes: response.data })),
            combo: () => itemApi.listComboComponents(itemId).then((response) => ({ comboComponents: response.data })),
            identifiers: () => itemApi.listIdentifiers(itemId).then((response) => ({ identifiers: response.data })),
            inventory: () => itemApi.getInventorySummary(itemId).then((response) => ({ inventorySummary: response.data })),
            pricing: () => itemApi.getPricingReferences(itemId).then((response) => ({ pricingReferences: response.data })),
            units: () => itemApi.getItemUnits(itemId).then((response) => ({ units: response.data })),
            variants: () => itemApi.listVariants(itemId).then((response) => ({ variants: response.data })),
        }[tab as Exclude<ItemTabKey, 'overview' | 'usage'>];

        if (!request) {
            return;
        }

        request()
            .then((data) => setTabData((current) => ({ ...current, ...data })))
            .catch((caught: unknown) => setTabErrors((current) => ({ ...current, [tab]: caught instanceof Error ? caught.message : 'Unable to load this tab.' })))
            .finally(() => setTabLoading((current) => ({ ...current, [tab]: false })));
    }, [id, tabData, tabLoading]);

    useEffect(() => {
        loadActiveTab(activeTab);
    }, [activeTab, loadActiveTab]);

    if (isLoading) return <EmptyState description="Loading the item and capability summary..." title="Loading item detail" />;
    if (!item || !usage) return <EmptyState description={error || 'Item was not found.'} title="Unable to load item" />;

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

    function refreshTab(tab: ItemTabKey) {
        loadActiveTab(tab, true);
    }

    function renderTabState(tab: ItemTabKey, label: string) {
        if (tabLoading[tab]) {
            return <EmptyState description={`Loading ${label} from the backend...`} title={`Loading ${label}`} />;
        }

        if (tabErrors[tab]) {
            return (
                <div className="space-y-3">
                    <EmptyState description={tabErrors[tab]} title={`Unable to load ${label}`} />
                    <Button onClick={() => refreshTab(tab)} type="button">Retry</Button>
                </div>
            );
        }

        return null;
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
                    <ItemCapabilityPanel capabilities={usage.capabilities} />
                </div>
            ) : null}
            {activeTab === 'units' ? renderTabState('units', 'units') ?? <ItemUnitsPanel units={tabData.units ?? []} /> : null}
            {activeTab === 'attributes' ? renderTabState('attributes', 'attributes') ?? <ItemAttributesTable attributes={tabData.attributes ?? []} /> : null}
            {activeTab === 'variants' ? (
                renderTabState('variants', 'variants') ?? (
                    <div className="space-y-5">
                        <ItemVariantCreateForm item={item} onSaved={() => refreshTab('variants')} />
                        <ItemVariantsTable onDelete={async (row) => { await itemApi.deleteVariant(row.id); refreshTab('variants'); }} variants={tabData.variants ?? []} />
                    </div>
                )
            ) : null}
            {activeTab === 'combo' ? (
                renderTabState('combo', 'combo components') ?? (
                    <div className="space-y-5">
                        <ItemComboComponentCreateForm item={item} onSaved={() => refreshTab('combo')} />
                        <ItemComboComponentsTable components={tabData.comboComponents ?? []} onDelete={async (row) => { await itemApi.deleteComboComponent(row.id); refreshTab('combo'); }} />
                    </div>
                )
            ) : null}
            {activeTab === 'identifiers' ? (
                renderTabState('identifiers', 'identifiers') ?? (
                    <div className="space-y-5">
                        <ItemIdentifierCreateForm item={item} onSaved={() => refreshTab('identifiers')} />
                        <ItemIdentifiersTable identifiers={tabData.identifiers ?? []} onDelete={async (row) => { await itemApi.deleteIdentifier(row.id); refreshTab('identifiers'); }} />
                    </div>
                )
            ) : null}
            {activeTab === 'pricing' ? renderTabState('pricing', 'pricing references') ?? <ItemPricingReferencesPanel references={tabData.pricingReferences ?? []} /> : null}
            {activeTab === 'inventory' ? renderTabState('inventory', 'inventory summary') ?? (tabData.inventorySummary ? <ItemInventorySummaryPanel summary={tabData.inventorySummary} /> : <EmptyState description="No inventory summary is loaded yet." title="No inventory summary" />) : null}
            {activeTab === 'usage' ? <ItemUsagePanel summary={usage} /> : null}
            {activeTab === 'audit' ? renderTabState('audit', 'audit history') ?? <ItemActivityTimeline entries={tabData.activity ?? []} /> : null}
        </div>
    );
}
