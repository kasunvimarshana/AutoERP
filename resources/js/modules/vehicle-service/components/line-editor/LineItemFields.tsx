import { fieldError, type ApiError } from '@/shared/api/apiError';
import { lookupApi, type ItemLookupResource } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type { VehicleServiceLineSourceType } from '../../vehicleServiceTypes';
import {
    lineTypeLabel,
    type VehicleServiceLineFormValue,
} from './lineForm';

export function LineItemFields({ value, error, onChange }: {
    value: VehicleServiceLineFormValue;
    error: ApiError | null;
    onChange: (value: VehicleServiceLineFormValue) => void;
}) {
    const external = value.source === 'external_item';

    if (external) {
        return (
            <div className="space-y-3 sm:col-span-2 lg:col-span-3">
                <Input
                    label="External item description"
                    value={value.description}
                    error={fieldError(error, 'description')}
                    placeholder="Enter the item or service description"
                    required
                    onChange={(event) => onChange({ ...value, description: event.target.value })}
                />
                <Button
                    type="button"
                    variant="ghost"
                    className="px-0"
                    onClick={() => onChange(toRegisteredItem(value))}
                >
                    Search registered items instead
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-3 sm:col-span-2 lg:col-span-3">
            <LookupSelect
                label="Item"
                value={value.item}
                error={fieldError(error, 'item_id') ?? fieldError(error, 'line_source_type')}
                onChange={(item) => onChange(lineValueWithItem(value, item))}
                search={searchVehicleServiceLineItems}
                renderOption={(item, state) => <ItemOption option={item} active={state.active} />}
                recentResultsKey="vehicle-service:job-line-items"
                placeholder="Search inventory, service, labour, or package items..."
                required
            />
            <Button
                type="button"
                variant="ghost"
                className="px-0"
                onClick={() => onChange(toExternalItem(value))}
            >
                Enter an external or customer-supplied item
            </Button>
        </div>
    );
}

export async function searchVehicleServiceLineItems(
    params: LookupLoadParams,
): Promise<LookupResult<ItemLookupResource>> {
    const results = await Promise.all([
        lookupApi.stockableItems(params),
        lookupApi.serviceItems(params),
        lookupApi.labourItems(params),
        lookupApi.comboItems(params),
    ]);
    const data = dedupeById(results.flatMap((result) => result.data).filter(isSupportedLineItem));
    const metas = results.map((result) => result.meta).filter((meta) => meta !== undefined);

    return {
        data,
        meta: metas.length === 0 ? undefined : {
            current_page: params.page,
            from: data.length === 0 ? null : ((params.page - 1) * params.perPage) + 1,
            last_page: Math.max(...metas.map((meta) => meta.last_page)),
            per_page: params.perPage,
            to: data.length === 0 ? null : ((params.page - 1) * params.perPage) + data.length,
            total: metas.reduce((total, meta) => total + meta.total, 0),
        },
    };
}

export function lineSourceTypeForItem(item: ItemLookupResource): VehicleServiceLineSourceType {
    if (item.item_type === 'service') return 'service_item';
    if (item.item_type === 'labour') return 'labour_item';
    if (item.item_type === 'combo' || item.item_type === 'package' || item.is_combo) return 'combo_parent';
    if (isInventoryLineItem(item)) return 'inventory_item';

    throw new Error(`Item ${item.id} is not supported on vehicle service job lines.`);
}

export function isInventoryLineItem(item: ItemLookupResource): boolean {
    return Boolean(item.is_stockable)
        && !item.is_combo
        && !['non_stock', 'service', 'labour', 'combo', 'package'].includes(item.item_type ?? '');
}

function isSupportedLineItem(item: ItemLookupResource): boolean {
    return item.item_type === 'service'
        || item.item_type === 'labour'
        || item.item_type === 'combo'
        || item.item_type === 'package'
        || Boolean(item.is_combo)
        || isInventoryLineItem(item);
}

export function lineValueWithItem(
    value: VehicleServiceLineFormValue,
    item: ItemLookupResource | null,
): VehicleServiceLineFormValue {
    if (item === null) {
        return {
            ...value,
            item: null,
            description: '',
            uom: null,
            issueWarehouse: null,
            issueLocation: null,
        };
    }

    const source = lineSourceTypeForItem(item);

    return {
        ...value,
        source,
        item,
        description: item.name,
        uom: item.base_uom ?? null,
        unit_cost: item.resolved_purchase_unit_price ?? value.unit_cost,
        unit_price: item.resolved_service_unit_price ?? value.unit_price,
        customer_supplied: false,
        issueWarehouse: source === 'inventory_item' ? value.issueWarehouse : null,
        issueLocation: source === 'inventory_item' ? value.issueLocation : null,
    };
}

function toExternalItem(value: VehicleServiceLineFormValue): VehicleServiceLineFormValue {
    return {
        ...value,
        source: 'external_item',
        item: null,
        uom: null,
        description: '',
        customer_supplied: false,
        issueWarehouse: null,
        issueLocation: null,
    };
}

function toRegisteredItem(value: VehicleServiceLineFormValue): VehicleServiceLineFormValue {
    return {
        ...value,
        source: 'inventory_item',
        item: null,
        uom: null,
        description: '',
        customer_supplied: false,
        issueWarehouse: null,
        issueLocation: null,
    };
}

function ItemOption({ option, active }: { option: ItemLookupResource; active: boolean }) {
    return (
        <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
                <div className={`truncate font-medium ${active ? 'text-sky-900' : 'text-slate-900'}`}>
                    {option.code ? `${option.code} - ${option.name}` : option.name}
                </div>
                <div className="mt-1 text-xs text-slate-500">
                    {stockNotice(option)}
                </div>
            </div>
            <span className={`shrink-0 rounded-full px-2 py-1 text-xs font-semibold ${
                option.is_stockable
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-slate-100 text-slate-600'
            }`}>
                {lineTypeLabel(lineSourceTypeForItem(option))}
            </span>
        </div>
    );
}

function stockNotice(option: ItemLookupResource): string {
    if (!option.is_stockable) {
        return 'No stock tracking';
    }

    const quantity = option.available_stock_quantity ?? '0.000000';
    const unit = option.base_uom?.code ?? option.base_uom?.name ?? 'units';

    return `Available stock: ${quantity} ${unit}`.trim();
}

function dedupeById<T extends ItemLookupResource>(options: T[]): T[] {
    const seen = new Set<number>();

    return options.filter((option) => {
        const id = Number(option.id);
        if (seen.has(id)) return false;
        seen.add(id);

        return true;
    });
}
