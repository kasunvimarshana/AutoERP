import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchVehicleTypes } from '../vehicleApi';
import type { VehicleType } from '../vehicleTypes';

export function VehicleTypeSelect({ value, onChange, error }: {
    value: VehicleType | null;
    onChange: (value: VehicleType | null) => void;
    error?: string;
}) {
    const search = useCallback(searchVehicleTypes, []);
    return <GenericLookupSelect label="Type" value={value} onChange={onChange} search={search} formatLabel={(item) => `${item.code ?? ''} ${item.name}`.trim()} error={error} loadOnOpen minSearchLength={0} />;
}
