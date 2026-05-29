import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { UomUnitForm } from '../components/UomComponents';
import { uomApi } from '../services/uomApi';
import type { UomUnit } from '../types/uom.types';

export function UomUnitEditPage() {
    const { id } = useParams();
    const [unit, setUnit] = useState<UomUnit | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        uomApi.getUnit(id ?? '')
            .then((response) => { if (mounted) setUnit(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load unit.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading UOM unit..." title="Loading unit" />;
    if (error || !unit) return <EmptyState description={error || 'Unit was not found.'} title="Unable to edit unit" />;

    return <div className="space-y-6"><PageHeader eyebrow="UOM" subtitle="Edit unit setup. Backend remains authoritative for conversion and rounding effects." title={`Edit ${unit.code}`} /><UomUnitForm mode="edit" unit={unit} /></div>;
}
