import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { LinkButton } from '@/shared/components/Button';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { createDriver } from './driverApi';
import { DriverForm } from './components/DriverForm';
import type { DriverPayload } from './driverTypes';

export default function DriverCreatePage() {
    const navigate = useNavigate();
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);

    const handleSubmit = async (payload: DriverPayload) => {
        if (submitting) return;
        setSubmitting(true);
        setError(null);

        try {
            const created = await createDriver(payload);
            formGuard.markSaved();
            navigate(`/hr/drivers/${created.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Register Driver"
                description="Register a new chauffeur/driver with driving license endorsements, contact details, and daily allowances."
                actions={
                    <LinkButton to="/hr/drivers" variant="secondary">
                        Back to Drivers
                    </LinkButton>
                }
            />

            <DriverForm
                submitting={submitting}
                error={error}
                onDirty={formGuard.markDirty}
                onSubmit={handleSubmit}
            />
        </div>
    );
}
