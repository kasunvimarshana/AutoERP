import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Select } from '../../../shared/components/ui/Select';
import { PricingRuleRowActions } from '../components/PricingComponents';
import { pricingApi } from '../services/pricingApi';
import type { PricingRule } from '../types/pricing.types';

export function PricingRuleListPage() {
    const [rows, setRows] = useState<PricingRule[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [source, setSource] = useState('');
    const [ruleType, setRuleType] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        pricingApi.listPricingRules()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load pricing rules.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    const visibleRows = useMemo(() => {
        const q = query.trim().toLowerCase();
        return rows.filter((row) => {
            const matchesQuery = q ? [row.code, row.name, row.ruleType, row.sourceType].some((value) => value.toLowerCase().includes(q)) : true;
            return matchesQuery && (!status || row.status === status) && (!source || row.sourceType === source) && (!ruleType || row.ruleType === ruleType);
        });
    }, [query, rows, ruleType, source, status]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/pricing/rules/new"><Button>New Rule</Button></Link>} eyebrow="Pricing" subtitle="Rules describe resolver priority, conditions, discounts, and tiers. Backend evaluates every rule." title="Pricing Rules" />
            <SearchFilterBar onSearch={setQuery} />
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
                <Select onChange={(event) => setSource(event.target.value)} options={[{ label: 'All sources', value: '' }, { label: 'Sales', value: 'sales' }, { label: 'Purchase', value: 'purchase' }, { label: 'Vehicle Service', value: 'vehicle_service' }, { label: 'Vehicle Rental', value: 'vehicle_rental' }]} value={source} />
                <Select onChange={(event) => setStatus(event.target.value)} options={[{ label: 'All statuses', value: '' }, { label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }]} value={status} />
                <Select onChange={(event) => setRuleType(event.target.value)} options={[{ label: 'All rule types', value: '' }, { label: 'Discount', value: 'discount' }, { label: 'Tier', value: 'tier' }, { label: 'Price resolve', value: 'price_resolve' }, { label: 'Generic', value: 'generic' }]} value={ruleType} />
            </div>
            {isLoading ? <EmptyState description="Loading pricing rules..." title="Loading rules" /> : null}
            {error ? <EmptyState description={error} title="Pricing rule service unavailable" /> : null}
            {!isLoading && !error && visibleRows.length ? (
                <DataTable
                    columns={[
                        { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/pricing/rules/${row.id}`}>{row.code}</Link> },
                        { header: 'Name', key: 'name' },
                        { header: 'Type', key: 'ruleType' },
                        { header: 'Source', key: 'sourceType' },
                        { header: 'Priority', key: 'priority' },
                        { header: 'Action', key: 'actionType' },
                        { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                        { header: 'Actions', key: 'actions', render: (row) => <PricingRuleRowActions onChanged={() => pricingApi.listPricingRules().then((response) => setRows(response.data))} rule={row} /> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={visibleRows}
                />
            ) : null}
            {!isLoading && !error && !visibleRows.length ? <EmptyState description="Create a rule or adjust filters." title="No pricing rules found" /> : null}
        </div>
    );
}
