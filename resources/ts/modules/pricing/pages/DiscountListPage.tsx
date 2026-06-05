import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { DiscountManager } from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { Discount } from '../types/pricing.types';

export function DiscountListPage() {
    const [rows, setRows] = useState<Discount[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    function loadDiscounts() {
        let mounted = true;
        setIsLoading(true);
        pricingApi.listDiscounts()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load discounts.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }

    useEffect(() => {
        return loadDiscounts();
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing" subtitle="Discount definitions are configured here, but discount amount previews and final application are backend-owned." title="Discounts" />
            {isLoading ? <EmptyState description="Loading discounts..." title="Loading discounts" /> : null}
            {error ? <EmptyState description={error} title="Discount service unavailable" /> : null}
            {!isLoading && !error ? <DiscountManager onChanged={loadDiscounts} rows={rows} /> : null}
        </div>
    );
}
