import { useCallback } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { humanize } from '@/shared/utils/object';
import { searchWarehouseLocationOptions, searchWarehouseOptions } from '../warehouseApi';
import type { WarehouseLocationPayload, WarehouseLocationSummary, WarehouseSummary } from '../warehouseTypes';
import { warehouseLocationTypes } from '../warehouseTypes';

interface WarehouseLocationFormProps {
    value: WarehouseLocationPayload;
    onChange: (value: WarehouseLocationPayload) => void;
    warehouse: WarehouseSummary | null;
    onWarehouseChange: (warehouse: WarehouseSummary | null) => void;
    parent: WarehouseLocationSummary | null;
    onParentChange: (parent: WarehouseLocationSummary | null) => void;
    error: ApiError | null;
    currentLocationId?: number | null;
    lockWarehouse?: boolean;
    canManageDefault?: boolean;
}

export function WarehouseLocationForm({
    value,
    onChange,
    warehouse,
    onWarehouseChange,
    parent,
    onParentChange,
    error,
    currentLocationId,
    lockWarehouse = false,
    canManageDefault = true,
}: WarehouseLocationFormProps) {
    const set = <K extends keyof WarehouseLocationPayload>(key: K, next: WarehouseLocationPayload[K]) => onChange({ ...value, [key]: next });
    const parentSearch = useCallback(
        (params: Parameters<typeof searchWarehouseLocationOptions>[0]) => searchWarehouseLocationOptions(params, value.warehouse_id),
        [value.warehouse_id],
    );

    return (
        <div className="space-y-5">
            <Panel>
                <h2 className="mb-4 text-base font-semibold text-slate-900">Basic Information</h2>
                <div className="grid gap-4 md:grid-cols-2">
                    <GenericLookupSelect
                        label="Warehouse"
                        value={warehouse}
                        onChange={(next) => {
                            onWarehouseChange(next);
                            onParentChange(null);
                            onChange({ ...value, warehouse_id: next ? Number(next.id) : null, parent_id: null });
                        }}
                        search={searchWarehouseOptions}
                        formatLabel={formatWarehouseLabel}
                        error={fieldError(error, 'warehouse_id')}
                        required
                        loadOnOpen
                        minSearchLength={0}
                        disabled={lockWarehouse}
                    />
                    <GenericLookupSelect
                        label="Parent Location"
                        value={parent}
                        onChange={(next) => {
                            onParentChange(next);
                            set('parent_id', next ? Number(next.id) : null);
                        }}
                        search={parentSearch}
                        formatLabel={formatLocationLabel}
                        error={fieldError(error, 'parent_id')}
                        excludeId={currentLocationId}
                        disabled={!value.warehouse_id}
                        loadOnOpen
                        minSearchLength={0}
                    />
                </div>
                <div className="mt-4 grid gap-4 md:grid-cols-3">
                    <Input label="Name" value={value.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name')} required />
                    <Input label="Code" value={value.code ?? ''} onChange={(event) => set('code', event.target.value.toUpperCase())} error={fieldError(error, 'code')} />
                    <Select label="Type" value={value.type} onChange={(event) => set('type', event.target.value as WarehouseLocationPayload['type'])} options={warehouseLocationTypes.map((type) => ({ value: type, label: humanize(type) }))} error={fieldError(error, 'type')} />
                </div>
            </Panel>

            <Panel>
                <h2 className="mb-4 text-base font-semibold text-slate-900">Operational Settings</h2>
                <div className="grid gap-4 md:grid-cols-3">
                    <Input label="Capacity" type="number" min="0" step="0.000001" value={value.capacity ?? ''} onChange={(event) => set('capacity', event.target.value)} error={fieldError(error, 'capacity')} />
                    <div className="md:col-span-2 flex flex-wrap items-end gap-5 text-sm text-slate-700">
                        <label className="flex min-h-10 items-center gap-2">
                            <input type="checkbox" checked={value.is_pickable} onChange={(event) => set('is_pickable', event.target.checked)} />
                            Pickable
                        </label>
                        <label className="flex min-h-10 items-center gap-2">
                            <input type="checkbox" checked={value.is_receivable} onChange={(event) => set('is_receivable', event.target.checked)} />
                            Receivable
                        </label>
                        <label className="flex min-h-10 items-center gap-2">
                            <input
                                type="checkbox"
                                checked={value.is_active}
                                onChange={(event) => onChange({
                                    ...value,
                                    is_active: event.target.checked,
                                    is_default: event.target.checked ? value.is_default : false,
                                })}
                            />
                            Active
                        </label>
                        {canManageDefault && (
                            <label className="flex min-h-10 items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={value.is_default}
                                    onChange={(event) => onChange({
                                        ...value,
                                        is_default: event.target.checked,
                                        is_active: event.target.checked ? true : value.is_active,
                                    })}
                                />
                                Default Location
                            </label>
                        )}
                    </div>
                </div>
                {(fieldError(error, 'is_active') || fieldError(error, 'is_default')) && (
                    <p className="mt-2 text-xs text-rose-600">{fieldError(error, 'is_default') ?? fieldError(error, 'is_active')}</p>
                )}
            </Panel>
        </div>
    );
}

export function formatWarehouseLabel(warehouse: WarehouseSummary): string {
    return `${warehouse.code ? `${warehouse.code} - ` : ''}${warehouse.name}`;
}

export function formatLocationLabel(location: WarehouseLocationSummary): string {
    const label = `${location.code ? `${location.code} - ` : ''}${location.name}`;
    return location.path && location.path !== label ? `${label} (${location.path})` : label;
}
