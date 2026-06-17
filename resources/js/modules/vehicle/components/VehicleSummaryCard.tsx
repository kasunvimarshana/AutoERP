import { DetailGrid } from '@/shared/components/DetailGrid';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { Vehicle } from '../vehicleTypes';

export function VehicleSummaryCard({ vehicle }: { vehicle: Vehicle }) {
    const currentCustomer = vehicle.current_ownerships?.find((ownership) => ownership.owner_type === 'customer')?.owner;
    const currentSupplier = vehicle.current_ownerships?.find((ownership) => ['supplier', 'owner'].includes(ownership.owner_type))?.owner;
    const hasCompanyOwnership = vehicle.current_ownerships?.some((ownership) => ownership.owner_type === 'company');
    const ownershipLabel = currentCustomer?.name ?? currentSupplier?.name ?? (hasCompanyOwnership ? 'Company Owned' : '-');

    return (
        <section className="space-y-5">
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
                    { label: currentCustomer ? 'Current Customer' : currentSupplier ? 'Current Owner / Supplier' : 'Ownership', value: ownershipLabel },
                    { label: 'Odometer', value: `${vehicle.odometer_reading ?? '0.000000'} ${vehicle.odometer_unit ?? ''}`.trim() },
                    { label: 'Fuel', value: vehicle.fuel_type ?? '-' },
                    { label: 'Transmission', value: vehicle.transmission_type ?? '-' },
                    { label: 'Chassis', value: vehicle.chassis_number ?? '-' },
                    { label: 'VIN', value: vehicle.vin_number ?? '-' },
                ]}
            />
        </section>
    );
}
