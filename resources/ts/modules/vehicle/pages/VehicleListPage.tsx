import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Select } from '../../../shared/components/ui/Select';
import { VehicleTable } from '../components/VehiclePanels';
import { vehicleApi } from '../services/vehicleApi';
import type { Vehicle, VehicleStatus } from '../types/vehicle.types';

const statusOptions: Array<{ label: string; value: VehicleStatus }> = [
    { label: 'Draft', value: 'draft' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'In service', value: 'in_service' },
    { label: 'In rental', value: 'in_rental' },
    { label: 'Under maintenance', value: 'under_maintenance' },
    { label: 'Unavailable', value: 'unavailable' },
    { label: 'Sold', value: 'sold' },
    { label: 'Archived', value: 'archived' },
];

function pageError(error: unknown, fallback: string) {
    if (error instanceof ApiError) {
        return error.message;
    }

    return error instanceof Error ? error.message : fallback;
}

export function VehicleListPage() {
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [rows, setRows] = useState<Vehicle[]>([]);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');

    useEffect(() => {
        let mounted = true;

        setIsLoading(true);
        setError('');
        vehicleApi
            .list({ search, status: status || undefined })
            .then((response) => {
                if (mounted) {
                    setRows(response.data);
                }
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setError(pageError(error, 'Unable to load vehicles.'));
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
    }, [search, status]);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/vehicles/new"><Button>New Vehicle</Button></Link>}
                eyebrow="Master Data"
                subtitle="Vehicles are shared master profiles. Ownership, service eligibility, and rental eligibility are backend-owned context."
                title="Vehicles"
            />

            <div className="grid gap-4 md:grid-cols-3">
                {[
                    ['Vehicle profiles', String(rows.length), 'Loaded from Vehicle API'],
                    ['Ownership model', 'Flexible', 'History-aware owner and provider context'],
                    ['Boundary', 'Core master', 'No service or rental workflow logic here'],
                ].map(([label, value, helper]) => (
                    <Card className="p-5" key={label}>
                        <p className="text-sm text-slate-500">{label}</p>
                        <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
                        <p className="mt-1 text-xs text-slate-400">{helper}</p>
                    </Card>
                ))}
            </div>

            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_220px]">
                    <SearchFilterBar onSearch={setSearch} placeholder="Search code, plate, VIN, make, model, category..." />
                    <Select onChange={(event) => setStatus(event.target.value)} options={statusOptions} placeholder="All statuses" value={status} />
                </div>
            </Card>

            {isLoading ? <EmptyState description="Loading vehicle profiles from the backend..." title="Loading vehicles" /> : null}
            {error ? <EmptyState description={error} title="Unable to load vehicles" /> : null}
            {!isLoading && !error && rows.length === 0 ? <EmptyState description="Create a vehicle profile to begin." title="No vehicles found" /> : null}
            {!isLoading && !error && rows.length > 0 ? <VehicleTable rows={rows} /> : null}
        </div>
    );
}
