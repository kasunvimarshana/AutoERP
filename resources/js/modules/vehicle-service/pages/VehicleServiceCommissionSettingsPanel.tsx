import { useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import type { CommissionType } from '../vehicleServiceTypes';
import type { VehicleServiceSupervisorCommissionPolicy } from '../commissionTypes';
import {
    getSupervisorCommissionDefault,
    saveSupervisorCommissionDefault,
} from '../vehicleServiceApi';
import { vehicleServicePermissions } from '../vehicleServicePermissions';

const ZERO_AMOUNT = '0.000000';

export default function VehicleServiceCommissionSettingsPanel() {
    const auth = useAuth();
    const canManage = hasPermission(auth, vehicleServicePermissions.commissionsManage);
    const policy = useApi((signal) => getSupervisorCommissionDefault(signal), []);

    if (policy.loading) return <LoadingState label="Loading commission defaults..." />;

    return (
        <div className="mb-5 space-y-4">
            <ErrorAlert error={policy.error} />
            {!canManage && (
                <CapabilityNotice>
                    You can review the current default, but you do not have permission to change commission policies.
                </CapabilityNotice>
            )}
            <SupervisorPolicyForm
                key={policy.data?.row_version ?? 'new'}
                policy={policy.data ?? null}
                canManage={canManage}
                onSaved={policy.setData}
            />
        </div>
    );
}

function SupervisorPolicyForm({ policy, canManage, onSaved }: {
    policy: VehicleServiceSupervisorCommissionPolicy | null;
    canManage: boolean;
    onSaved: (policy: VehicleServiceSupervisorCommissionPolicy) => void;
}) {
    const [commissionType, setCommissionType] = useState<CommissionType>(policy?.commission_type ?? 'none');
    const [commissionValue, setCommissionValue] = useState(policy?.commission_value ?? ZERO_AMOUNT);
    const [isActive, setIsActive] = useState(policy?.is_active ?? true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    return (
        <Panel title="Default supervisor commission">
            <p className="mb-4 text-sm text-slate-600">
                This default is copied into a new Job Card as a snapshot. Percentage commissions use the complete job grand total; changing this policy does not recalculate existing jobs.
            </p>
            <ErrorAlert error={error} />
            <form className="space-y-5" onSubmit={(event) => {
                event.preventDefault();
                if (!canManage || saving) return;
                void save();
            }}>
                <div className="grid gap-4 md:grid-cols-3">
                    <Select
                        label="Commission type"
                        value={commissionType}
                        disabled={!canManage}
                        options={[
                            { value: 'none', label: 'None' },
                            { value: 'fixed', label: 'Fixed amount' },
                            { value: 'percentage', label: 'Percentage of whole job' },
                        ]}
                        onChange={(event) => setCommissionType(event.target.value as CommissionType)}
                    />
                    <DecimalInput
                        label="Commission value"
                        value={commissionValue}
                        disabled={!canManage || commissionType === 'none'}
                        onChange={(event) => setCommissionValue(event.target.value)}
                    />
                    <label className="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            checked={isActive}
                            disabled={!canManage}
                            onChange={(event) => setIsActive(event.target.checked)}
                        />
                        Apply this default to new jobs
                    </label>
                </div>
                {canManage && <div className="flex justify-end"><Button type="submit" loading={saving}>Save default</Button></div>}
            </form>
        </Panel>
    );

    async function save() {
        setSaving(true);
        setError(null);
        try {
            const saved = await saveSupervisorCommissionDefault({
                expected_version: policy?.row_version,
                commission_type: commissionType,
                commission_value: commissionType === 'none' ? ZERO_AMOUNT : commissionValue,
                is_active: isActive,
            });
            onSaved(saved);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }
}
