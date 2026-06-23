import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { TabPanel, Tabs } from '@/shared/components/Tabs';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import { useAuth } from '@/modules/auth/AuthProvider';
import { getVehicle, updateVehicle } from './vehicleApi';
import { VehicleAttributeTab } from './components/VehicleAttributeTab';
import { VehicleBasicFields } from './components/VehicleBasicFields';
import { VehicleDocumentTab } from './components/VehicleDocumentTab';
import { hasVehiclePermission, vehiclePermissions } from './vehiclePermissions';
import type { Vehicle, VehicleCategory, VehicleMake, VehicleModel, VehiclePayload, VehicleType } from './vehicleTypes';

type EditTab = 'basic' | 'documents' | 'attributes';
const tabs = [
    { id: 'basic' as const, label: 'Basic' },
    { id: 'documents' as const, label: 'Documents' },
    { id: 'attributes' as const, label: 'Attributes' },
];

export default function VehicleEditPage() {
    const vehicleId = Number(useParams().id);
    const navigate = useNavigate();
    const auth = useAuth();
    const canUpdate = hasVehiclePermission(auth, vehiclePermissions.update);
    const [vehicle, setVehicle] = useState<Vehicle | null>(null);
    const [activeTab, setActiveTab] = useState<EditTab>('basic');
    const [make, setMake] = useState<VehicleMake | null>(null);
    const [model, setModel] = useState<VehicleModel | null>(null);
    const [type, setType] = useState<VehicleType | null>(null);
    const [category, setCategory] = useState<VehicleCategory | null>(null);
    const [payload, setPayload] = useState<VehiclePayload | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const initialSnapshot = useRef<string | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        getVehicle(vehicleId, controller.signal)
            .then((value) => {
                if (controller.signal.aborted) return;
                setVehicle(value);
                setMake(value.make as VehicleMake ?? null);
                setModel(value.model as VehicleModel ?? null);
                setType(value.type as VehicleType ?? null);
                setCategory(value.category as VehicleCategory ?? null);
                const mapped = vehicleToPayload(value);
                setPayload(mapped);
                initialSnapshot.current = JSON.stringify({
                    payload: mapped,
                    makeId: value.make?.id ?? null,
                    modelId: value.model?.id ?? null,
                    typeId: value.type?.id ?? null,
                    categoryId: value.category?.id ?? null,
                });
                setError(null);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [vehicleId]);

    const finalPayload = useMemo<VehiclePayload | null>(() => payload === null ? null : ({
        ...payload,
        vehicle_make_id: make?.id ?? null,
        vehicle_model_id: model?.id ?? null,
        vehicle_type_id: type?.id ?? null,
        vehicle_category_id: category?.id ?? null,
    }), [category, make, model, payload, type]);
    const snapshot = finalPayload === null ? '' : JSON.stringify({
        payload: finalPayload,
        makeId: make?.id ?? null,
        modelId: model?.id ?? null,
        typeId: type?.id ?? null,
        categoryId: category?.id ?? null,
    });
    const confirmDiscard = useUnsavedChanges(Boolean(initialSnapshot.current && snapshot !== initialSnapshot.current && !submitting));

    if (!canUpdate) {
        return (
            <div className="mx-auto max-w-6xl">
                <ContentHeader title="Edit Vehicle" description={vehicle?.vehicle_number ?? undefined} />
                <CapabilityNotice>You do not have permission to edit vehicles.</CapabilityNotice>
            </div>
        );
    }

    const submit = async () => {
        if (submitting || finalPayload === null) return;
        setSubmitting(true);
        setError(null);
        try {
            const editablePayload = { ...finalPayload };
            delete editablePayload.status;
            delete editablePayload.vehicle_number;
            const updated = await updateVehicle(vehicleId, editablePayload);
            navigate(`/vehicles/${updated.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    if (loading || payload === null) return <LoadingState label="Loading vehicle..." />;
    if (!vehicle) return <ErrorAlert error={error} />;

    return (
        <div className="mx-auto max-w-6xl">
            <ContentHeader title="Edit Vehicle" description={vehicle.vehicle_number} />
            <ErrorAlert error={error} />
            <Panel className="p-0">
                <Tabs<EditTab> id="vehicle-edit-tabs" tabs={tabs} active={activeTab} onChange={setActiveTab} />
                <div className="p-5">
                    <TabPanel tabsId="vehicle-edit-tabs" tabId="basic" active={activeTab}>
                        <VehicleBasicFields
                            value={payload}
                            onChange={setPayload}
                            make={make}
                            onMakeChange={setMake}
                            model={model}
                            onModelChange={setModel}
                            type={type}
                            onTypeChange={setType}
                            category={category}
                            onCategoryChange={setCategory}
                            error={error}
                            vehicleNumberReadOnly
                            statusReadOnly
                        />
                    </TabPanel>
                    <TabPanel tabsId="vehicle-edit-tabs" tabId="documents" active={activeTab}>
                        <VehicleDocumentTab vehicleId={vehicle.id} />
                    </TabPanel>
                    <TabPanel tabsId="vehicle-edit-tabs" tabId="attributes" active={activeTab}>
                        <VehicleAttributeTab vehicleId={vehicle.id} />
                    </TabPanel>
                </div>
            </Panel>
            <FormActions>
                <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(-1)}>Cancel</Button>
                <Button type="button" loading={submitting} onClick={submit}>Save Changes</Button>
            </FormActions>
        </div>
    );
}

function vehicleToPayload(vehicle: Vehicle): VehiclePayload {
    return {
        vehicle_number: vehicle.vehicle_number ?? '',
        code: vehicle.code ?? '',
        registration_number: vehicle.registration_number ?? '',
        chassis_number: vehicle.chassis_number ?? '',
        engine_number: vehicle.engine_number ?? '',
        vin_number: vehicle.vin_number ?? '',
        manufacture_year: vehicle.manufacture_year ?? undefined,
        registration_date: vehicle.registration_date ?? '',
        color: vehicle.color ?? '',
        fuel_type: vehicle.fuel_type ?? '',
        transmission_type: vehicle.transmission_type ?? '',
        odometer_reading: vehicle.odometer_reading ?? '0.000000',
        odometer_unit: vehicle.odometer_unit ?? 'km',
        fuel_level: vehicle.fuel_level ?? '',
        status: vehicle.status ?? 'active',
        notes: vehicle.notes ?? '',
    };
}
