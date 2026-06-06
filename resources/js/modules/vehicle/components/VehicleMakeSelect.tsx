import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchVehicleMakes } from '../vehicleApi';
import type { VehicleMake } from '../vehicleTypes';

export function VehicleMakeSelect({ value, onChange, error }: {
    value: VehicleMake | null;
    onChange: (value: VehicleMake | null) => void;
    error?: string;
}) {
    const search = useCallback((query: string, signal: AbortSignal) => searchVehicleMakes(query, signal), []);
    return <GenericLookupSelect label="Make" value={value} onChange={onChange} search={search} formatLabel={(item) => `${item.code ?? ''} ${item.name}`.trim()} error={error} />;
}
