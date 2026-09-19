import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import { useAuth } from '@/modules/auth/AuthProvider';
import { createVehicle } from './vehicleApi';
import { defaultVehiclePayload, loadVehicleCreationDefaults } from './vehicleCreateDefaults';
import { hasVehiclePermission, vehiclePermissions } from './vehiclePermissions';
import { VehicleBasicFields } from './components/VehicleBasicFields';
import type { VehicleMake, VehicleModel, VehiclePayload, VehicleType } from './vehicleTypes';

export default function VehicleCreatePage() {
    const navigate = useNavigate();
    const auth = useAuth();
    const canCreate = hasVehiclePermission(auth, vehiclePermissions.create);
    const [make, setMake] = useState<VehicleMake | null>(null);
    const [model, setModel] = useState<VehicleModel | null>(null);
    const [type, setType] = useState<VehicleType | null>(null);
    const [payload, setPayload] = useState<VehiclePayload>(defaultVehiclePayload());
    const [initialSnapshot, setInitialSnapshot] = useState<string | null>(null);
    const [loadingDefaults, setLoadingDefaults] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const finalPayload = useMemo<VehiclePayload>(() => ({
        ...payload,
        vehicle_make_id: make?.id ?? null,
        vehicle_model_id: model?.id ?? null,
        vehicle_type_id: type?.id ?? null,
        vehicle_category_id: null,
    }), [make, model, payload, type]);
    const snapshot = JSON.stringify(finalPayload);
    const confirmDiscard = useUnsavedChanges(initialSnapshot !== null && snapshot !== initialSnapshot && !submitting);

    useEffect(() => {
        const controller = new AbortController();

        void loadVehicleCreationDefaults(controller.signal)
            .then(({ code }) => {
                const defaults = { ...defaultVehiclePayload(), code };
                setPayload(defaults);
                setInitialSnapshot(JSON.stringify({
                    ...defaults,
                    vehicle_make_id: null,
                    vehicle_model_id: null,
                    vehicle_type_id: null,
                    vehicle_category_id: null,
                }));
                setLoadingDefaults(false);
            })
            .catch((requestError) => {
                if (controller.signal.aborted) return;
                setError(toApiError(requestError));
                setLoadingDefaults(false);
            });

        return () => controller.abort();
    }, []);

    if (!canCreate) {
        return (
            <div className="mx-auto max-w-6xl">
                <ContentHeader title="Create Vehicle" description="Create the essential vehicle master data." />
                <CapabilityNotice>You do not have permission to create vehicles.</CapabilityNotice>
            </div>
        );
    }

    const submit = async () => {
        if (submitting) return;
        setSubmitting(true);
        setError(null);
        try {
            const vehicle = await createVehicle(finalPayload);
            navigate(`/vehicles/${vehicle.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="mx-auto max-w-6xl">
            <ContentHeader title="Create Vehicle" description="Create the essential vehicle master data." />
            <ErrorAlert error={error} />
            {loadingDefaults && <LoadingState label="Preparing vehicle defaults..." />}
            {!loadingDefaults && initialSnapshot !== null && (
                <Panel>
                    <VehicleBasicFields
                        value={payload}
                        onChange={setPayload}
                        make={make}
                        onMakeChange={setMake}
                        model={model}
                        onModelChange={setModel}
                        type={type}
                        onTypeChange={setType}
                        error={error}
                        creating
                    />
                </Panel>
            )}
            {!loadingDefaults && initialSnapshot !== null && (
            <FormActions>
                <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(-1)}>Cancel</Button>
                <Button type="button" loading={submitting} onClick={submit}>Create Vehicle</Button>
            </FormActions>
            )}
        </div>
    );
}
