import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { DiscountRulesTable, PricingActivityTimeline, PricingRuleConditionsTable, PricingUsagePanel } from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { DiscountRule, PricingAuditEntry, PricingRule, PricingRuleCondition, PricingUsageSummary } from '../types/pricing.types';

const tabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Conditions', value: 'conditions' },
    { label: 'Applied Discounts', value: 'discounts' },
    { label: 'Usage', value: 'usage' },
    { label: 'Audit / History', value: 'audit' },
];

export function PricingRuleDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [rule, setRule] = useState<PricingRule | null>(null);
    const [conditions, setConditions] = useState<PricingRuleCondition[]>();
    const [discountRules, setDiscountRules] = useState<DiscountRule[]>();
    const [usage, setUsage] = useState<PricingUsageSummary>();
    const [activity, setActivity] = useState<PricingAuditEntry[]>();
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        const ruleId = id ?? '';
        setIsLoading(true);
        setRule(null);
        setConditions(undefined);
        setDiscountRules(undefined);
        setUsage(undefined);
        setActivity(undefined);
        pricingApi.getPricingRule(ruleId)
            .then((ruleResponse) => {
                if (mounted) {
                    setRule(ruleResponse.data);
                    setError('');
                }
            })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load pricing rule detail.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    useEffect(() => {
        if (!rule) {
            return undefined;
        }

        let mounted = true;

        if (activeTab === 'conditions' && conditions === undefined) {
            pricingApi.listPricingRuleConditions(rule.id).then((response) => mounted && setConditions(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load rule conditions.'));
        }

        if (activeTab === 'discounts' && discountRules === undefined) {
            pricingApi.listDiscountRules().then((response) => mounted && setDiscountRules(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load discount rules.'));
        }

        if (activeTab === 'usage' && usage === undefined) {
            pricingApi.getPricingUsage(rule.id, 'rule').then((response) => mounted && setUsage(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load pricing usage.'));
        }

        if (activeTab === 'audit' && activity === undefined) {
            pricingApi.getPricingActivity(rule.id).then((response) => mounted && setActivity(response.data)).catch((caught: unknown) => mounted && setError(caught instanceof Error ? caught.message : 'Unable to load pricing activity.'));
        }

        return () => {
            mounted = false;
        };
    }, [activeTab, activity, conditions, discountRules, rule, usage]);

    if (isLoading) return <EmptyState description="Loading pricing rule detail..." title="Loading rule" />;
    if (error || !rule) return <EmptyState description={error || 'Pricing rule was not found.'} title="Unable to load pricing rule" />;

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/pricing/rules"><Button variant="secondary">Back</Button></Link><Link to={`/pricing/rules/${rule.id}/edit`}><Button>Edit Rule</Button></Link></>} eyebrow="Pricing Rule" subtitle="Rule detail shows conditions, usage, and audit. Backend evaluates the rule during preview/posting." title={rule.name} />
            <Card className="p-5">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex flex-wrap items-center gap-2"><p className="text-xs font-bold uppercase tracking-widest text-slate-400">{rule.code}</p><StatusBadge status={rule.status} /><StatusBadge status={rule.sourceType} /></div>
                        <p className="mt-2 max-w-3xl text-sm text-slate-500">{rule.description}</p>
                    </div>
                    <PreviewPanel rows={[{ label: 'Priority', value: rule.priority }, { label: 'Action', value: rule.actionType }, { label: 'Stacking', value: rule.isExclusive ? 'Exclusive' : rule.isStackable ? 'Stackable' : 'Single' }]} title="Rule setup" />
                </div>
            </Card>
            <Card className="p-5"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? <PreviewPanel rows={[{ label: 'Rule type', value: rule.ruleType }, { label: 'Action value', value: rule.actionValue }, { label: 'Valid', value: `${rule.validFrom} to ${rule.validTo}` }]} title="Overview" /> : null}
            {activeTab === 'conditions' ? conditions ? <PricingRuleConditionsTable conditions={conditions} /> : <EmptyState description="Loading pricing rule conditions..." title="Loading conditions" /> : null}
            {activeTab === 'discounts' ? discountRules ? <DiscountRulesTable rules={discountRules} /> : <EmptyState description="Loading applied discount rules..." title="Loading discounts" /> : null}
            {activeTab === 'usage' ? usage ? <PricingUsagePanel usage={usage} /> : <EmptyState description="Loading backend usage summary..." title="Loading usage" /> : null}
            {activeTab === 'audit' ? activity ? <PricingActivityTimeline entries={activity} /> : <EmptyState description="Loading pricing activity..." title="Loading activity" /> : null}
        </div>
    );
}
