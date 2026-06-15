import { useEffect, useMemo, useRef, useState, type ChangeEvent, type ReactNode } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Tabs } from '@/shared/components/Tabs';
import { Textarea } from '@/shared/components/Textarea';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { lookupApi } from '@/shared/api/lookupApi';
import type { NamedResource } from '@/shared/types/common';
import { searchSuppliers } from '@/modules/supplier/supplierApi';
import { VehicleCategorySelect } from './VehicleCategorySelect';
import { VehicleMakeSelect } from './VehicleMakeSelect';
import { VehicleModelSelect } from './VehicleModelSelect';
import { VehicleTypeSelect } from './VehicleTypeSelect';
import type { Vehicle, VehicleAttributePayload, VehicleCategory, VehicleDocumentPayload, VehicleMake, VehicleModel, VehicleOwnerType, VehicleOwnershipPayload, VehiclePayload, VehicleType } from '../vehicleTypes';

type FormTab = 'basic' | 'ownership' | 'documents' | 'attributes' | 'review';

const statusOptions = ['active', 'inactive', 'under_service', 'rented', 'reserved', 'sold', 'blocked', 'scrapped'];
const fuelOptions = ['petrol', 'diesel', 'hybrid', 'electric', 'lpg', 'cng', 'other'];
const transmissionOptions = ['manual', 'automatic', 'semi_automatic', 'cvt', 'other'];
const documentTypes = ['registration', 'insurance', 'emission_test', 'revenue_license', 'fitness_certificate', 'lease_document', 'ownership_document', 'warranty', 'other'];
const ownershipTypes = ['owned', 'customer_owned', 'leased', 'rented', 'company_owned', 'third_party'];
const ownerTypes: Array<{ value: VehicleOwnerType; label: string }> = [
    { value: 'company', label: 'Company' },
    { value: 'customer', label: 'Customer' },
    { value: 'supplier', label: 'Supplier' },
    { value: 'third_party', label: 'Third party' },
];
const dataTypes = ['text', 'number', 'date', 'boolean', 'decimal'];

export function VehicleForm({ initial, submitting, error, enableRelations = false, onSubmit }: {
    initial?: Vehicle | null;
    submitting?: boolean;
    error: ApiError | null;
    enableRelations?: boolean;
    onSubmit: (payload: VehiclePayload, relations: { documents: VehicleDocumentPayload[]; ownerships: VehicleOwnershipPayload[]; attributes: VehicleAttributePayload[] }) => Promise<void>;
}) {
    const [tab, setTab] = useState<FormTab>('basic');
    const [make, setMake] = useState<VehicleMake | null>(initial?.make as VehicleMake ?? null);
    const [model, setModel] = useState<VehicleModel | null>(initial?.model as VehicleModel ?? null);
    const [type, setType] = useState<VehicleType | null>(initial?.type as VehicleType ?? null);
    const [category, setCategory] = useState<VehicleCategory | null>(initial?.category as VehicleCategory ?? null);
    const [ownershipOwnerType, setOwnershipOwnerType] = useState<VehicleOwnerType>('company');
    const [ownershipOwner, setOwnershipOwner] = useState<NamedResource | null>(null);
    const [payload, setPayload] = useState<VehiclePayload>({
        vehicle_number: initial?.vehicle_number ?? '',
        code: initial?.code ?? '',
        registration_number: initial?.registration_number ?? '',
        chassis_number: initial?.chassis_number ?? '',
        engine_number: initial?.engine_number ?? '',
        vin_number: initial?.vin_number ?? '',
        manufacture_year: initial?.manufacture_year ?? undefined,
        registration_date: initial?.registration_date ?? '',
        color: initial?.color ?? '',
        fuel_type: initial?.fuel_type ?? '',
        transmission_type: initial?.transmission_type ?? '',
        odometer_reading: initial?.odometer_reading ?? '0.000000',
        odometer_unit: initial?.odometer_unit ?? 'km',
        fuel_level: initial?.fuel_level ?? '',
        status: initial?.status ?? 'active',
        notes: initial?.notes ?? '',
    });
    const [ownerships, setOwnerships] = useState<VehicleOwnershipPayload[]>([]);
    const [documents, setDocuments] = useState<VehicleDocumentPayload[]>([]);
    const [attributes, setAttributes] = useState<VehicleAttributePayload[]>([]);
    const [ownershipDraft, setOwnershipDraft] = useState<VehicleOwnershipPayload>({ ownership_type: 'owned', started_at: new Date().toISOString().slice(0, 10), is_current: true });
    const [documentDraft, setDocumentDraft] = useState<VehicleDocumentPayload>({ document_type: 'registration', status: 'pending', document_number: '' });
    const [attributeDraft, setAttributeDraft] = useState<VehicleAttributePayload>({ attribute_key: '', attribute_value: '', data_type: 'text', sort_order: 0 });

    const finalPayload = useMemo<VehiclePayload>(() => ({
        ...payload,
        vehicle_make_id: make?.id ?? null,
        vehicle_model_id: model?.id ?? null,
        vehicle_type_id: type?.id ?? null,
        vehicle_category_id: category?.id ?? null,
    }), [category, make, model, payload, type]);
    const currentSnapshot = JSON.stringify({ finalPayload, documents, ownerships, attributes });
    const initialSnapshot = useRef(currentSnapshot);
    const submitLock = useRef(false);
    const dirty = currentSnapshot !== initialSnapshot.current;

    useEffect(() => {
        if (!dirty || submitting) return;
        const warn = (event: BeforeUnloadEvent) => event.preventDefault();
        window.addEventListener('beforeunload', warn);
        return () => window.removeEventListener('beforeunload', warn);
    }, [dirty, submitting]);

    const submit = async () => {
        if (submitting || submitLock.current) return;
        submitLock.current = true;
        try {
            await onSubmit(finalPayload, { documents, ownerships, attributes });
        } finally {
            submitLock.current = false;
        }
    };

    const input = (key: keyof VehiclePayload) => ({
        value: String(payload[key] ?? ''),
        onChange: (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => setPayload({ ...payload, [key]: event.target.value }),
        error: fieldError(error, key),
    });

    return (
        <Panel>
            <ErrorAlert error={error} />
            <Tabs<FormTab> active={tab} onChange={setTab} tabs={[
                { id: 'basic', label: 'Basic' },
                { id: 'ownership', label: 'Ownership' },
                { id: 'documents', label: 'Documents' },
                { id: 'attributes', label: 'Attributes' },
                { id: 'review', label: 'Review' },
            ]} />

            <div className="mt-5 space-y-5">
                {tab === 'basic' && (
                    <div className="grid gap-4 md:grid-cols-3">
                        <Input label="Vehicle Number" {...input('vehicle_number')} disabled={Boolean(initial)} />
                        <Input label="Code" {...input('code')} />
                        <Input label="Registration" {...input('registration_number')} />
                        <VehicleMakeSelect value={make} onChange={(value) => { setMake(value); if (!value) setModel(null); }} error={fieldError(error, 'vehicle_make_id')} />
                        <VehicleModelSelect makeId={make?.id} value={model} onChange={setModel} error={fieldError(error, 'vehicle_model_id')} />
                        <VehicleTypeSelect value={type} onChange={setType} error={fieldError(error, 'vehicle_type_id')} />
                        <VehicleCategorySelect value={category} onChange={setCategory} error={fieldError(error, 'vehicle_category_id')} />
                        <Select label="Status" options={statusOptions.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} {...input('status')} />
                        <Input label="Chassis" {...input('chassis_number')} />
                        <Input label="Engine" {...input('engine_number')} />
                        <Input label="VIN" {...input('vin_number')} />
                        <Input label="Manufacture Year" type="number" {...input('manufacture_year')} />
                        <Input label="Registration Date" type="date" {...input('registration_date')} />
                        <Input label="Color" {...input('color')} />
                        <Select label="Fuel" options={fuelOptions.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} {...input('fuel_type')} />
                        <Select label="Transmission" options={transmissionOptions.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} {...input('transmission_type')} />
                        <DecimalInput label="Odometer" value={String(payload.odometer_reading ?? '')} onChange={(event) => setPayload({ ...payload, odometer_reading: event.target.value })} error={fieldError(error, 'odometer_reading')} />
                        <Input label="Odometer Unit" {...input('odometer_unit')} />
                        <Input label="Fuel Level" {...input('fuel_level')} />
                        <div className="md:col-span-3"><Textarea label="Notes" {...input('notes')} /></div>
                    </div>
                )}

                {tab === 'ownership' && (
                    <RelationDraft disabled={!enableRelations} emptyMessage="Ownerships are saved from the detail page when editing.">
                        <div className="grid gap-3 md:grid-cols-4">
                            <Select label="Ownership" value={ownershipDraft.ownership_type} options={ownershipTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => {
                                const ownershipType = event.target.value as VehicleOwnershipPayload['ownership_type'];
                                setOwnershipDraft({ ...ownershipDraft, ownership_type: ownershipType });
                                setOwnershipOwnerType(defaultOwnerType(ownershipType));
                                setOwnershipOwner(null);
                            }} />
                            <Select label="Owner Type" value={ownershipOwnerType} options={ownerTypes} onChange={(event) => { setOwnershipOwnerType(event.target.value as VehicleOwnerType); setOwnershipOwner(null); }} />
                            <Input label="Started" type="date" value={ownershipDraft.started_at} onChange={(event) => setOwnershipDraft({ ...ownershipDraft, started_at: event.target.value })} />
                            {ownershipOwnerType !== 'company' && (
                                <LookupSelect
                                    label={ownershipOwnerType === 'customer' ? 'Customer' : 'Supplier / Owner'}
                                    value={ownershipOwner}
                                    onChange={setOwnershipOwner}
                                    search={ownershipOwnerType === 'customer' ? lookupApi.customers : searchSuppliers}
                                    error={fieldError(error, ownershipOwnerType === 'customer' ? 'ownerships.0.customer_id' : 'ownerships.0.owner_id')}
                                />
                            )}
                        </div>
                        <Button type="button" onClick={() => {
                            const ownerId = ownershipOwner?.id ?? null;
                            setOwnerships([...ownerships, {
                                ...ownershipDraft,
                                owner_type: ownershipOwnerType,
                                owner_id: ownershipOwnerType === 'company' ? null : ownerId,
                                customer_id: ownershipOwnerType === 'customer' ? ownerId : null,
                            }]);
                            setOwnershipDraft({ ownership_type: 'owned', started_at: new Date().toISOString().slice(0, 10), is_current: true });
                            setOwnershipOwnerType('company');
                            setOwnershipOwner(null);
                        }} disabled={ownershipOwnerType !== 'company' && !ownershipOwner}>Add Ownership</Button>
                        <Count label="Ownerships" count={ownerships.length} />
                    </RelationDraft>
                )}

                {tab === 'documents' && (
                    <RelationDraft disabled={!enableRelations} emptyMessage="Documents are saved from the detail page when editing.">
                        <div className="grid gap-3 md:grid-cols-3">
                            <Select label="Type" value={documentDraft.document_type} options={documentTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setDocumentDraft({ ...documentDraft, document_type: event.target.value as VehicleDocumentPayload['document_type'] })} />
                            <Input label="Number" value={documentDraft.document_number ?? ''} onChange={(event) => setDocumentDraft({ ...documentDraft, document_number: event.target.value })} />
                            <Input label="Expiry" type="date" value={documentDraft.expiry_date ?? ''} onChange={(event) => setDocumentDraft({ ...documentDraft, expiry_date: event.target.value })} />
                        </div>
                        <Button type="button" onClick={() => { setDocuments([...documents, documentDraft]); setDocumentDraft({ document_type: 'registration', status: 'pending', document_number: '' }); }}>Add Document</Button>
                        <Count label="Documents" count={documents.length} />
                    </RelationDraft>
                )}

                {tab === 'attributes' && (
                    <RelationDraft disabled={!enableRelations} emptyMessage="Attributes are saved from the detail page when editing.">
                        <div className="grid gap-3 md:grid-cols-3">
                            <Input label="Key" value={attributeDraft.attribute_key} onChange={(event) => setAttributeDraft({ ...attributeDraft, attribute_key: event.target.value })} />
                            <Input label="Value" value={attributeDraft.attribute_value ?? ''} onChange={(event) => setAttributeDraft({ ...attributeDraft, attribute_value: event.target.value })} />
                            <Select label="Type" value={attributeDraft.data_type} options={dataTypes.map((value) => ({ value, label: value }))} onChange={(event) => setAttributeDraft({ ...attributeDraft, data_type: event.target.value as VehicleAttributePayload['data_type'] })} />
                        </div>
                        <Button type="button" onClick={() => { setAttributes([...attributes, attributeDraft]); setAttributeDraft({ attribute_key: '', attribute_value: '', data_type: 'text', sort_order: attributes.length + 1 }); }}>Add Attribute</Button>
                        <Count label="Attributes" count={attributes.length} />
                    </RelationDraft>
                )}

                {tab === 'review' && (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                        <p className="font-semibold text-slate-900">{finalPayload.vehicle_number || 'Auto-numbered vehicle'}</p>
                        <p>{make?.name ?? 'No make'} / {model?.name ?? 'No model'} / {finalPayload.registration_number || 'No registration'}</p>
                        <p className="mt-2">Relations queued: {ownerships.length} ownerships, {documents.length} documents, {attributes.length} attributes.</p>
                    </div>
                )}

                <div className="flex justify-end">
                    <Button type="button" loading={submitting} onClick={submit}>{initial ? 'Save Vehicle' : 'Create Vehicle'}</Button>
                </div>
            </div>
        </Panel>
    );
}

function defaultOwnerType(ownershipType: VehicleOwnershipPayload['ownership_type']): VehicleOwnerType {
    if (ownershipType === 'customer_owned') return 'customer';
    if (ownershipType === 'rented' || ownershipType === 'third_party') return 'supplier';
    return 'company';
}

function Count({ label, count }: { label: string; count: number }) {
    return <p className="text-sm text-slate-500">{label}: {count}</p>;
}

function RelationDraft({ disabled, emptyMessage, children }: { disabled: boolean; emptyMessage: string; children: ReactNode }) {
    if (disabled) return <p className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">{emptyMessage}</p>;
    return <div className="space-y-4">{children}</div>;
}
