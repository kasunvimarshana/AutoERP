import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { PageHeader } from '../../../shared/components/erp/ErpUi';
import { VehicleForm } from '../components/VehicleForm';
import { vehicleApi } from '../services/vehicleApi';
import type { Vehicle, VehicleInput } from '../types/vehicle.types';

function toInput(vehicle: Vehicle): VehicleInput {
    return {
        chassisNumber: vehicle.chassisNumber,
        color: vehicle.color,
        engineNumber: vehicle.engineNumber,
        fuelType: vehicle.fuelType,
        make: vehicle.make,
        model: vehicle.model,
        notes: vehicle.notes,
        organizationUnitId: vehicle.organizationUnitId,
        ownershipType: vehicle.ownershipType,
        registrationNumber: vehicle.registrationNumber,
        status: vehicle.status,
        transmissionType: vehicle.transmissionType,
        vehicleCode: vehicle.vehicleCode,
        vehicleType: vehicle.vehicleType,
        year: vehicle.year,
    };
}

export function VehicleEditorPage({ mode }: { mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const { id } = useParams();
    const [vehicle, setVehicle] = useState<Vehicle | null>(null);
    const [error, setError] = useState('');
    const editing = mode === 'edit';

    useEffect(() => {
        if (!editing || !id) return;

        let active = true;
        void vehicleApi.get(Number(id)).then((response) => {
            if (active) setVehicle(response);
        }).catch((requestError) => {
            if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load this vehicle.');
        });

        return () => {
            active = false;
        };
    }, [editing, id]);

    async function submit(input: VehicleInput) {
        const saved = editing && id ? await vehicleApi.update(Number(id), input) : await vehicleApi.create(input);
        navigate(`/vehicles/${saved.id}`, { replace: true, state: { vehicle: saved } });
    }

    if (editing && !vehicle && !error) {
        return <div className="flex items-center justify-center p-16 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading vehicle</span></div>;
    }

    return (
        <div className="mx-auto max-w-5xl space-y-5">
            <PageHeader eyebrow={editing ? 'Edit record' : 'New record'} subtitle="Maintain registration, ownership, and technical vehicle information." title={editing ? 'Edit vehicle' : 'Create vehicle'} />
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
            {!error ? <VehicleForm initialValue={vehicle ? toInput(vehicle) : undefined} onCancel={() => navigate('/vehicles')} onSubmit={submit} submitLabel={editing ? 'Update vehicle' : 'Create vehicle'} /> : null}
        </div>
    );
}
