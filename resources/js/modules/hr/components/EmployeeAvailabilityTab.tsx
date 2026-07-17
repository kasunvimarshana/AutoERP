import { useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { RecordTable } from '@/shared/components/RecordTable';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { availabilityApi } from '../hrApi';
import { hrPermissions } from '../hrPermissions';
import type { EmployeeAvailabilityPayload } from '../hrTypes';

const availabilityStatuses: EmployeeAvailabilityPayload['availability_status'][] = [
    'available',
    'assigned',
    'on_leave',
    'unavailable',
    'suspended',
    'inactive',
];

export function EmployeeAvailabilityTab({ employeeId }: { employeeId: number }) {
    const auth = useAuth();
    const canManage = hasPermission(auth, hrPermissions.employeesUpdate);
    const result = useApi((signal) => availabilityApi.list(employeeId, 1, signal), [employeeId]);
    const [draft, setDraft] = useState<EmployeeAvailabilityPayload>({ availability_status: 'available', availability_date: businessDateInputValue(), reason: '' });
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);

    const save = async () => {
        if (!canManage) return;
        setSaving(true);
        try {
            await availabilityApi.create(employeeId, draft);
            result.reload();
            setError(null);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    return <div className="space-y-4">
        <ErrorAlert error={error ?? result.error} />
        {canManage && <div className="grid gap-3 md:grid-cols-4">
            <Select label="Status" value={draft.availability_status} options={availabilityStatuses.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setDraft({ ...draft, availability_status: event.target.value as EmployeeAvailabilityPayload['availability_status'] })} />
            <Input label="Date" type="date" value={draft.availability_date ?? ''} onChange={(event) => setDraft({ ...draft, availability_date: event.target.value })} />
            <Input label="Reason" value={draft.reason ?? ''} onChange={(event) => setDraft({ ...draft, reason: event.target.value })} />
            <div className="self-end"><Button loading={saving} onClick={save}>Record Availability</Button></div>
        </div>}
        {result.loading ? <LoadingState label="Loading availability..." /> : <RecordTable rows={(result.data?.data ?? []) as unknown as Record<string, unknown>[]} fields={['availability_date', 'availability_status', 'source_type', 'reason', 'starts_at', 'ends_at']} rowKey={(row, index) => String(row.id ?? `${String(row.availability_date ?? 'availability')}-${String(row.source_type ?? index)}`)} />}
    </div>;
}
