import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { uomApi } from '../services/uomApi';
import type { UomCategory, UomUnit } from '../types/uom.types';

export function UomUnitListPage() {
    const [units, setUnits] = useState<UomUnit[]>([]);
    const [categories, setCategories] = useState<UomCategory[]>([]);
    const [query, setQuery] = useState('');
    const [role, setRole] = useState('');
    const [type, setType] = useState('');
    const [status, setStatus] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        Promise.all([uomApi.listUnits(), uomApi.listCategories()])
            .then(([unitResponse, categoryResponse]) => {
                if (mounted) {
                    setUnits(unitResponse.data);
                    setCategories(categoryResponse.data);
                }
            })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load UOM units.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    const visibleUnits = useMemo(() => {
        const q = query.trim().toLowerCase();
        return units.filter((unit) => {
            const matchesQuery = q ? [unit.code, unit.name, unit.symbol, unit.category].some((value) => value.toLowerCase().includes(q)) : true;
            const matchesRole = role ? (role === 'base' ? unit.isBase : !unit.isBase) : true;
            return matchesQuery && matchesRole && (!type || unit.type === type) && (!status || unit.status === status);
        });
    }, [query, role, status, type, units]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        const next = typeof value === 'string' ? value : '';
        if (filterId === 'type') setType(next);
        if (filterId === 'status') setStatus(next);
        if (filterId === 'role') setRole(next);
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/uom/units/new"><Button>New Unit</Button></Link>} eyebrow="UOM" subtitle="Units support receipt, issue, consumption, charge, inventory, and pricing contexts without frontend conversion calculations." title="Units" />
            <div className="grid gap-4 md:grid-cols-3">
                {[
                    ['Units loaded', String(units.length), 'Backend records'],
                    ['Base units', String(units.filter((unit) => unit.isBase).length), 'Category base units'],
                    ['Conversion previews', 'API', 'No frontend quantity conversion'],
                ].map(([label, value, helper]) => <Card className="p-5" key={label}><p className="text-sm text-slate-500">{label}</p><p className="mt-2 text-2xl font-bold text-slate-950">{value}</p><p className="mt-1 text-xs text-slate-400">{helper}</p></Card>)}
            </div>
            <DataToolbar
                filterValues={{ role, status, type }}
                filters={[
                    { id: 'type', label: 'Category', options: categories.map((category) => ({ label: `${category.name} (${category.unitCount})`, value: category.type })), placeholder: 'All categories', type: 'select' },
                    { id: 'status', label: 'Status', options: [{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }], placeholder: 'All statuses', type: 'status' },
                    { id: 'role', label: 'Unit role', options: [{ label: 'Base units', value: 'base' }, { label: 'Conversion units', value: 'conversion' }], placeholder: 'All unit roles', type: 'select' },
                ]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={(filterId) => updateFilter(filterId, undefined)}
                onResetFilters={() => { setType(''); setStatus(''); setRole(''); }}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views need a user-preferences backend before they can be enabled for UOM lists."
                searchPlaceholder="Search unit code, name, symbol, or category..."
                searchValue={query}
            />
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
