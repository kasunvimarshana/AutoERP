import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchVehicleCategories } from '../vehicleApi';
import type { VehicleCategory } from '../vehicleTypes';

export function VehicleCategorySelect({ value, onChange, error }: {
    value: VehicleCategory | null;
    onChange: (value: VehicleCategory | null) => void;
    error?: string;
}) {
    const search = useCallback((query: string, signal: AbortSignal) => searchVehicleCategories(query, signal), []);
    return <GenericLookupSelect label="Category" value={value} onChange={onChange} search={search} formatLabel={(item) => `${item.code ?? ''} ${item.name}`.trim()} error={error} />;
}
