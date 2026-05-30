import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Select } from '../../../shared/components/ui/Select';
import { uomApi } from '../services/uomApi';
import type { UomUnit } from '../types/uom.types';

export function UomUnitListPage() {
    const [units, setUnits] = useState<UomUnit[]>([]);
    const [query, setQuery] = useState('');
    const [type, setType] = useState('');
    const [status, setStatus] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        uomApi.listUnits()
            .then((response) => { if (mounted) setUnits(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load UOM units.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    const visibleUnits = useMemo(() => {
        const q = query.trim().toLowerCase();
        return units.filter((unit) => {
            const matchesQuery = q ? [unit.code, unit.name, unit.symbol, unit.category].some((value) => value.toLowerCase().includes(q)) : true;
            return matchesQuery && (!type || unit.type === type) && (!status || unit.status === status);
        });
    }, [query, status, type, units]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/uom/units/new"><Button>New Unit</Button></Link>} eyebrow="UOM" subtitle="Units support receipt, issue, consumption, charge, inventory, and pricing contexts without frontend conversion calculations." title="Units" />
            <div className="grid gap-4 md:grid-cols-3">
                {[
                    ['Units loaded', String(units.length), 'Mock or backend-normalized records'],
                    ['Base units', String(units.filter((unit) => unit.isBase).length), 'Category base units'],
                    ['Conversion logic', 'Backend-owned', 'No frontend quantity conversion'],
                ].map(([label, value, helper]) => <Card className="p-5" key={label}><p className="text-sm text-slate-500">{label}</p><p className="mt-2 text-2xl font-bold text-slate-950">{value}</p><p className="mt-1 text-xs text-slate-400">{helper}</p></Card>)}
            </div>
            <SearchFilterBar onSearch={setQuery} placeholder="Search unit code, name, symbol, category..." />
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
                <Select onChange={(event) => setType(event.target.value)} options={[{ label: 'Count', value: 'count' }, { label: 'Volume', value: 'volume' }, { label: 'Mass', value: 'mass' }, { label: 'Duration', value: 'duration' }, { label: 'Distance', value: 'distance' }]} placeholder="All categories" value={type} />
                <Select onChange={(event) => setStatus(event.target.value)} options={[{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }]} placeholder="All statuses" value={status} />
                <Select options={[{ label: 'Base units', value: 'base' }, { label: 'Conversion units', value: 'conversion' }]} placeholder="All unit types" />
            </div>
            {isLoading ? <EmptyState description="Loading UOM units..." title="Loading units" /> : null}
            {error ? <EmptyState description={error} title="UOM service unavailable" /> : null}
            {!isLoading && !error && !visibleUnits.length ? <EmptyState description="Create the first unit and define conversion behavior separately." title="No UOM units found" /> : null}
            {!isLoading && !error && visibleUnits.length ? (
                <DataTable columns={[
                    { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/uom/units/${row.id}`}>{row.code}</Link> },
                    { header: 'Name', key: 'name' },
                    { header: 'Category', key: 'category' },
                    { header: 'Symbol', key: 'symbol' },
                    { header: 'Precision', key: 'precision' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                    { header: 'Updated', key: 'updatedAt' },
                    { header: 'Actions', key: 'actions', render: (row) => <div className="flex gap-2"><Link className="font-semibold text-slate-950" to={`/uom/units/${row.id}`}>View</Link><Link className="font-semibold text-slate-500" to={`/uom/units/${row.id}/edit`}>Edit</Link></div> },
                ]} getRowKey={(row) => row.id} rows={visibleUnits} />
            ) : null}
        </div>
    );
}
