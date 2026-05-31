import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Select } from '../../../shared/components/ui/Select';
import { UomConversionTable } from '../components/UomComponents';
import { uomApi } from '../services/uomApi';
import type { UomConversion } from '../types/uom.types';

export function UomConversionListPage() {
    const [conversions, setConversions] = useState<UomConversion[]>([]);
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState('');
    const [status, setStatus] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        uomApi.listConversions()
            .then((response) => { if (mounted) setConversions(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load conversions.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    const visibleConversions = useMemo(() => {
        const q = query.trim().toLowerCase();
        return conversions.filter((conversion) => {
            const matchesQuery = q ? [conversion.fromUnitCode, conversion.toUnitCode, conversion.factor, conversion.category].some((value) => value.toLowerCase().includes(q)) : true;
            const matchesCategory = category ? conversion.category === category : true;
            const matchesStatus = status ? (status === 'active' ? conversion.isActive : !conversion.isActive) : true;
            return matchesQuery && matchesCategory && matchesStatus;
        });
    }, [category, conversions, query, status]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/uom/conversions/new"><Button>New Conversion</Button></Link>} eyebrow="UOM" subtitle="Conversions describe compatible unit relationships. Backend applies factors and rounding." title="Conversions" />
            <SearchFilterBar onSearch={setQuery} placeholder="Search from unit, to unit, factor, category..." />
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
                <Select onChange={(event) => setCategory(event.target.value)} options={[{ label: 'Unit / Count', value: 'UNIT' }, { label: 'Volume', value: 'VOLUME' }, { label: 'Mass', value: 'MASS' }, { label: 'Time', value: 'TIME' }, { label: 'Distance', value: 'DISTANCE' }]} placeholder="All categories" value={category} />
                <Select onChange={(event) => setStatus(event.target.value)} options={[{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }]} placeholder="All statuses" value={status} />
                <Select options={[{ label: 'General', value: 'general' }, { label: 'Item-specific', value: 'item' }]} placeholder="General / item-specific" />
            </div>
            {isLoading ? <EmptyState description="Loading conversions..." title="Loading conversions" /> : null}
            {error ? <EmptyState description={error} title="Conversion service unavailable" /> : null}
            {!isLoading && !error && !visibleConversions.length ? <EmptyState description="Create the first conversion. Preview results come from backend." title="No conversions found" /> : null}
            {!isLoading && !error && visibleConversions.length ? <UomConversionTable conversions={visibleConversions} /> : null}
        </div>
    );
}
