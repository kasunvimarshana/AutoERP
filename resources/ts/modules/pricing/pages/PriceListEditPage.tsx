import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PriceListForm } from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { PriceList } from '../types/pricing.types';

export function PriceListEditPage() {
    const { id } = useParams();
    const [priceList, setPriceList] = useState<PriceList | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        pricingApi.getPriceList(id ?? '')
            .then((response) => { if (mounted) setPriceList(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load price list.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading price list..." title="Loading pricing" />;
    if (error || !priceList) return <EmptyState description={error || 'Price list not found.'} title="Unable to load price list" />;

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing" subtitle="Edit setup inputs only. Backend remains authoritative for price resolving and discounts." title={`Edit ${priceList.name}`} />
            <PriceListForm mode="edit" priceList={priceList} />
        </div>
    );
}
