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
import { PriceListRowActions } from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { PriceList } from '../types/pricing.types';

export function PriceListPage() {
    const [rows, setRows] = useState<PriceList[]>([]);
    const [query, setQuery] = useState('');
    const [type, setType] = useState('');
    const [status, setStatus] = useState('');
    const [currency, setCurrency] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        pricingApi.listPriceLists()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load price lists.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    const visibleRows = useMemo(() => {
        const q = query.trim().toLowerCase();
        return rows.filter((row) => {
            const matchesQuery = q ? [row.code, row.name, row.currency, row.type].some((value) => value.toLowerCase().includes(q)) : true;
            return matchesQuery && (!type || row.type === type) && (!status || row.status === status) && (!currency || row.currency === currency);
        });
    }, [currency, query, rows, status, type]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/pricing/price-lists/new"><Button>New Price List</Button></Link>} eyebrow="Pricing" subtitle="Price lists can be sales, purchase, service, rental, customer-specific, or supplier-specific. Resolver priority stays backend-owned." title="Price Lists" />
            <div className="grid gap-4 md:grid-cols-3">
                <Card className="p-5"><p className="text-sm text-slate-500">Loaded lists</p><p className="mt-2 text-2xl font-bold text-slate-950">{rows.length}</p></Card>
                <Card className="p-5"><p className="text-sm text-slate-500">Active lists</p><p className="mt-2 text-2xl font-bold text-slate-950">{rows.filter((row) => row.status === 'active').length}</p></Card>
                <Card className="p-5"><p className="text-sm text-slate-500">Price resolving</p><p className="mt-2 text-2xl font-bold text-slate-950">Backend-owned</p></Card>
            </div>
            <SearchFilterBar onSearch={setQuery} placeholder="Search code, name, currency, type..." />
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-4">
                <Select onChange={(event) => setType(event.target.value)} options={[{ label: 'Sales', value: 'sales' }, { label: 'Purchase', value: 'purchase' }, { label: 'Customer', value: 'customer' }, { label: 'Supplier', value: 'supplier' }, { label: 'Service', value: 'service' }, { label: 'Rental', value: 'rental' }]} placeholder="All types" value={type} />
                <Select onChange={(event) => setCurrency(event.target.value)} options={[{ label: 'LKR', value: 'LKR' }, { label: 'USD', value: 'USD' }]} placeholder="All currencies" value={currency} />
                <Select onChange={(event) => setStatus(event.target.value)} options={[{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }, { label: 'Draft', value: 'draft' }]} placeholder="All statuses" value={status} />
                <Select options={[{ label: 'Current validity', value: 'current' }, { label: 'Expiring soon', value: 'expiring' }]} placeholder="All valid dates" />
            </div>
            {isLoading ? <EmptyState description="Loading price lists..." title="Loading pricing" /> : null}
            {error ? <EmptyState description={error} title="Pricing service unavailable" /> : null}
            {!isLoading && !error && !visibleRows.length ? <EmptyState description="Create a price list or adjust filters." title="No price lists found" /> : null}
            {!isLoading && !error && visibleRows.length ? (
                <DataTable
                    columns={[
                        { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/pricing/price-lists/${row.id}`}>{row.code}</Link> },
                        { header: 'Name', key: 'name' },
                        { header: 'Type', key: 'type' },
                        { header: 'Currency', key: 'currency' },
                        { header: 'Valid From', key: 'validFrom' },
                        { header: 'Valid To', key: 'validTo' },
                        { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                        { header: 'Updated', key: 'updatedAt' },
                        { header: 'Actions', key: 'actions', render: (row) => <PriceListRowActions priceList={row} /> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={visibleRows}
                />
            ) : null}
        </div>
    );
}
