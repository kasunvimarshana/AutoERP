import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { Panel } from '@/shared/components/Panel';
import { TabPanel, Tabs } from '@/shared/components/Tabs';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import { useAuth } from '@/modules/auth/AuthProvider';
import { createVehicle, createVehicleWithRelations } from './vehicleApi';
import { hasVehiclePermission, vehiclePermissions } from './vehiclePermissions';
import { VehicleAttributeDraftEditor } from './components/VehicleAttributeDraftEditor';
import { VehicleBasicFields } from './components/VehicleBasicFields';
import { VehicleDocumentDraftEditor } from './components/VehicleDocumentDraftEditor';
import type { VehicleAttributePayload, VehicleCategory, VehicleDocumentPayload, VehicleMake, VehicleModel, VehiclePayload, VehicleType } from './vehicleTypes';

type CreateTab = 'basic' | 'documents' | 'attributes';
const tabs = [
    { id: 'basic' as const, label: 'Basic' },
    { id: 'documents' as const, label: 'Documents' },
    { id: 'attributes' as const, label: 'Attributes' },
];

export default function VehicleCreatePage() {
    const navigate = useNavigate();
    const auth = useAuth();
    const canCreate = hasVehiclePermission(auth, vehiclePermissions.create);
    const [activeTab, setActiveTab] = useState<CreateTab>('basic');
    const [make, setMake] = useState<VehicleMake | null>(null);
    const [model, setModel] = useState<VehicleModel | null>(null);
    const [type, setType] = useState<VehicleType | null>(null);
    const [category, setCategory] = useState<VehicleCategory | null>(null);
    const [payload, setPayload] = useState<VehiclePayload>(defaultVehiclePayload());
    const [documents, setDocuments] = useState<VehicleDocumentPayload[]>([]);
    const [attributes, setAttributes] = useState<VehicleAttributePayload[]>([]);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const finalPayload = useMemo<VehiclePayload>(() => ({
        ...payload,
        vehicle_make_id: make?.id ?? null,
        vehicle_model_id: model?.id ?? null,
        vehicle_type_id: type?.id ?? null,
        vehicle_category_id: category?.id ?? null,
    }), [category, make, model, payload, type]);
    const snapshot = JSON.stringify({ finalPayload, documents: documentsForSnapshot(documents), attributes });
    const [initialSnapshot] = useState(snapshot);
    const confirmDiscard = useUnsavedChanges(snapshot !== initialSnapshot && !submitting);

    if (!canCreate) {
        return (
            <div className="mx-auto max-w-6xl">
                <ContentHeader title="Create Vehicle" description="Create vehicle master data, documents, and attributes." />
                <CapabilityNotice>You do not have permission to create vehicles.</CapabilityNotice>
            </div>
        );
    }

    const submit = async () => {
        if (submitting) return;
        setSubmitting(true);
        setError(null);
        try {
            const vehicle = documents.length || attributes.length
                ? await createVehicleWithRelations({ vehicle: finalPayload, documents, attributes, ownerships: [] })
                : await createVehicle(finalPayload);
            navigate(`/vehicles/${vehicle.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="mx-auto max-w-6xl">
            <ContentHeader title="Create Vehicle" description="Create vehicle master data, documents, and attributes." />
            <ErrorAlert error={error} />
            <Panel className="p-0">
                <Tabs<CreateTab> id="vehicle-create-tabs" tabs={tabs} active={activeTab} onChange={setActiveTab} />
                <div className="p-5">
                    <TabPanel tabsId="vehicle-create-tabs" tabId="basic" active={activeTab}>
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
                        />
                    </TabPanel>
                    <TabPanel tabsId="vehicle-create-tabs" tabId="documents" active={activeTab}>
                        <VehicleDocumentDraftEditor documents={documents} onChange={setDocuments} error={error} />
                    </TabPanel>
                    <TabPanel tabsId="vehicle-create-tabs" tabId="attributes" active={activeTab}>
                        <VehicleAttributeDraftEditor attributes={attributes} onChange={setAttributes} error={error} />
                    </TabPanel>
                </div>
            </Panel>
            <FormActions>
                <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(-1)}>Cancel</Button>
                <Button type="button" loading={submitting} onClick={submit}>Create Vehicle</Button>
            </FormActions>
        </div>
    );
}

function documentsForSnapshot(documents: VehicleDocumentPayload[]) {
    return documents.map((document) => ({
        ...document,
        file: document.file ? { name: document.file.name, size: document.file.size, type: document.file.type } : null,
    }));
}

function defaultVehiclePayload(): VehiclePayload {
    return {
        vehicle_number: '',
        code: '',
        registration_number: '',
        chassis_number: '',
        engine_number: '',
        vin_number: '',
        manufacture_year: undefined,
        registration_date: '',
        color: '',
        fuel_type: '',
        transmission_type: '',
        odometer_reading: '0.000000',
        odometer_unit: 'km',
        fuel_level: '',
        status: 'active',
        notes: '',
    };
}
