import { fieldError, type ApiError } from '@/shared/api/apiError';
import { lookupApi, type ItemLookupResource } from '@/shared/api/lookupApi';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Select } from '@/shared/components/Select';
import type { VehicleServiceLineSourceType } from '../../vehicleServiceTypes';
import {
    lineTypeOptions,
    type VehicleServiceLineFormValue,
} from './lineForm';

export function LineSourceTypeFields({ value, error, onChange }: {
    value: VehicleServiceLineFormValue;
    error: ApiError | null;
    onChange: (value: VehicleServiceLineFormValue) => void;
}) {
    const external = value.source === 'external_item';
    const itemSearch = value.source === 'inventory_item'
        ? lookupApi.stockableItems
        : value.source === 'service_item'
            ? lookupApi.serviceItems
            : value.source === 'labour_item'
                ? lookupApi.labourItems
                : value.source === 'combo_parent'
                    ? lookupApi.comboItems
                    : lookupApi.items;

    return (
        <>
            <Select
                label="Line type"
                value={value.source}
                options={lineTypeOptions}
                error={fieldError(error, 'line_source_type')}
                onChange={(event) => onChange({
                    ...value,
                    source: event.target.value as VehicleServiceLineSourceType,
                    item: null,
                    customer_supplied: false,
                })}
            />
            {!external && (
                <LookupSelect
                    label="Item"
                    value={value.item}
                    error={fieldError(error, 'item_id')}
                    onChange={(item) => onChange(nextLineValue(value, item))}
                    search={itemSearch}
                />
            )}
        </>
    );
}

function nextLineValue(
    value: VehicleServiceLineFormValue,
    item: ItemLookupResource | null,
): VehicleServiceLineFormValue {
    return {
        ...value,
        item,
        description: item?.name ?? value.description,
        unit_price: item?.resolved_service_unit_price ?? value.unit_price,
    };
}
