import { DecimalInput } from '@/shared/components/DecimalInput';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import type { CommissionType } from '../vehicleServiceTypes';
import {
    vehicleServiceWorkforceRoles,
    type VehicleServiceWorkforceRole,
} from '../commissionTypes';

export interface LaborItemCommissionDraft {
    role_type: VehicleServiceWorkforceRole;
    commission_type: CommissionType;
    commission_value: string;
    is_active: boolean;
}

export const emptyLaborItemCommissionDraft: LaborItemCommissionDraft = {
    role_type: 'technician',
    commission_type: 'none',
    commission_value: '0.000000',
    is_active: true,
};

export function LaborItemCommissionPanel({ value, onChange, disabled = false }: {
    value: LaborItemCommissionDraft;
    onChange: (value: LaborItemCommissionDraft) => void;
    disabled?: boolean;
}) {
    return (
        <Panel title="Default labor commission">
            <p className="mb-4 text-sm text-slate-600">
                This rule is copied into new employee assignments for this labor item and role. The item may use Hour, Unit, Job, Service, or any other valid UOM; commission is calculated from the resulting labor line total.
            </p>
            <div className="grid gap-4 md:grid-cols-4">
                <Select
                    label="Default role"
                    value={value.role_type}
                    disabled={disabled}
                    options={vehicleServiceWorkforceRoles.map((role) => ({
                        value: role,
                        label: role.replaceAll('_', ' '),
                    }))}
                    onChange={(event) => onChange({
                        ...value,
                        role_type: event.target.value as VehicleServiceWorkforceRole,
                    })}
                />
                <Select
                    label="Commission type"
                    value={value.commission_type}
                    disabled={disabled}
                    options={[
                        { value: 'none', label: 'None' },
                        { value: 'fixed', label: 'Fixed amount' },
                        { value: 'percentage', label: 'Percentage of labor line' },
                    ]}
                    onChange={(event) => onChange({
                        ...value,
                        commission_type: event.target.value as CommissionType,
                    })}
                />
                <DecimalInput
                    label="Commission value"
                    value={value.commission_value}
                    disabled={disabled || value.commission_type === 'none'}
                    onChange={(event) => onChange({ ...value, commission_value: event.target.value })}
                />
                <label className="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        checked={value.is_active}
                        disabled={disabled}
                        onChange={(event) => onChange({ ...value, is_active: event.target.checked })}
                    />
                    Apply to new assignments
                </label>
            </div>
        </Panel>
    );
}
