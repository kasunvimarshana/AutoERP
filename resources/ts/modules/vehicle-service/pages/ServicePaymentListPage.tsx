import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { DataToolbar, type DataToolbarFilterValues } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { ServicePaymentPanel, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServicePayment } from '../types/vehicleService.types';

export function ServicePaymentListPage() {
    const [rows, setRows] = useState<VehicleServicePayment[]>([]);
    const [filters, setFilters] = useState<DataToolbarFilterValues>({});
    const [search, setSearch] = useState('');

    useEffect(() => {
        vehicleServiceApi.payments.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to="/vehicle-service/payments/new"><Button>Record Service Payment</Button></Link>}
                subtitle="Service payments are linked to VehicleService invoices/jobs. Backend owns allocation, balances, AR, refunds, and finance posting."
                title="Service Payments"
            />
            <DataToolbar
                filterValues={filters}
                filters={[{ id: 'status', label: 'Status', options: [{ label: 'Active', value: 'active' }, { label: 'Reversed', value: 'reversed' }, { label: 'Voided', value: 'voided' }], type: 'status' }]}
                onFilterChange={(key, value) => setFilters((current) => ({ ...current, [key]: value }))}
                onResetFilters={() => setFilters({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Vehicle Service lists."
                searchPlaceholder="Search payment, invoice, customer..."
                searchValue={search}
            />
            <ServicePaymentPanel payments={rows.filter((row) => {
                const matchesSearch = !search || `${row.paymentNumber} ${row.payer} ${row.sourceInvoice}`.toLowerCase().includes(search.toLowerCase());
                const status = typeof filters.status === 'string' ? filters.status : '';
                return matchesSearch && (!status || row.status === status);
            })} />
        </div>
    );
}

export { ServicePaymentListPage as ServicePaymentsPage };
