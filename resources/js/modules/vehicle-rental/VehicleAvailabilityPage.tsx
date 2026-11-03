import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { fetchAvailableVehicles, type AvailableVehicleOption } from './vehicleUseApi';
import { operationalTimeZoneLabel, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset } from './vehicleUse';

export default function VehicleAvailabilityPage() {
    const navigate = useNavigate();
    const today = businessDateInputValue();
    const [startsAt, setStartsAt] = useState(`${today}T08:00`);
    const [endsAt, setEndsAt] = useState(`${today}T18:00`);
    const [searchTerm, setSearchTerm] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [vehicles, setVehicles] = useState<AvailableVehicleOption[]>([]);
    const [searched, setSearched] = useState(false);

    async function handleCheck(event: FormEvent) {
        event.preventDefault();
        if (!startsAt) return;
        setLoading(true);
        setError(null);
        try {
            const startParam = timestampWithOffset(startsAt);
            const endParam = endsAt ? timestampWithOffset(endsAt) : null;
            const data = await fetchAvailableVehicles(startParam, endParam);
            setVehicles(data);
            setSearched(true);
        } catch (err) {
            setError(toApiError(err));
            setVehicles([]);
        } finally {
            setLoading(false);
        }
    }

    const filteredVehicles = searchTerm.trim() === ''
        ? vehicles
        : vehicles.filter(v => {
            const term = searchTerm.toLowerCase();
            return (v.display_label?.toLowerCase().includes(term) ||
                v.vehicle_number?.toLowerCase().includes(term) ||
                (v.registration_number && v.registration_number.toLowerCase().includes(term)) ||
                (v.make && v.make.toLowerCase().includes(term)) ||
                (v.model && v.model.toLowerCase().includes(term)) ||
                (v.category && v.category.toLowerCase().includes(term)));
        });

    const columns: DataColumn<AvailableVehicleOption>[] = [
        {
            key: 'label',
            header: 'Vehicle',
            render: row => (
                <div>
                    <span className="font-semibold text-slate-900">{row.display_label}</span>
                    {row.registration_number && row.registration_number !== row.vehicle_number && (
                        <span className="ml-2 text-xs text-slate-500">({row.vehicle_number})</span>
                    )}
                </div>
            ),
        },
        {
            key: 'category',
            header: 'Category',
            render: row => row.category || 'General Fleet',
        },
        {
            key: 'make_model',
            header: 'Make & Model',
            render: row => [row.make, row.model].filter(Boolean).join(' ') || 'Standard',
        },
        {
            key: 'odometer',
            header: 'Current Odometer',
            render: row => `${Number(row.odometer_reading || 0).toLocaleString()} km`,
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: row => (
                <div className="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => navigate(`/vehicle-rental/reservations?vehicle_id=${row.id}`)}
                    >
                        Reserve
                    </Button>
                    <Button
                        type="button"
                        variant="primary"
                        onClick={() => navigate(`/vehicle-rental/customer/agreements?vehicle_id=${row.id}`)}
                    >
                        Dispatch
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <div className="space-y-5">
            <ContentHeader
                title="Vehicle availability"
                description="Check fleet availability across planned and active custody periods to prevent booking collisions."
            />
            <Panel title="Availability Search">
                <form aria-label="Query vehicle availability" onSubmit={handleCheck} className="grid gap-3 md:grid-cols-3">
                    <Input
                        label="Required start date & time"
                        type="datetime-local"
                        required
                        step={OPERATIONAL_TIME_STEP_SECONDS}
                        value={startsAt}
                        onChange={e => setStartsAt(e.target.value)}
                        error={error?.fields.starts_at?.[0]}
                    />
                    <Input
                        label="Expected end date & time"
                        type="datetime-local"
                        step={OPERATIONAL_TIME_STEP_SECONDS}
                        min={startsAt || undefined}
                        value={endsAt}
                        onChange={e => setEndsAt(e.target.value)}
                        error={error?.fields.ends_at?.[0]}
                    />
                    <Input
                        label="Filter available results"
                        placeholder="Registration, make, model or category"
                        value={searchTerm}
                        onChange={e => setSearchTerm(e.target.value)}
                    />
                    <p className="text-sm text-slate-600 md:col-span-3">
                        Operational times use {operationalTimeZoneLabel()}. Checks for conflicts against both planned assignments and vehicles physically in customer custody.
                    </p>
                    <div className="flex gap-2 md:col-span-3">
                        <Button type="submit" loading={loading}>Check Availability</Button>
                        {searched && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => {
                                    setSearchTerm('');
                                    setStartsAt(`${today}T08:00`);
                                    setEndsAt(`${today}T18:00`);
                                    setVehicles([]);
                                    setSearched(false);
                                    setError(null);
                                }}
                            >
                                Reset
                            </Button>
                        )}
                    </div>
                </form>
            </Panel>
            <ErrorAlert error={error} inline />
            {loading ? (
                <LoadingState label="Checking fleet availability…" />
            ) : searched ? (
                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h3 className="text-base font-semibold text-slate-900">
                            Available Vehicles ({filteredVehicles.length})
                        </h3>
                    </div>
                    <DataTable
                        columns={columns}
                        rows={filteredVehicles}
                        rowKey={row => row.id}
                        emptyMessage="No vehicles are available for the selected time window."
                    />
                </div>
            ) : null}
        </div>
    );
}
