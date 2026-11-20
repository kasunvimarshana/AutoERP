import { useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { createVehicleMake, createVehicleModel, createVehicleType } from '../vehicleApi';
import type { VehicleMake, VehicleModel, VehicleType } from '../vehicleTypes';

const MASTER_CODE_MAX_LENGTH = 80;
const MASTER_NAME_MAX_LENGTH = 150;

export type QuickMasterKind = 'make' | 'model' | 'type';
export type QuickMasterCreated =
    | { kind: 'make'; resource: VehicleMake }
    | { kind: 'model'; resource: VehicleModel }
    | { kind: 'type'; resource: VehicleType };

export function VehicleQuickMasterCreate({ kind, make, onCancel, onCreated, onSavingChange }: {
    kind: QuickMasterKind;
    make: VehicleMake | null;
    onCancel: () => void;
    onCreated: (created: QuickMasterCreated) => void;
    onSavingChange: (saving: boolean) => void;
}) {
    const [code, setCode] = useState('');
    const [name, setName] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const label = kind[0].toUpperCase() + kind.slice(1);

    async function save() {
        if (saving || !code.trim() || !name.trim() || (kind === 'model' && !make)) return;
        setSaving(true);
        onSavingChange(true);
        setError(null);

        try {
            const base = { code: code.trim(), name: name.trim() };
            if (kind === 'make') {
                onCreated({ kind, resource: await createVehicleMake(base) });
            } else if (kind === 'model') {
                onCreated({ kind, resource: await createVehicleModel({ ...base, vehicle_make_id: make!.id }) });
            } else {
                onCreated({ kind, resource: await createVehicleType(base) });
            }
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
            onSavingChange(false);
        }
    }

    return (
        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4" role="group" aria-label={`New ${label} details`}>
            <div className="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h3 className="font-semibold text-slate-900">Add {label}</h3>
                    <p className="text-xs text-slate-600">This {label.toLowerCase()} will also be available for other vehicles.</p>
                </div>
                <Button type="button" variant="ghost" size="sm" onClick={onCancel} disabled={saving}>Cancel</Button>
            </div>
            {kind === 'model' && (
                <p className={`mb-3 text-sm ${make ? 'text-slate-700' : 'text-rose-700'}`}>
                    {make ? <>Make: <strong>{make.name}</strong></> : 'Select or add a Make before saving this Model.'}
                </p>
            )}
            <ErrorAlert error={error} title={`${label} could not be added`} inline />
            <div className="mt-3 grid gap-3 sm:grid-cols-2" onKeyDown={(event) => {
                if (event.key === 'Enter' && event.target instanceof HTMLInputElement) {
                    event.preventDefault();
                    void save();
                }
            }}>
                <Input label={`${label} name`} value={name} onChange={(event) => setName(event.target.value)} error={fieldError(error, 'name')} maxLength={MASTER_NAME_MAX_LENGTH} required />
                <Input label="Code" value={code} onChange={(event) => setCode(event.target.value)} error={fieldError(error, 'code')} maxLength={MASTER_CODE_MAX_LENGTH} required />
            </div>
            <div className="mt-3 flex justify-end">
                <Button type="button" size="sm" onClick={() => void save()} loading={saving} disabled={!code.trim() || !name.trim() || (kind === 'model' && !make)}>Save {label}</Button>
            </div>
        </div>
    );
}
