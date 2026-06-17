import { useCallback, useEffect } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { searchVehicleModels } from '../vehicleApi';
import type { VehicleModel } from '../vehicleTypes';

export function VehicleModelSelect({ makeId, value, onChange, error }: {
    makeId?: number | null;
    value: VehicleModel | null;
    onChange: (value: VehicleModel | null) => void;
    error?: string;
}) {
    useEffect(() => {
        if (value?.make?.id && makeId && Number(value.make.id) !== Number(makeId)) onChange(null);
    }, [makeId, onChange, value]);

    const search = useCallback((params: LookupLoadParams) => searchVehicleModels(params, makeId), [makeId]);
    return <GenericLookupSelect label="Model" value={value} onChange={onChange} search={search} formatLabel={(item) => `${item.code ?? ''} ${item.name}`.trim()} error={error} loadOnOpen minSearchLength={0} />;
}
