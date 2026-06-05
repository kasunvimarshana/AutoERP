import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
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

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        const next = typeof value === 'string' ? value : '';
        if (filterId === 'source') setSource(next);
        if (filterId === 'status') setStatus(next);
        if (filterId === 'ruleType') setRuleType(next);
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/pricing/rules/new"><Button>New Rule</Button></Link>} eyebrow="Pricing" subtitle="Rules describe resolver priority, conditions, discounts, and tiers. Backend evaluates every rule." title="Pricing Rules" />
            <DataToolbar
                filterValues={{ ruleType, source, status }}
                filters={[
                    { id: 'source', label: 'Source', options: [{ label: 'Sales', value: 'sales' }, { label: 'Purchase', value: 'purchase' }, { label: 'Vehicle Service', value: 'vehicle_service' }, { label: 'Vehicle Rental', value: 'vehicle_rental' }], placeholder: 'All sources', type: 'select' },
                    { id: 'status', label: 'Status', options: [{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }], placeholder: 'All statuses', type: 'status' },
                    { id: 'ruleType', label: 'Rule type', options: [{ label: 'Discount', value: 'discount' }, { label: 'Tier', value: 'tier' }, { label: 'Price resolve', value: 'price_resolve' }, { label: 'Generic', value: 'generic' }], placeholder: 'All rule types', type: 'select' },
                ]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={(filterId) => updateFilter(filterId, undefined)}
                onResetFilters={() => { setSource(''); setStatus(''); setRuleType(''); }}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views need a user-preferences backend before they can be enabled for pricing rules."
                searchPlaceholder="Search code, name, rule type, or source..."
                searchValue={query}
            />
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
