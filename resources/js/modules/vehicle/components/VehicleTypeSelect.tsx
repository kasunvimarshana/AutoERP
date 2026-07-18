import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { useApi } from '@/shared/hooks/useApi';
import { preloadVehicleTypes, searchVehicleTypes } from '../vehicleApi';
import type { VehicleType } from '../vehicleTypes';

export function VehicleTypeSelect({ value, onChange, error }: {
    value: VehicleType | null;
    onChange: (value: VehicleType | null) => void;
    error?: string;
}) {
    useApi((signal) => preloadVehicleTypes(signal), []);
    return <GenericLookupSelect label="Type" value={value} onChange={onChange} search={searchVehicleTypes} formatLabel={(item) => `${item.code ?? ''} ${item.name}`.trim()} error={error} loadOnOpen minSearchLength={0} />;
}
