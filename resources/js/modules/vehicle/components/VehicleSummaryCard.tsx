import { DetailGrid } from '@/shared/components/DetailGrid';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { Vehicle } from '../vehicleTypes';

export function VehicleSummaryCard({ vehicle }: { vehicle: Vehicle }) {
    return (
        <Panel title="Summary">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="text-lg font-semibold text-slate-900">{vehicle.vehicle_number}</p>
                    <p className="text-sm text-slate-500">{vehicle.registration_number ?? vehicle.code ?? 'Unregistered'}</p>
                </div>
                <StatusBadge status={vehicle.status} />
            </div>
            <DetailGrid
                items={[
                    { label: 'Make', value: vehicle.make?.name ?? '-' },
                    { label: 'Model', value: vehicle.model?.name ?? '-' },
                    { label: 'Type', value: vehicle.type?.name ?? '-' },
                    { label: 'Category', value: vehicle.category?.name ?? '-' },
                    { label: 'Customer', value: vehicle.customer?.name ?? '-' },
                    { label: 'Odometer', value: `${vehicle.odometer_reading ?? '0.000000'} ${vehicle.odometer_unit ?? ''}`.trim() },
                    { label: 'Fuel', value: vehicle.fuel_type ?? '-' },
                    { label: 'Transmission', value: vehicle.transmission_type ?? '-' },
                    { label: 'Chassis', value: vehicle.chassis_number ?? '-' },
                    { label: 'VIN', value: vehicle.vin_number ?? '-' },
                ]}
            />
        </Panel>
    );
}
