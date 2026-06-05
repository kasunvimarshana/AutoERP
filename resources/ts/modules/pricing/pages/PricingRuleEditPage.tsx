import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PricingRuleForm } from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { PricingRule } from '../types/pricing.types';

export function PricingRuleEditPage() {
    const { id } = useParams();
    const [rule, setRule] = useState<PricingRule | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        pricingApi.getPricingRule(id ?? '')
            .then((response) => { if (mounted) setRule(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load pricing rule.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading pricing rule..." title="Loading rule" />;
    if (error || !rule) return <EmptyState description={error || 'Pricing rule not found.'} title="Unable to load rule" />;

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing Rule" subtitle="Edit setup inputs only. Backend remains authoritative for resolver results." title={`Edit ${rule.name}`} />
            <PricingRuleForm mode="edit" rule={rule} />
        </div>
    );
}
