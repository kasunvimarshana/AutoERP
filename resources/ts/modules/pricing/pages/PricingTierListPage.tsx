import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PricingTierForm, PricingTierTable } from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { PricingTier } from '../types/pricing.types';

export function PricingTierListPage() {
    const [rows, setRows] = useState<PricingTier[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        pricingApi.listPricingTiers()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load pricing tiers.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing" subtitle="Tiers are maintained as resolver inputs. Frontend does not select or apply tier prices." title="Pricing Tiers" />
            <PricingTierForm />
            {isLoading ? <EmptyState description="Loading pricing tiers..." title="Loading tiers" /> : null}
            {error ? <EmptyState description={error} title="Tier service unavailable" /> : null}
            {!isLoading && !error ? <PricingTierTable tiers={rows} /> : null}
        </div>
    );
}
