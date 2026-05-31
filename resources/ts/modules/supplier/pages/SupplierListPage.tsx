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
import { supplierApi } from '../services/supplierApi';
import type { Supplier, SupplierStatus } from '../types/supplier.types';
import { SupplierStatusBadge } from '../components/SupplierStatusBadge';

export function SupplierListPage() {
    const [suppliers, setSuppliers] = useState<Supplier[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [category, setCategory] = useState('');

    useEffect(() => {
        let mounted = true;

        supplierApi
            .listSuppliers()
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

        return () => {
            mounted = false;
        };
    }, []);

    const visibleSuppliers = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();

        return suppliers.filter((supplier) => {
            const matchesQuery = normalizedQuery
                ? [supplier.code, supplier.name, supplier.email, supplier.phone, supplier.taxNumber, supplier.vatNumber].some((value) => (value ?? '').toLowerCase().includes(normalizedQuery))
                : true;
            const matchesStatus = status ? supplier.status === status : true;
            const matchesCategory = category ? supplier.category === category : true;

            return matchesQuery && matchesStatus && matchesCategory;
        });
    }, [category, query, status, suppliers]);

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
            <div className="space-y-3">
                <SearchFilterBar onSearch={setQuery} placeholder="Search supplier code, name, email, phone, tax/VAT number..." />
                <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
                    <Select
                        onChange={(event) => setStatus(event.target.value)}
                        options={[
                            { label: 'Active', value: 'active' },
                            { label: 'Inactive', value: 'inactive' },
                            { label: 'Blocked', value: 'blocked' },
                            { label: 'Draft', value: 'draft' },
                            { label: 'Pending Approval', value: 'pending_approval' },
                        ]}
                        placeholder="All statuses"
                        value={status}
                    />
                    <Select
                        onChange={(event) => setCategory(event.target.value)}
                        options={[
                            { label: 'Parts Supplier', value: 'Parts Supplier' },
                            { label: 'External Service Provider', value: 'External Service Provider' },
                            { label: 'Fleet Provider', value: 'Fleet Provider' },
                        ]}
                        placeholder="All categories"
                        value={category}
                    />
                    <Select
                        onChange={(event) => setStatus(event.target.value)}
                        options={[
                            { label: 'Active only', value: 'active' },
                            { label: 'Inactive only', value: 'inactive' },
                            { label: 'Blocked only', value: 'blocked' },
                        ]}
                        placeholder="Active / inactive / blocked"
                        value={['active', 'inactive', 'blocked'].includes(status) ? status : ''}
                    />
                </div>
            </div>
            {isLoading ? <EmptyState description="Loading suppliers from the Supplier service..." title="Loading suppliers" /> : null}
            {error ? <EmptyState description={error} title="Supplier service unavailable" /> : null}
            {!isLoading && !error && visibleSuppliers.length === 0 ? <EmptyState description="Create a supplier profile without linking a user account." title="No suppliers found" /> : null}
            {!isLoading && !error && visibleSuppliers.length > 0 ? (
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
                    rows={visibleSuppliers}
                />
            ) : null}
        </div>
    );
}
