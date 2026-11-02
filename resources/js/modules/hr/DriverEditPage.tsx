import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { LinkButton } from '@/shared/components/Button';
import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { getDriver, updateDriver } from './driverApi';
import { DriverForm } from './components/DriverForm';
import type { DriverDetail, DriverPayload } from './driverTypes';

export default function DriverEditPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const [driver, setDriver] = useState<DriverDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError(null);

        getDriver(id, controller.signal)
            .then((data) => {
                if (!controller.signal.aborted) {
                    setDriver(data);
                }
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) {
                    setError(toApiError(requestError));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            });

        return () => controller.abort();
    }, [id]);

    const handleSubmit = async (payload: DriverPayload) => {
        if (submitting) return;
        setSubmitting(true);
        setError(null);

        try {
            await updateDriver(id, payload);
            formGuard.markSaved();
            navigate(`/hr/drivers/${id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) return <LoadingState label="Loading driver details..." />;
    if (!driver) return <ErrorAlert error={error} />;

    return (
        <div className="space-y-6">
            <ContentHeader
                title={`Edit Driver: ${driver.display_name}`}
                description={`Driver Code: ${driver.employee_number || driver.code || `ID #${driver.id}`}`}
                actions={
                    <div className="flex gap-2">
                        <LinkButton to={`/hr/drivers/${id}`} variant="secondary">
                            View Profile
                        </LinkButton>
                        <LinkButton to="/hr/drivers" variant="secondary">
                            Back to Drivers
                        </LinkButton>
                    </div>
                }
            />

            <DriverForm
                initial={driver}
                isEdit
                submitting={submitting}
                error={error}
                onDirty={formGuard.markDirty}
                onSubmit={handleSubmit}
            />
        </div>
    );
}
