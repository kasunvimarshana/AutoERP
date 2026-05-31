import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { Card } from '../../../shared/components/ui/Card';
import type { Vehicle, VehicleOwnership } from '../types/vehicle.types';

function ownerLabel(ownership?: VehicleOwnership | null) {
    if (!ownership) {
        return 'No current legal owner returned';
    }

    return `${ownership.ownerDisplayName} (${ownership.ownershipType})`;
}

function yesNo(value: boolean) {
    return value ? 'Enabled' : 'Disabled';
}

export function VehicleSummaryCard({ currentOwnership, vehicle }: { currentOwnership?: VehicleOwnership | null; vehicle: Vehicle }) {
    return (
        <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
            <Card className="p-5">
                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wide text-slate-400">{vehicle.code || 'Uncoded vehicle'}</p>
                        <h2 className="mt-1 text-xl font-bold text-slate-950">{vehicle.registrationNumber || 'Registration pending'}</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            {[vehicle.year, vehicle.make, vehicle.model].filter(Boolean).join(' ') || 'Vehicle details pending'}
                        </p>
                    </div>
                    <StatusBadge status={vehicle.status} />
                </div>

                <div className="mt-5 grid gap-3 md:grid-cols-3">
                    {[
                        ['Current owner', ownerLabel(currentOwnership)],
                        ['Usage profile', vehicle.usageProfile],
                        ['Odometer', vehicle.currentOdometer],
                        ['Service profile', yesNo(vehicle.serviceEnabled)],
                        ['Rental profile', yesNo(vehicle.rentalEnabled)],
                        ['VIN / chassis', vehicle.vin || 'Not provided'],
                    ].map(([label, value]) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3" key={label}>
                            <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
                            <p className="mt-1 text-sm font-semibold text-slate-800">{value}</p>
                        </div>
                    ))}
                </div>
            </Card>

            <PreviewPanel
                rows={[
                    { label: 'Vehicle master', value: 'Generic shared profile' },
                    { label: 'VehicleService', value: 'Consumes service validation' },
                    { label: 'VehicleRental', value: 'Consumes rental validation and ownership context' },
                ]}
                status="Core"
                subtitle="This module exposes reusable vehicle master data and validation context only."
                title="Module Boundary"
            />
        </div>
    );
}
