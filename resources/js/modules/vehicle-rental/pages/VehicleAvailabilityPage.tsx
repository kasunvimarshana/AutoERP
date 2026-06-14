import { useState } from 'react';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import { getVehicleAvailability } from '../vehicleRentalApi';

const localDateTime = (days = 0) => {
    const date = new Date(Date.now() + days * 86_400_000);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
};

export default function VehicleAvailabilityPage() {
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [startAt, setStartAt] = useState(localDateTime());
    const [endAt, setEndAt] = useState(localDateTime(1));
    const result = useApi((signal) => getVehicleAvailability({ start_at: startAt, end_at: endAt, vehicle_id: vehicle?.id }, signal), [startAt, endAt, vehicle?.id]);
    return (
        <>
            <ContentHeader title="Vehicle availability" description="Date-range availability across allocations, reservations, service work, and vehicle status." />
            <div className="mb-5 grid gap-4 md:grid-cols-3">
                <VehicleLookupSelect value={vehicle} onChange={setVehicle} kind="all" />
                <Input label="From" type="datetime-local" value={startAt} onChange={(event) => setStartAt(event.target.value)} />
                <Input label="To" type="datetime-local" value={endAt} onChange={(event) => setEndAt(event.target.value)} />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data ?? []} rowKey={(row) => row.vehicle.id} columns={[
                { key: 'vehicle', header: 'Vehicle', render: (row) => <strong>{row.vehicle.registration_number ?? row.vehicle.name}</strong> },
                { key: 'make', header: 'Make / model', render: (row) => `${row.vehicle.make ?? '-'} / ${row.vehicle.model ?? '-'}` },
                { key: 'available', header: 'Availability', render: (row) => <RentalStatusBadge status={row.available ? 'available' : 'unavailable'} /> },
                { key: 'reasons', header: 'Reason', render: (row) => row.reasons.join('; ') || 'Available for selected period' },
            ]} />}
        </>
    );
}
