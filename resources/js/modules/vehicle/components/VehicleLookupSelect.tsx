import { useCallback } from 'react';
import { mapLookupResult } from '@/shared/api/lookupRequest';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { searchVehicles } from '../vehicleApi';
import type { VehicleSummary } from '../vehicleTypes';

type VehicleLookupOption = VehicleSummary & NamedResource;

interface VehicleLookupSelectProps {
    value: VehicleSummary | null;
    onChange: (value: VehicleSummary | null) => void;
    error?: string;
    kind?: string;
    required?: boolean;
    disabled?: boolean;
}

export function VehicleLookupSelect({
    value,
    onChange,
    error,
    kind = 'active',
    required = false,
    disabled = false,
}: VehicleLookupSelectProps) {
    const search = useCallback(async (params: LookupLoadParams) => {
        const result = await searchVehicles(params, kind);
        return mapLookupResult(result, (vehicle): VehicleLookupOption => ({
            ...vehicle,
            name: vehicle.vehicle_number,
        }));
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
            required={required}
            disabled={disabled}
        />
    );
}
