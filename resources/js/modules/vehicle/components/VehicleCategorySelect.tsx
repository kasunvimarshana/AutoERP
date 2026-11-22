/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchVehicleCategories } from '../vehicleApi';
import type { VehicleCategory } from '../vehicleTypes';

export function VehicleCategorySelect({ value, onChange, error }: {
    value: VehicleCategory | null;
    onChange: (value: VehicleCategory | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Category" value={value} onChange={onChange} search={searchVehicleCategories} formatLabel={(item) => `${item.code ?? ''} ${item.name}`.trim()} error={error} loadOnOpen minSearchLength={0} />;
}
