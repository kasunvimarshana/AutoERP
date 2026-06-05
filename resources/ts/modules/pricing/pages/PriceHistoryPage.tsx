import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PricingHistoryTable } from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { PriceHistory } from '../types/pricing.types';

export function PriceHistoryPage() {
    const [rows, setRows] = useState<PriceHistory[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        pricingApi.listPriceHistory()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load price history.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing" subtitle="Readonly pricing history. Backend records authoritative price changes." title="Price History" />
            {isLoading ? <EmptyState description="Loading price history..." title="Loading history" /> : null}
            {error ? <EmptyState description={error} title="History service unavailable" /> : null}
            {!isLoading && !error ? <PricingHistoryTable history={rows} /> : null}
        </div>
    );
}
