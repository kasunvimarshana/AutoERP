import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { DataToolbar, type DataToolbarFilterValues } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { ServiceInvoiceTable, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceInvoice } from '../types/vehicleService.types';

export function ServiceInvoiceListPage() {
    const [rows, setRows] = useState<VehicleServiceInvoice[]>([]);
    const [filters, setFilters] = useState<DataToolbarFilterValues>({});
    const [search, setSearch] = useState('');

    useEffect(() => {
        vehicleServiceApi.invoices.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to="/vehicle-service/job-cards"><Button>Invoiceable Jobs</Button></Link>}
                subtitle="Service invoices are generated from job cards. Backend owns totals, tax, discounts, documents, AR, and posting."
                title="Service Invoices"
            />
            <DataToolbar
                filterValues={filters}
                filters={[{ id: 'status', label: 'Status', options: [{ label: 'Invoiced', value: 'invoiced' }, { label: 'Pending', value: 'pending' }, { label: 'Reversed', value: 'reversed' }], type: 'status' }]}
                onFilterChange={(key, value) => setFilters((current) => ({ ...current, [key]: value }))}
                onResetFilters={() => setFilters({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Vehicle Service lists."
                searchPlaceholder="Search invoice, job card, customer..."
                searchValue={search}
            />
            <ServiceInvoiceTable rows={rows.filter((row) => {
                const matchesSearch = !search || `${row.invoiceNumber} ${row.jobCardNumber} ${row.billingCustomer}`.toLowerCase().includes(search.toLowerCase());
                const status = typeof filters.status === 'string' ? filters.status : '';
                return matchesSearch && (!status || row.status === status);
            })} />
        </div>
    );
}

export { ServiceInvoiceListPage as ServiceInvoicesPage };
