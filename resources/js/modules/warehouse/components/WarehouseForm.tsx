import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { humanize } from '@/shared/utils/object';
import type { WarehousePayload } from '../warehouseTypes';
import { warehouseTypes } from '../warehouseTypes';

interface WarehouseFormProps {
    value: WarehousePayload;
    onChange: (value: WarehousePayload) => void;
    error: ApiError | null;
    canManageDefault?: boolean;
}

export function WarehouseForm({ value, onChange, error, canManageDefault = true }: WarehouseFormProps) {
    const set = <K extends keyof WarehousePayload>(key: K, next: WarehousePayload[K]) => onChange({ ...value, [key]: next });

    return (
        <div className="space-y-5">
            <Panel>
                <h2 className="mb-4 text-base font-semibold text-slate-900">Basic Information</h2>
                <div className="grid gap-4 md:grid-cols-3">
                    <Input label="Name" value={value.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name')} required />
                    <Input label="Code" value={value.code ?? ''} onChange={(event) => set('code', event.target.value.toUpperCase())} error={fieldError(error, 'code')} />
                    <Select label="Type" value={value.type} onChange={(event) => set('type', event.target.value as WarehousePayload['type'])} options={warehouseTypes.map((type) => ({ value: type, label: humanize(type) }))} error={fieldError(error, 'type')} />
                </div>
            </Panel>

            <Panel>
                <h2 className="mb-4 text-base font-semibold text-slate-900">Operational Settings</h2>
                <div className="flex flex-wrap gap-5 text-sm text-slate-700">
                    <label className="flex items-center gap-2">
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
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={value.is_default}
                                onChange={(event) => onChange({
                                    ...value,
                                    is_default: event.target.checked,
                                    is_active: event.target.checked ? true : value.is_active,
                                })}
                            />
                            Default Warehouse
                        </label>
                    )}
                </div>
                {(fieldError(error, 'is_active') || fieldError(error, 'is_default')) && (
                    <p className="mt-2 text-xs text-rose-600">{fieldError(error, 'is_default') ?? fieldError(error, 'is_active')}</p>
                )}
            </Panel>
        </div>
    );
}
