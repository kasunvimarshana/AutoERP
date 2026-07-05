import { fieldError, type ApiError } from '@/shared/api/apiError';
import { lookupApi, type ItemLookupResource } from '@/shared/api/lookupApi';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Select } from '@/shared/components/Select';
import type { VehicleServiceLineSourceType } from '../../vehicleServiceTypes';
import {
    lineTypeLabel,
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
                    description: lineTypeLabel(event.target.value as VehicleServiceLineSourceType),
                    customer_supplied: false,
                })}
            />
            {!external && (
                <div className="sm:col-span-2">
                    <LookupSelect
                        label="Item"
                        value={value.item}
                        error={fieldError(error, 'item_id')}
                        onChange={(item) => onChange(nextLineValue(value, item))}
                        search={itemSearch}
                        renderOption={(item, state) => <ItemOption option={item} active={state.active} />}
                        recentResultsKey={`vehicle-service:job-line-items:${value.source}`}
                    />
                </div>
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
        description: item?.name ?? lineTypeLabel(value.source),
        uom: item?.base_uom ?? value.uom,
        unit_cost: item?.resolved_purchase_unit_price ?? value.unit_cost,
        unit_price: item?.resolved_service_unit_price ?? value.unit_price,
    };
}

function ItemOption({ option, active }: { option: ItemLookupResource; active: boolean }) {
    const stockText = stockNotice(option);

    return (
        <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
                <div className={`truncate font-medium ${active ? 'text-sky-900' : 'text-slate-900'}`}>
                    {option.code ? `${option.code} - ${option.name}` : option.name}
                </div>
                <div className="mt-1 text-xs text-slate-500">
                    {stockText}
                </div>
            </div>
            <span className={`shrink-0 rounded-full px-2 py-1 text-xs font-semibold ${
                option.is_stockable
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-slate-100 text-slate-600'
            }`}>
                {option.is_stockable ? 'Stock item' : 'Non-stock'}
            </span>
        </div>
    );
}

function stockNotice(option: ItemLookupResource): string {
    if (!option.is_stockable) {
        return 'No stock tracking for this item type';
    }

    const quantity = option.available_stock_quantity ?? '0.000000';
    const unit = option.base_uom?.code ?? option.base_uom?.name ?? 'units';

    return `Available stock: ${quantity} ${unit}`.trim();
}
