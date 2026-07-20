import { useCallback, useEffect, useEffectEvent, useRef, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';
import { getDefaultWarehouse, getDefaultWarehouseLocation } from '@/modules/warehouse/warehouseApi';

export interface VehicleServiceInventoryLocationValue {
    warehouse: NamedResource | null;
    location: NamedResource | null;
}

export function VehicleServiceInventoryLocationFields({
    value,
    onChange,
    disabled = false,
}: {
    value: VehicleServiceInventoryLocationValue;
    onChange: (value: VehicleServiceInventoryLocationValue) => void;
    disabled?: boolean;
}) {
    const [defaultsError, setDefaultsError] = useState<ApiError | null>(null);
    const warehouseTouched = useRef(false);
    const locationTouched = useRef(false);
    const notifyChange = useEffectEvent(onChange);

    useEffect(() => {
        if (disabled || warehouseTouched.current || value.warehouse !== null) return;

        const controller = new AbortController();
        void getDefaultWarehouse(controller.signal)
            .then((warehouse) => {
                if (controller.signal.aborted || warehouseTouched.current || warehouse === null) return;
                notifyChange({ warehouse, location: null });
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setDefaultsError(toApiError(requestError));
            });

        return () => controller.abort();
    }, [disabled, value.warehouse]);

    useEffect(() => {
        const warehouse = value.warehouse;
        if (disabled || !warehouse || value.location !== null || locationTouched.current) return;

        const controller = new AbortController();
        void getDefaultWarehouseLocation(warehouse.id, controller.signal)
            .then((location) => {
                if (controller.signal.aborted || locationTouched.current || location === null) return;
                notifyChange({ warehouse, location });
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setDefaultsError(toApiError(requestError));
            });

        return () => controller.abort();
    }, [disabled, value.location, value.warehouse]);

    const searchLocations = useCallback(
        (params: Parameters<typeof searchWarehouseLocations>[0]) =>
            searchWarehouseLocations(params, value.warehouse?.id),
        [value.warehouse?.id],
    );

    return (
        <div className="space-y-3">
            <ErrorAlert error={defaultsError} inline />
            <div className="grid gap-4 sm:grid-cols-2">
                <GenericLookupSelect
                    label="Issue warehouse"
                    value={value.warehouse}
                    onChange={(warehouse) => {
                        warehouseTouched.current = true;
                        locationTouched.current = false;
                        setDefaultsError(null);
                        onChange({ warehouse, location: null });
                    }}
                    search={searchWarehouses}
                    formatLabel={(warehouse) => `${warehouse.code ?? ''} ${warehouse.name}`.trim()}
                    disabled={disabled}
                    loadOnOpen
                    minSearchLength={0}
                />
                <GenericLookupSelect
                    label="Issue location"
                    value={value.location}
                    onChange={(location) => {
                        locationTouched.current = true;
                        setDefaultsError(null);
                        onChange({ ...value, location });
                    }}
                    search={searchLocations}
                    formatLabel={(location) => `${location.code ?? ''} ${location.name}`.trim()}
                    placeholder={value.warehouse ? 'Select warehouse location' : 'Select a warehouse first'}
                    disabled={disabled || !value.warehouse}
                    loadOnOpen={Boolean(value.warehouse)}
                    minSearchLength={0}
                />
            </div>
        </div>
    );
}
