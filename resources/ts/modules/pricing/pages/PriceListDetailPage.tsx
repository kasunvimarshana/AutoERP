import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import {
    DiscountRulesTable,
    PriceListItemsManager,
    PriceListSummaryCard,
    PricingActivityTimeline,
    PricingRuleConditionsTable,
    PricingTierTable,
    PricingUsagePanel,
} from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { DiscountRule, PriceList, PriceListItem, PricingAuditEntry, PricingRule, PricingRuleCondition, PricingTier, PricingUsageSummary } from '../types/pricing.types';

const tabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Price List Items', value: 'items' },
    { label: 'Customer Price Lists', value: 'customers' },
    { label: 'Supplier Price Lists', value: 'suppliers' },
    { label: 'Pricing Rules', value: 'rules' },
    { label: 'Discounts', value: 'discounts' },
    { label: 'Tiers', value: 'tiers' },
    { label: 'Usage / Activity', value: 'usage' },
    { label: 'Audit / History', value: 'audit' },
];

export function PriceListDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [priceList, setPriceList] = useState<PriceList | null>(null);
    const [items, setItems] = useState<PriceListItem[]>([]);
    const [rules, setRules] = useState<PricingRule[]>([]);
    const [conditions, setConditions] = useState<PricingRuleCondition[]>([]);
    const [discountRules, setDiscountRules] = useState<DiscountRule[]>([]);
    const [tiers, setTiers] = useState<PricingTier[]>([]);
    const [usage, setUsage] = useState<PricingUsageSummary | null>(null);
    const [activity, setActivity] = useState<PricingAuditEntry[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [loadingTab, setLoadingTab] = useState('');
    const [tabError, setTabError] = useState('');

    function loadDetail() {
        let mounted = true;
        const priceListId = id ?? '';
        Promise.all([
            pricingApi.getPriceList(priceListId),
            pricingApi.getPricingUsage(priceListId, 'price-list'),
        ]).then(([priceListResponse, usageResponse]) => {
            if (mounted) {
                setPriceList(priceListResponse.data);
                setUsage(usageResponse.data);
            }
        }).catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load price list detail.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }

    useEffect(() => {
        return loadDetail();
    }, [id]);

    const loadItems = useCallback((force = false) => {
        if ((!force && items.length > 0) || loadingTab) return;
        setLoadingTab('items');
        setTabError('');
        pricingApi.listPriceListItems(id ?? '')
            .then((response) => setItems(response.data))
            .catch((caught: unknown) => setTabError(caught instanceof Error ? caught.message : 'Unable to load price list items.'))
            .finally(() => setLoadingTab(''));
    }, [id, items.length, loadingTab]);

    const loadRules = useCallback(() => {
        if ((rules.length > 0 && conditions.length > 0) || loadingTab) return;
        setLoadingTab('rules');
        setTabError('');
        Promise.all([pricingApi.listPricingRules(), pricingApi.listPricingRuleConditions()])
            .then(([ruleResponse, conditionResponse]) => {
                setRules(ruleResponse.data);
                setConditions(conditionResponse.data);
            })
            .catch((caught: unknown) => setTabError(caught instanceof Error ? caught.message : 'Unable to load pricing rules.'))
            .finally(() => setLoadingTab(''));
    }, [conditions.length, loadingTab, rules.length]);

    const loadDiscountRules = useCallback(() => {
        if (discountRules.length > 0 || loadingTab) return;
        setLoadingTab('discounts');
        setTabError('');
        pricingApi.listDiscountRules()
            .then((response) => setDiscountRules(response.data))
            .catch((caught: unknown) => setTabError(caught instanceof Error ? caught.message : 'Unable to load discount rules.'))
            .finally(() => setLoadingTab(''));
    }, [discountRules.length, loadingTab]);

    const loadTiers = useCallback(() => {
        if (tiers.length > 0 || loadingTab) return;
        setLoadingTab('tiers');
        setTabError('');
        Promise.all([pricingApi.listPriceListItems(id ?? ''), pricingApi.listPricingTiers()])
            .then(([itemResponse, tierResponse]) => {
                setItems(itemResponse.data);
                const itemIds = new Set(itemResponse.data.map((item) => item.id));
                setTiers(tierResponse.data.filter((tier) => itemIds.has(tier.priceListItemId)));
            })
            .catch((caught: unknown) => setTabError(caught instanceof Error ? caught.message : 'Unable to load pricing tiers.'))
            .finally(() => setLoadingTab(''));
    }, [id, loadingTab, tiers.length]);

    const loadActivity = useCallback(() => {
        if (activity.length > 0 || loadingTab) return;
        setLoadingTab('audit');
        setTabError('');
        pricingApi.getPricingActivity(id ?? '')
            .then((response) => setActivity(response.data))
            .catch((caught: unknown) => setTabError(caught instanceof Error ? caught.message : 'Unable to load pricing activity.'))
            .finally(() => setLoadingTab(''));
    }, [activity.length, id, loadingTab]);

    useEffect(() => {
        if (activeTab === 'items') loadItems();
        if (activeTab === 'rules') loadRules();
        if (activeTab === 'discounts') loadDiscountRules();
        if (activeTab === 'tiers') loadTiers();
        if (activeTab === 'audit') loadActivity();
    }, [activeTab, loadActivity, loadDiscountRules, loadItems, loadRules, loadTiers]);

    if (isLoading) return <EmptyState description="Loading price list detail..." title="Loading price list" />;
    if (error || !priceList || !usage) return <EmptyState description={error || 'Price list was not found.'} title="Unable to load price list" />;

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/pricing/price-lists"><Button variant="secondary">Back</Button></Link><Link to={`/pricing/price-lists/${priceList.id}/edit`}><Button>Edit Price List</Button></Link></>} eyebrow="Price List" subtitle="Detail workspace shows setup, links, rules, discounts, tiers, usage, and audit without resolving prices in frontend." title={priceList.name} />
            <PriceListSummaryCard onStatusChange={setPriceList} priceList={priceList} />
            <Card className="p-5"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} /></Card>
            {tabError ? <EmptyState description={tabError} title="Unable to load tab data" /> : null}
            {activeTab === 'overview' ? <div className="grid gap-5 xl:grid-cols-[1fr_340px]"><PreviewPanel rows={[{ label: 'Type', value: priceList.type }, { label: 'Module usage', value: priceList.moduleUsage.join(', ') }, { label: 'Party scope', value: priceList.isCustomerSpecific ? 'Customer-specific' : priceList.isSupplierSpecific ? 'Supplier-specific' : 'General' }]} title="Overview" /><PricingUsagePanel usage={usage} /></div> : null}
            {activeTab === 'items' ? loadingTab === 'items' ? <EmptyState description="Loading price list items..." title="Loading items" /> : <PriceListItemsManager items={items} onChanged={() => loadItems(true)} priceListId={priceList.id} /> : null}
            {activeTab === 'customers' ? <PreviewPanel rows={[{ label: 'Customer lists', value: 'Managed by backend/customer-price-list endpoints' }]} title="Customer Price Lists" /> : null}
            {activeTab === 'suppliers' ? <PreviewPanel rows={[{ label: 'Supplier lists', value: 'Managed by backend/supplier-price-list endpoints' }]} title="Supplier Price Lists" /> : null}
            {activeTab === 'rules' ? loadingTab === 'rules' ? <EmptyState description="Loading pricing rules..." title="Loading rules" /> : <PricingRuleConditionsTable conditions={conditions.filter((condition) => rules.some((rule) => rule.id === condition.ruleId))} /> : null}
            {activeTab === 'discounts' ? loadingTab === 'discounts' ? <EmptyState description="Loading discount rules..." title="Loading discounts" /> : <DiscountRulesTable rules={discountRules} /> : null}
            {activeTab === 'tiers' ? loadingTab === 'tiers' ? <EmptyState description="Loading pricing tiers..." title="Loading tiers" /> : <PricingTierTable tiers={tiers} /> : null}
            {activeTab === 'usage' ? <PricingUsagePanel usage={usage} /> : null}
            {activeTab === 'audit' ? loadingTab === 'audit' ? <EmptyState description="Loading pricing activity..." title="Loading activity" /> : <PricingActivityTimeline entries={activity} /> : null}
        </div>
    );
}
