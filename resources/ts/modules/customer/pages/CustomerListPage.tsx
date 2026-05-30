import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { customerApi } from '../services/customerApi';
import type { Customer } from '../types/customer.types';
import { CustomerStatusBadge } from '../components/CustomerStatusBadge';

export function CustomerListPage() {
    const [customers, setCustomers] = useState<Customer[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;

        customerApi
            .listCustomers()
            .then((response) => {
                if (mounted) {
                    setCustomers(response.data);
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load customers.');
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

    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <Link to="/customers/new">
                        <Button>New Customer</Button>
                    </Link>
                }
                eyebrow="Master Data"
                subtitle="Customers are reusable billing and relationship profiles. User access is optional and managed separately."
                title="Customers"
            />
            <div className="grid gap-4 md:grid-cols-3">
                {[
                    ['Active customers', '2', 'Mock master-data count'],
                    ['Optional user links', '1', 'Customer login is not automatic'],
                    ['Backend credit previews', '3', 'Readonly mock values'],
                ].map(([label, value, helper]) => (
                    <Card className="p-5" key={label}>
                        <p className="text-sm text-slate-500">{label}</p>
                        <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
                        <p className="mt-1 text-xs text-slate-400">{helper}</p>
                    </Card>
                ))}
            </div>
            <SearchFilterBar placeholder="Search customer code, name, phone, email, tax number..." />
            {isLoading ? <EmptyState description="Loading customers from the Customer service..." title="Loading customers" /> : null}
            {error ? <EmptyState description={error} title="Customer service unavailable" /> : null}
            {!isLoading && !error && customers.length === 0 ? <EmptyState description="Create your first customer without linking a user account." title="No customers found" /> : null}
            {!isLoading && !error && customers.length > 0 ? (
                <DataTable
                    columns={[
                        { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/customers/${row.id}`}>{row.code}</Link> },
                        { header: 'Customer', key: 'name' },
                        { header: 'Contact', key: 'contactPerson' },
                        { header: 'Phone', key: 'phone' },
                        { header: 'User Access', key: 'userAccessStatus', render: (row) => <StatusBadge status={row.userAccessStatus === 'linked' ? 'Linked' : 'Optional'} /> },
                        { header: 'Status', key: 'status', render: (row) => <CustomerStatusBadge status={row.status} /> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={customers}
                />
            ) : null}
        </div>
    );
}
