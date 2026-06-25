import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Select } from '@/shared/components/Select';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { createEmployee, createEmployeeWithRelations } from './hrApi';
import { EmployeeForm } from './components/EmployeeForm';
import type { EmployeePayload, EmployeeRelationsPayload } from './hrTypes';

type CreateMode = 'simple' | 'one-shot';

export default function EmployeeCreatePage() {
    const navigate = useNavigate();
    const [mode, setMode] = useState<CreateMode>('one-shot');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);

    async function submit(employee: EmployeePayload, relations: EmployeeRelationsPayload) {
        if (submitting) return;
        setSubmitting(true);
        setError(null);
        try {
            const created = mode === 'one-shot'
                ? await createEmployeeWithRelations({ employee, ...relations })
                : await createEmployee(employee);
            formGuard.markSaved();
            navigate(`/hr/employees/${created.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div>
            <ContentHeader
                title="Create Employee"
                description="Capture workforce profile and related records in one transaction."
            />
            <div className="mb-4 max-w-xs">
                <Select
                    label="Create mode"
                    value={mode}
                    options={[
                        { value: 'one-shot', label: 'Employee with details' },
                        { value: 'simple', label: 'Simple employee' },
                    ]}
                    onChange={(event) => {
                        formGuard.markDirty();
                        setMode(event.target.value as CreateMode);
                    }}
                />
            </div>
            <EmployeeForm
                oneShot={mode === 'one-shot'}
                submitting={submitting}
                error={error}
                onDirty={formGuard.markDirty}
                onSubmit={submit}
            />
        </div>
    );
}
