import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';
import { searchVehicles } from '../vehicleApi';
import type { VehicleSummary } from '../vehicleTypes';

type VehicleLookupOption = VehicleSummary & NamedResource;

export function VehicleLookupSelect({ value, onChange, error, kind = 'active' }: {
    value: VehicleSummary | null;
    onChange: (value: VehicleSummary | null) => void;
    error?: string;
    kind?: string;
}) {
    const search = useCallback(async (query: string, signal: AbortSignal) => {
        const results = await searchVehicles(query, signal, kind);
        return results.map((vehicle) => ({ ...vehicle, name: vehicle.vehicle_number }));
    }, [kind]);
    const selected = value ? { ...value, name: value.vehicle_number } : null;
    return (
        <GenericLookupSelect<VehicleLookupOption>
            label="Vehicle"
            value={selected}
            onChange={onChange}
            search={search}
            formatLabel={(item) => `${item.vehicle_number} ${item.registration_number ?? ''}`.trim()}
            error={error}
        />
    );
}
