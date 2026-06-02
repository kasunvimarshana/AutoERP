import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { DataToolbar, type DataToolbarFilterValues } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { JobCardTable, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceJobCard } from '../types/vehicleService.types';

export function JobCardListPage() {
    const [rows, setRows] = useState<VehicleServiceJobCard[]>([]);
    const [filters, setFilters] = useState<DataToolbarFilterValues>({});
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const timeout = window.setTimeout(() => setDebouncedSearch(search), 300);

        return () => window.clearTimeout(timeout);
    }, [search]);

    useEffect(() => {
        let active = true;
        setLoading(true);
        vehicleServiceApi.jobCards.list({
            search: debouncedSearch,
            status: typeof filters.status === 'string' ? filters.status : undefined,
        }).then((response) => {
            if (active) setRows(response.data);
        }).finally(() => {
            if (active) setLoading(false);
        });

        return () => {
            active = false;
        };
    }, [debouncedSearch, filters.status]);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<Link to="/vehicle-service/job-cards/new"><Button>New Job Card</Button></Link>}
                subtitle="Job cards are workshop records. Backend owns workflow state, pricing, UOM conversion, stock effects, invoice totals, payments, and finance posting."
                title="Job Cards"
            />
            <DataToolbar
                filterValues={filters}
                filters={[{ id: 'status', label: 'Status', options: [{ label: 'Open', value: 'open' }, { label: 'In progress', value: 'in_progress' }, { label: 'Waiting parts', value: 'waiting_parts' }, { label: 'Completed', value: 'completed' }, { label: 'Closed', value: 'closed' }], type: 'status' }]}
                isLoading={loading}
                onFilterChange={(key, value) => setFilters((current) => ({ ...current, [key]: value }))}
                onResetFilters={() => setFilters({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Vehicle Service lists."
                searchPlaceholder="Search job card, customer, vehicle, service advisor..."
                searchValue={search}
            />
            <JobCardTable rows={rows} />
        </div>
    );
}
