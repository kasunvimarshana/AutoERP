import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { useApi } from '@/shared/hooks/useApi';
import { preloadVehicleMakes, searchVehicleMakes } from '../vehicleApi';
import type { VehicleMake } from '../vehicleTypes';

export function VehicleMakeSelect({ value, onChange, error }: {
    value: VehicleMake | null;
    onChange: (value: VehicleMake | null) => void;
    error?: string;
}) {
    useApi((signal) => preloadVehicleMakes(signal), []);
    return <GenericLookupSelect label="Make" value={value} onChange={onChange} search={searchVehicleMakes} formatLabel={(item) => `${item.code ?? ''} ${item.name}`.trim()} error={error} loadOnOpen minSearchLength={0} />;
}
