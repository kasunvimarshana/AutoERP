import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { UomConversionForm } from '../components/UomComponents';
import { uomApi } from '../services/uomApi';
import type { UomConversion } from '../types/uom.types';

export function UomConversionEditPage() {
    const { id } = useParams();
    const [conversion, setConversion] = useState<UomConversion | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        uomApi.getConversion(id ?? '')
            .then((response) => { if (mounted) setConversion(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load conversion.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading conversion..." title="Loading conversion" />;
    if (error || !conversion) return <EmptyState description={error || 'Conversion was not found.'} title="Unable to edit conversion" />;

    return <div className="space-y-6"><PageHeader eyebrow="UOM" subtitle="Edit conversion inputs. Backend remains authoritative for converted quantities." title={`Edit ${conversion.fromUnitCode} to ${conversion.toUnitCode}`} /><UomConversionForm conversion={conversion} mode="edit" /></div>;
}
