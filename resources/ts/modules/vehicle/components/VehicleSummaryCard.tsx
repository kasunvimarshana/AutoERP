import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { Card } from '../../../shared/components/ui/Card';
import type { Vehicle, VehicleOwnership } from '../types/vehicle.types';

function currentOwnerLabel(ownership: VehicleOwnership | null) {
    if (!ownership) {
        return 'Backend owner context pending';
    }

    return `${ownership.ownerDisplayName} (${ownership.ownershipType})`;
}

export function VehicleSummaryCard({ currentOwnership, vehicle }: { currentOwnership?: VehicleOwnership | null; vehicle: Vehicle }) {
    return (
        <div className="grid gap-5 xl:grid-cols-[1fr_340px]">
            <Card className="p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wide text-slate-400">{vehicle.code}</p>
                        <h2 className="mt-1 text-xl font-bold text-slate-950">{vehicle.registrationNumber}</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            {vehicle.year || 'Year pending'} {vehicle.brand} {vehicle.model}
                        </p>
                    </div>
                    <StatusBadge status={vehicle.status} />
                </div>
                <div className="mt-5 grid gap-4 md:grid-cols-3">
                    {[
                        ['Current owner', currentOwnerLabel(currentOwnership ?? null)],
                        ['Usage profile', vehicle.usageProfile],
                        ['Odometer', vehicle.currentOdometer],
                        ['Service profile', vehicle.serviceEligibility],
                        ['Rental profile', vehicle.rentalEligibility],
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
                    { label: 'Availability', value: 'Backend-owned preview' },
                    { label: 'Service due', value: 'Backend-owned status' },
                    { label: 'Rental usage', value: 'Backend-owned history' },
                ]}
                status="Readonly"
                title="Vehicle backend summary"
            />
        </div>
    );
}

