import { useEffect, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { vehicleApi } from '../services/vehicleApi';
import type { Vehicle } from '../types/vehicle.types';

export function VehicleDetailPage() {
    const { id } = useParams();
    const location = useLocation();
    const stateVehicle = (location.state as { vehicle?: Vehicle } | null)?.vehicle;
    const [vehicle, setVehicle] = useState<Vehicle | null>(stateVehicle?.id === Number(id) ? stateVehicle : null);
    const [error, setError] = useState('');

    useEffect(() => {
        if (vehicle || !id) return;

        let active = true;
        void vehicleApi.get(Number(id)).then((response) => {
            if (active) setVehicle(response);
        }).catch((requestError) => {
            if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load this vehicle.');
        });

        return () => {
            active = false;
        };
    }, [id, vehicle]);

    if (!vehicle && !error) return <div className="flex items-center justify-center p-16 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading vehicle</span></div>;
    if (!vehicle) return <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>;

    return (
        <div className="mx-auto max-w-5xl space-y-5">
            <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{vehicle.vehicleCode}</p><h1 className="mt-1 text-3xl font-bold text-slate-950">{vehicle.registrationNumber}</h1><p className="mt-1 text-sm text-slate-500">{[vehicle.make, vehicle.model, vehicle.year].filter(Boolean).join(' ') || 'Vehicle record'}</p></div>
                <div className="flex gap-2"><Link className="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to="/vehicles">Back</Link><Link className="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" to={`/vehicles/${vehicle.id}/edit`}>Edit</Link></div>
            </header>

            <div className="grid gap-5 lg:grid-cols-3">
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h2 className="font-bold text-slate-950">Identity</h2>
                    <dl className="mt-4 grid gap-4 sm:grid-cols-2"><Item label="Registration number" value={vehicle.registrationNumber} /><Item label="Status" value={vehicle.status} /><Item label="Chassis number" value={vehicle.chassisNumber} /><Item label="Engine number" value={vehicle.engineNumber} /><Item label="Organization unit" value={vehicle.organizationUnitId} /><Item label="Ownership type" value={vehicle.ownershipType} /></dl>
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="font-bold text-slate-950">Classification</h2>
                    <dl className="mt-4 space-y-4"><Item label="Vehicle type" value={vehicle.vehicleType} /><Item label="Fuel type" value={vehicle.fuelType} /><Item label="Transmission" value={vehicle.transmissionType} /></dl>
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h2 className="font-bold text-slate-950">Vehicle details</h2>
                    <dl className="mt-4 grid gap-4 sm:grid-cols-2"><Item label="Make" value={vehicle.make} /><Item label="Model" value={vehicle.model} /><Item label="Year" value={vehicle.year} /><Item label="Color" value={vehicle.color} /></dl>
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold text-slate-950">Notes</h2><p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-600">{vehicle.notes || 'No notes.'}</p></section>
            </div>
        </div>
    );
}

function Item({ label, value }: { label: string; value?: string | number | null }) {
    return <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</dt><dd className="mt-1 text-sm font-semibold text-slate-800">{value === null || value === undefined || value === '' ? 'Not provided' : value}</dd></div>;
}
