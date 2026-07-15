import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import type { VehicleServiceLaborItemCommissionRule } from '../commissionTypes';
import {
    getLaborItemCommissionRule,
    saveLaborItemCommissionRule,
} from '../vehicleServiceApi';
import {
    emptyLaborItemCommissionDraft,
    LaborItemCommissionPanel,
    type LaborItemCommissionDraft,
} from './LaborItemCommissionPanel';

export default function LaborItemCommissionEditor({ itemId, canManage }: {
    itemId: number;
    canManage: boolean;
}) {
    const rule = useApi(
        (signal) => getLaborItemCommissionRule(itemId, signal),
        [itemId],
    );

    if (rule.loading) return <LoadingState label="Loading labor commission..." />;
    if (rule.error) return <ErrorAlert error={rule.error} />;

    return (
        <div className="space-y-4">
            {!canManage && (
                <CapabilityNotice>
                    You can review this labor commission, but you do not have permission to update it.
                </CapabilityNotice>
            )}
            <LaborItemCommissionForm
                key={rule.data?.row_version ?? 'new'}
                itemId={itemId}
                rule={rule.data}
                canManage={canManage}
                onSaved={rule.setData}
            />
        </div>
    );
}

function LaborItemCommissionForm({ itemId, rule, canManage, onSaved }: {
    itemId: number;
    rule: VehicleServiceLaborItemCommissionRule | null;
    canManage: boolean;
    onSaved: (rule: VehicleServiceLaborItemCommissionRule) => void;
}) {
    const [draft, setDraft] = useState<LaborItemCommissionDraft>(() => rule === null
        ? emptyLaborItemCommissionDraft
        : {
            commission_type: rule.commission_type,
            commission_value: rule.commission_value,
            is_active: rule.is_active,
        });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    return (
        <form
            className="space-y-4"
            onSubmit={(event) => {
                event.preventDefault();
                if (!canManage || saving) return;
                void save();
            }}
        >
            <ErrorAlert error={error} />
            <LaborItemCommissionPanel
                value={draft}
                onChange={setDraft}
                disabled={!canManage || saving}
            />
            {canManage && (
                <div className="flex justify-end">
                    <Button type="submit" loading={saving}>Save commission</Button>
                </div>
            )}
        </form>
    );

    async function save() {
        setSaving(true);
        setError(null);
        try {
            const saved = await saveLaborItemCommissionRule(itemId, {
                expected_version: rule?.row_version,
                commission_type: draft.commission_type,
                commission_value: draft.commission_type === 'none'
                    ? emptyLaborItemCommissionDraft.commission_value
                    : draft.commission_value.trim() || emptyLaborItemCommissionDraft.commission_value,
                is_active: draft.is_active,
            });
            onSaved(saved);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }
}
