import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { getEmployee, updateEmployee } from './hrApi';
import { EmployeeForm } from './components/EmployeeForm';
import type { Employee, EmployeePayload } from './hrTypes';

export default function EmployeeEditPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const [employee, setEmployee] = useState<Employee | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);

    useEffect(() => {
        const controller = new AbortController();
        getEmployee(id, controller.signal)
            .then((value) => {
                if (!controller.signal.aborted) setEmployee(value);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [id]);

    if (loading) return <LoadingState label="Loading employee..." />;
    if (!employee) return <ErrorAlert error={error} />;

    return (
        <div>
            <ContentHeader title={`Edit ${employee.display_name}`} />
            <EmployeeForm
                initial={employee}
                submitting={submitting}
                error={error}
                onDirty={formGuard.markDirty}
                onSubmit={async (payload: EmployeePayload) => {
                    if (submitting) return;
                    setSubmitting(true);
                    setError(null);
                    try {
                        await updateEmployee(id, payload);
                        formGuard.markSaved();
                        navigate(`/hr/employees/${id}`);
                    } catch (requestError) {
                        setError(toApiError(requestError));
                    } finally {
                        setSubmitting(false);
                    }
                }}
            />
        </div>
    );
}
