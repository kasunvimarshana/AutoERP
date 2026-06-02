import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { supplierApi } from '../services/supplierApi';
import type { Supplier, SupplierStatus } from '../types/supplier.types';
import { SupplierStatusBadge } from '../components/SupplierStatusBadge';

export function SupplierListPage() {
    const [suppliers, setSuppliers] = useState<Supplier[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');

    useEffect(() => {
        let mounted = true;
        const timeout = window.setTimeout(() => {
            setIsLoading(true);
            setError('');

            supplierApi
                .listSuppliers({ search: query, status: status ? (status as SupplierStatus) : undefined })
                .then((response) => {
                    if (mounted) {
                        setSuppliers(response.data);
                    }
                })
                .catch((caught: unknown) => {
                    if (mounted) {
                        setError(caught instanceof Error ? caught.message : 'Unable to load suppliers.');
                    }
                })
                .finally(() => {
                    if (mounted) {
                        setIsLoading(false);
                    }
                });
        }, 250);

        return () => {
            mounted = false;
            window.clearTimeout(timeout);
        };
    }, [query, status]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        const next = typeof value === 'string' ? value : '';
        if (filterId === 'status') setStatus(next);
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <Link to="/suppliers/new">
                        <Button>New Supplier</Button>
                    </Link>
                }
                eyebrow="Master Data"
                subtitle="Suppliers are reusable payee/provider profiles. User access is optional and managed separately."
                title="Suppliers"
            />
            <div className="grid gap-4 md:grid-cols-3">
                {[
                    ['Suppliers loaded', String(suppliers.length), 'Backend records'],
                    ['Optional user links', String(suppliers.filter((supplier) => supplier.userAccessStatus === 'linked').length), 'Supplier login is not automatic'],
                    ['Backend finance previews', 'Readonly', 'No frontend payable calculations'],
                ].map(([label, value, helper]) => (
                    <Card className="p-5" key={label}>
                        <p className="text-sm text-slate-500">{label}</p>
                        <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
                        <p className="mt-1 text-xs text-slate-400">{helper}</p>
                    </Card>
                ))}
            </div>
            <DataToolbar
                filterValues={{ status }}
                filters={[
                    { id: 'status', label: 'Status', options: [
                        { label: 'Active', value: 'active' },
                        { label: 'Inactive', value: 'inactive' },
                        { label: 'Blocked', value: 'blocked' },
                        { label: 'Draft', value: 'draft' },
                        { label: 'Pending Approval', value: 'pending_approval' },
                    ], placeholder: 'All statuses', type: 'status' },
                ]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={(filterId) => updateFilter(filterId, undefined)}
                onResetFilters={() => { setStatus(''); }}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views need a user-preferences backend before they can be enabled for supplier lists."
                searchPlaceholder="Search supplier code, name, email, phone, tax/VAT number..."
                searchValue={query}
            />
            {isLoading ? <EmptyState description="Loading suppliers from the Supplier service..." title="Loading suppliers" /> : null}
            {error ? <EmptyState description={error} title="Supplier service unavailable" /> : null}
            {!isLoading && !error && suppliers.length === 0 ? <EmptyState description="Create a supplier profile without linking a user account." title="No suppliers found" /> : null}
            {!isLoading && !error && suppliers.length > 0 ? (
                <DataTable
                    columns={[
                        { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/suppliers/${row.id}`}>{row.code}</Link> },
                        { header: 'Supplier', key: 'name' },
                        { header: 'Category', key: 'category' },
                        { header: 'Email', key: 'email' },
                        { header: 'Phone', key: 'phone' },
                        { header: 'Tax / VAT', key: 'taxNumber', render: (row) => row.vatNumber || row.taxNumber || 'Not provided' },
                        { header: 'Currency', key: 'defaultCurrency' },
                        { header: 'Status', key: 'status', render: (row) => <SupplierStatusBadge status={row.status as SupplierStatus} /> },
                        { header: 'User', key: 'userAccessStatus', render: (row) => <StatusBadge status={row.userAccessStatus === 'linked' ? 'Linked' : 'Optional'} /> },
                        {
                            header: 'Actions',
                            key: 'actions',
                            render: (row) => (
                                <div className="flex flex-wrap gap-2">
                                    <Link className="font-semibold text-slate-950" to={`/suppliers/${row.id}`}>View</Link>
                                    <Link className="font-semibold text-slate-500" to={`/suppliers/${row.id}/edit`}>Edit</Link>
                                </div>
                            ),
                        },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={suppliers}
                />
            ) : null}
        </div>
    );
}
