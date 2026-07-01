import { useCallback } from 'react';
import { ItemVariantSelect } from '@/modules/item/components/ItemVariantSelect';
import type { ItemVariant } from '@/modules/item/itemTypes';
import { listUoms, searchWarehouseLocations } from '@/shared/api/referenceApi';
import { LookupSelect } from '@/shared/components/LookupSelect';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { searchInventoryBatches, searchInventorySerials } from '../inventoryApi';

export interface InventoryDimensionValue {
    itemVariant: ItemVariant | null;
    warehouseLocation: NamedResource | null;
    batch: NamedResource | null;
    serial: NamedResource | null;
    uom: NamedResource | null;
}

export function emptyInventoryDimensions(): InventoryDimensionValue {
    return {
        itemVariant: null,
        warehouseLocation: null,
        batch: null,
        serial: null,
        uom: null,
    };
}

export function InventoryDimensionFields({
    item,
    warehouse,
    value,
    onChange,
    errors = {},
    includeLocation = true,
    includeSerial = false,
    includeUom = true,
}: {
    item: NamedResource | null;
    warehouse?: NamedResource | null;
    value: InventoryDimensionValue;
    onChange: (value: InventoryDimensionValue) => void;
    errors?: Partial<Record<keyof InventoryDimensionValue, string>>;
    includeLocation?: boolean;
    includeSerial?: boolean;
    includeUom?: boolean;
}) {
    const locationSearch = useCallback(
        (params: LookupLoadParams) => searchWarehouseLocations(params, warehouse?.id),
        [warehouse?.id],
    );
    const batchSearch = useCallback(
        (params: LookupLoadParams) => searchInventoryBatches(params, {
            itemId: item?.id,
            itemVariantId: value.itemVariant?.id,
        }),
        [item?.id, value.itemVariant?.id],
    );
    const serialSearch = useCallback(
        (params: LookupLoadParams) => searchInventorySerials(params, {
            itemId: item?.id,
            itemVariantId: value.itemVariant?.id,
            warehouseId: warehouse?.id,
            warehouseLocationId: value.warehouseLocation?.id,
            batchId: value.batch?.id,
        }),
        [item?.id, value.batch?.id, value.itemVariant?.id, value.warehouseLocation?.id, warehouse?.id],
    );

    return (
        <details className="lg:col-span-full">
            <summary className="cursor-pointer text-sm font-semibold text-slate-700">Optional dimensions</summary>
            <div className="mt-3 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {item && (
                    <ItemVariantSelect
                        itemId={item.id}
                        value={value.itemVariant}
                        error={errors.itemVariant}
                        onChange={(itemVariant) => onChange({
                            ...value,
                            itemVariant,
                            batch: null,
                            serial: null,
                        })}
                    />
                )}
                {includeLocation && (
                    <LookupSelect
                        label="Location"
                        value={value.warehouseLocation}
                        onChange={(warehouseLocation) => onChange({ ...value, warehouseLocation, serial: null })}
                        search={locationSearch}
                        placeholder="Search locations..."
                        error={errors.warehouseLocation}
                        disabled={!warehouse}
                        loadOnOpen
                        minSearchLength={0}
                    />
                )}
                <LookupSelect
                    label="Batch/Lot"
                    value={value.batch}
                    onChange={(batch) => onChange({ ...value, batch, serial: null })}
                    search={batchSearch}
                    placeholder="Search batches or lots..."
                    error={errors.batch}
                    disabled={!item}
                    loadOnOpen
                    minSearchLength={0}
                />
                {includeSerial && (
                    <LookupSelect
                        label="Serial"
                        value={value.serial}
                        onChange={(serial) => onChange({ ...value, serial })}
                        search={serialSearch}
                        placeholder="Search serial numbers..."
                        error={errors.serial}
                        disabled={!item || !warehouse}
                        loadOnOpen
                        minSearchLength={0}
                    />
                )}
                {includeUom && (
                    <LookupSelect
                        label="UOM"
                        value={value.uom}
                        onChange={(uom) => onChange({ ...value, uom })}
                        search={listUoms}
                        placeholder="Search UOMs..."
                        error={errors.uom}
                        loadOnOpen
                        minSearchLength={0}
                    />
                )}
            </div>
        </details>
    );
}
