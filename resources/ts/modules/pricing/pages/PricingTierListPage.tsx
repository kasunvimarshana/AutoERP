import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PricingTierManager } from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { PriceListItem, PricingTier } from '../types/pricing.types';

export function PricingTierListPage() {
    const [rows, setRows] = useState<PricingTier[]>([]);
    const [priceListItems, setPriceListItems] = useState<PriceListItem[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    function loadTiers() {
        let mounted = true;
        setIsLoading(true);
        Promise.all([pricingApi.listPricingTiers(), pricingApi.listPriceListItems()])
            .then(([tierResponse, itemResponse]) => {
                if (mounted) {
                    setRows(tierResponse.data);
                    setPriceListItems(itemResponse.data);
                }
            })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load pricing tiers.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }

    useEffect(() => {
        return loadTiers();
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing" subtitle="Tiers are maintained as resolver inputs. Frontend does not select or apply tier prices." title="Pricing Tiers" />
            {isLoading ? <EmptyState description="Loading pricing tiers..." title="Loading tiers" /> : null}
            {error ? <EmptyState description={error} title="Tier service unavailable" /> : null}
            {!isLoading && !error ? <PricingTierManager onChanged={loadTiers} priceListItems={priceListItems} rows={rows} /> : null}
        </div>
    );
}
