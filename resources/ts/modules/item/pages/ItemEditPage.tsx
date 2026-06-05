import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { ItemForm } from '../components/ItemForms';
import { itemApi } from '../services/itemApi';
import type { Item } from '../types/item.types';

export function ItemEditPage() {
    const { id } = useParams();
    const [item, setItem] = useState<Item | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        itemApi.getItem(id ?? '')
            .then((response) => { if (mounted) setItem(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load item.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading item setup..." title="Loading item" />;
    if (error || !item) return <EmptyState description={error || 'Item was not found.'} title="Unable to edit item" />;

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Core Master Data" subtitle="Edit permitted setup fields. Backend validates UOM, stock behavior, tax, and finance references." title={`Edit ${item.name}`} />
            <ItemForm item={item} mode="edit" />
        </div>
    );
}
