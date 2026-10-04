import { useCallback, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi, type ItemLookupResource, type VehicleLookupResource } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { lineValueWithItem, VehicleServiceLineItemLookup } from './line-editor/LineItemFields';
import { VehicleServiceLineForm } from './line-editor/VehicleServiceLineForm';
import { calculateLinePreview, emptyLineForm, lineFormToPayload, type VehicleServiceLineFormValue } from './line-editor/lineForm';
import { VehicleServiceLineTable } from './VehicleServiceLineEditor';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { addDecimal, isDecimalString } from '@/shared/utils/decimal';
import {
    createVehicleServiceJob,
    updateVehicleServiceJob,
} from '../vehicleServiceApi';
import type { CommissionType, VehicleServiceJob, VehicleServiceJobLine, VehicleServiceJobPayload, VehicleServiceJobType } from '../vehicleServiceTypes';
import { VehicleServiceQuickVehicleModal } from './VehicleServiceQuickVehicleModal';

const ZERO_AMOUNT = '0.000000';
const NO_COMMISSION_TYPE: CommissionType = 'none';
const JOB_TYPE = {
    FULL_SERVICE: 'full_service',
    BODY_WASH: 'body_wash',
    OIL_CHANGE: 'oil_change',
    ACCESSORIES: 'accessories',
} as const satisfies Record<string, VehicleServiceJobType>;
const JOB_TYPE_OPTIONS = [
    { value: JOB_TYPE.FULL_SERVICE, label: 'Full Service' },
    { value: JOB_TYPE.BODY_WASH, label: 'Body Wash' },
    { value: JOB_TYPE.OIL_CHANGE, label: 'Oil Change' },
    { value: JOB_TYPE.ACCESSORIES, label: 'Accessories' },
];
const MILEAGE_TRACKED_JOB_TYPES: VehicleServiceJobType[] = [JOB_TYPE.FULL_SERVICE, JOB_TYPE.OIL_CHANGE];
const SERVICE_MILEAGE_INTERVAL = '5000';
const DISABLED_MILEAGE_CLASS = 'disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400 disabled:opacity-100';
const today = businessDateInputValue;
const decimal = (value: string, fallback = ZERO_AMOUNT) => value.trim() || fallback;
const tracksMileage = (jobType: VehicleServiceJobType): boolean => MILEAGE_TRACKED_JOB_TYPES.includes(jobType);
const suggestedNextServiceMileage = (odometerReading: string): string => {
    const value = odometerReading.trim();

    return value !== '' && isDecimalString(value)
        ? addDecimal(value, SERVICE_MILEAGE_INTERVAL)
        : '';
};
const customerLabel = (customer: NamedResource | null) => customer ? `${customer.code ?? ''} ${customer.name}`.trim() : '';
const vehicleCustomer = (selectedVehicle: VehicleLookupResource | null, fallback: NamedResource | null): NamedResource | null => {
    if (selectedVehicle?.current_customer) {
        return {
            id: selectedVehicle.current_customer.id,
            code: selectedVehicle.current_customer.code,
            name: selectedVehicle.current_customer.name,
        };
    }

    return fallback;
};
const currentCustomerOwner = (vehicle: VehicleLookupResource | null, fallback: NamedResource | null) =>
    vehicle?.current_customer?.name ?? fallback?.name ?? '-';
const vehicleLookupLabel = (vehicle: VehicleLookupResource): string =>
    vehicle.registration_number?.trim() || vehicle.name?.trim() || vehicle.code?.trim() || '';

interface DraftJobLine {
    key: number;
    value: VehicleServiceLineFormValue;
}

let nextDraftLineKey = -1;

export function VehicleServiceJobForm({ job }: { job?: VehicleServiceJob }) {
    const supervisorRequiredMessage = 'Select a valid supervisor from the list.';
    const navigate = useNavigate();
    const isCreating = job === undefined;
    const [customer, setCustomer] = useState<NamedResource | null>(vehicleCustomer(job?.vehicle ?? null, job?.customer ?? null));
    const [billToCustomer, setBillToCustomer] = useState<NamedResource | null>(job?.bill_to_customer ?? vehicleCustomer(job?.vehicle ?? null, job?.customer ?? null));
    const [vehicle, setVehicle] = useState<VehicleLookupResource | null>(job?.vehicle ?? null);
    const [supervisor, setSupervisor] = useState<NamedResource | null>(job?.supervisor ?? null);
    const [quickVehicleNumber, setQuickVehicleNumber] = useState('');
    const [quickVehicleModalOpen, setQuickVehicleModalOpen] = useState(false);
    const [form, setForm] = useState({
        job_date: job?.job_date ?? today(),
        expected_delivery_date: job?.expected_delivery_date ?? '',
        type: job?.type ?? JOB_TYPE.FULL_SERVICE,
        supervisor_commission_type: (job?.supervisor_commission_type ?? null) as CommissionType | null,
        supervisor_commission_value: (job?.supervisor_commission_value ?? null) as string | null,
        odometer_reading: job && !tracksMileage(job.type) ? '' : job?.odometer_reading ?? '',
        next_service_mileage: job && !tracksMileage(job.type) ? '' : job?.next_service_mileage ?? '',
        manual_job_card: job?.manual_job_card ?? '',
        fuel_level: job?.fuel_level ?? '',
        priority: job?.priority ?? 'normal',
        customer_complaint: job?.inspection?.customer_complaint ?? '',
        notes: job?.notes ?? '',
    });
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [attemptedSubmit, setAttemptedSubmit] = useState(false);
    const [activeTab, setActiveTab] = useState<'details' | 'lines'>('details');
    const [selectedItem, setSelectedItem] = useState<ItemLookupResource | null>(null);
    const [draftLines, setDraftLines] = useState<DraftJobLine[]>([]);
    const [editingDraftLine, setEditingDraftLine] = useState<DraftJobLine | null>(null);
    const [removeDraftLineId, setRemoveDraftLineId] = useState<number | null>(null);
    const formGuard = useMutationFormGuard(submitting);
    const updateForm = useCallback((next: Parameters<typeof setForm>[0]) => {
        formGuard.markDirty();
        setForm(next);
    }, [formGuard]);
    const errorFor = (key: string) => fieldError(error, key);

    const searchVehicle = useCallback((params: LookupLoadParams) => {
        return lookupApi.serviceVehicles(params);
    }, []);
    const searchSupervisor = useCallback((params: LookupLoadParams) => {
        return lookupApi.availableSupervisors(params);
    }, []);

    const supervisorCommissionPayload = (): Partial<Pick<
        VehicleServiceJobPayload,
        'supervisor_commission_type' | 'supervisor_commission_value'
    >> => {
        const commissionType = form.supervisor_commission_type;
        if (isCreating || supervisor === null || commissionType === null) {
            return {};
        }

        return {
            supervisor_commission_type: commissionType,
            supervisor_commission_value: commissionType === NO_COMMISSION_TYPE
                ? ZERO_AMOUNT
                : decimal(form.supervisor_commission_value ?? ''),
        };
    };

    const payload = (): VehicleServiceJobPayload => ({
        expected_version: job?.row_version,
        job_date: form.job_date,
        expected_delivery_date: form.expected_delivery_date || undefined,
        type: form.type,
        customer_id: customer?.id ?? 0,
        bill_to_customer_id: billToCustomer?.id ?? customer?.id ?? 0,
        vehicle_id: vehicle?.id ?? 0,
        supervisor_employee_id: supervisor?.id,
        ...supervisorCommissionPayload(),
        odometer_reading: form.odometer_reading || undefined,
        next_service_mileage: form.next_service_mileage || undefined,
        manual_job_card: form.manual_job_card || undefined,
        fuel_level: form.fuel_level || undefined,
        priority: form.priority || undefined,
        customer_complaint: form.customer_complaint,
        notes: form.notes || undefined,
        ...(isCreating ? { lines: draftLines.map(({ value }) => lineFormToPayload(value)) } : {}),
    });

    const applyVehicle = useCallback((value: VehicleLookupResource | null) => {
        formGuard.markDirty();
        const nextCustomer = vehicleCustomer(value, null);
        setVehicle(value);
        setCustomer(nextCustomer);
        setBillToCustomer(nextCustomer);
        if (value?.odometer_reading && !form.odometer_reading && tracksMileage(form.type)) {
            updateForm((current) => ({
                ...current,
                odometer_reading: value.odometer_reading ?? '',
                next_service_mileage: suggestedNextServiceMileage(value.odometer_reading ?? ''),
            }));
        }
    }, [form.odometer_reading, form.type, formGuard, updateForm]);

    const applyJobType = useCallback((next: VehicleServiceJobType) => {
        updateForm((current) => ({
            ...current,
            type: next,
            odometer_reading: '',
            next_service_mileage: '',
        }));
    }, [updateForm]);

    const applyOdometerReading = useCallback((next: string) => {
        updateForm((current) => ({
            ...current,
            odometer_reading: next,
            next_service_mileage: suggestedNextServiceMileage(next),
        }));
    }, [updateForm]);

    return (
        <>
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                setAttemptedSubmit(true);
                if (supervisor === null) return;

                setSubmitting(true);
                setError(null);
                try {
                    const saved = job
                        ? await updateVehicleServiceJob(job.id, payload())
                        : await createVehicleServiceJob(payload());
                    formGuard.markSaved();
                    navigate(`/vehicle-service/jobs/${saved.id}`);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}>
                <ErrorAlert error={error} />
                {isCreating && (
                    <div className="border-b border-slate-200">
                        <div className="flex gap-6" role="tablist" aria-label="Service job details">
                            <button type="button" role="tab" aria-selected={activeTab === 'details'} className={`border-b-2 px-2 py-3 text-sm font-medium ${activeTab === 'details' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-600 hover:text-slate-900'}`} onClick={() => setActiveTab('details')}>Job details</button>
                            <button type="button" role="tab" aria-selected={activeTab === 'lines'} className={`border-b-2 px-2 py-3 text-sm font-medium ${activeTab === 'lines' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-600 hover:text-slate-900'}`} onClick={() => setActiveTab('lines')}>Job lines{draftLines.length > 0 ? ` (${draftLines.length})` : ''}</button>
                        </div>
                    </div>
                )}
                {(!isCreating || activeTab === 'details') && (
                <Panel title="Service job">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div>
                            <p className="text-sm font-semibold text-slate-900">Vehicle selection</p>
                            <p className="text-sm text-slate-500">Search an existing vehicle or register a new vehicle before continuing the job.</p>
                        </div>
                        <Button type="button" variant="secondary" onClick={() => {
                            setQuickVehicleNumber('');
                            setQuickVehicleModalOpen(true);
                        }}>Add new vehicle</Button>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <GenericLookupSelect
                            label="Vehicle"
                            value={vehicle}
                            onChange={applyVehicle}
                            search={searchVehicle}
                            formatLabel={vehicleLookupLabel}
                            error={errorFor('vehicle_id')}
                            placeholder="Search by registration number or model"
                            loadOnOpen
                            minSearchLength={0}
                            debounceMs={1000}
                            renderEmptyState={({ searchText }) => {
                                const vehicleNumber = searchText.trim();
                                if (vehicleNumber.length < 2) {
                                    return <span className="px-3 py-2 text-sm text-slate-500">No matching vehicle found.</span>;
                                }

                                return (
                                    <div className="space-y-2 px-3 py-2">
                                        <p className="text-sm text-slate-500">No matching vehicle found.</p>
                                        <button
                                            type="button"
                                            className="w-full rounded-lg border border-dashed border-sky-200 bg-sky-50 px-3 py-3 text-left text-sm text-sky-900 hover:border-sky-300 hover:bg-sky-100"
                                            onMouseDown={(event) => event.preventDefault()}
                                            onClick={() => {
                                                setQuickVehicleNumber(vehicleNumber);
                                                setQuickVehicleModalOpen(true);
                                            }}
                                        >
                                            <span className="block font-semibold">Register new vehicle</span>
                                            <span className="mt-1 block text-xs text-sky-700">Create vehicle {vehicleNumber} and continue this job without leaving the screen.</span>
                                        </button>
                                    </div>
                                );
                            }}
                        />
                        <Input label="Customer" value={customerLabel(customer)} error={errorFor('customer_id')} placeholder="Selected vehicle owner" readOnly />
                        <GenericLookupSelect
                            label="Supervisor"
                            value={supervisor}
                            onChange={(value) => {
                                formGuard.markDirty();
                                setSupervisor(value);
                                if (value !== null) {
                                    setAttemptedSubmit(false);
                                }
                            }}
                            search={searchSupervisor}
                            formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()}
                            error={errorFor('supervisor_employee_id') || (attemptedSubmit && supervisor === null ? supervisorRequiredMessage : undefined)}
                            required
                            loadOnOpen
                            minSearchLength={0}
                            debounceMs={0}
                        />
                        <DecimalInput
                            label="Odometer"
                            value={form.odometer_reading}
                            error={errorFor('odometer_reading')}
                            hint={!tracksMileage(form.type) ? `Not applicable to ${JOB_TYPE_OPTIONS.find((option) => option.value === form.type)?.label}.` : undefined}
                            className={DISABLED_MILEAGE_CLASS}
                            required={tracksMileage(form.type)}
                            disabled={!tracksMileage(form.type)}
                            onChange={(event) => applyOdometerReading(event.target.value)}
                        />
                        <DecimalInput
                            label="Next Service Mileage"
                            value={form.next_service_mileage}
                            error={errorFor('next_service_mileage')}
                            hint={!tracksMileage(form.type) ? `Not applicable to ${JOB_TYPE_OPTIONS.find((option) => option.value === form.type)?.label}.` : undefined}
                            className={DISABLED_MILEAGE_CLASS}
                            disabled={!tracksMileage(form.type)}
                            onChange={(event) => updateForm({ ...form, next_service_mileage: event.target.value })}
                        />
                        <Input label="Manual Job Card" value={form.manual_job_card} error={errorFor('manual_job_card')} onChange={(event) => updateForm({ ...form, manual_job_card: event.target.value })} />
                        <Select label="Type" value={form.type} options={JOB_TYPE_OPTIONS} error={errorFor('type')} onChange={(event) => applyJobType(event.target.value as VehicleServiceJobType)} />
                    </div>
                    {vehicle && <div className="mt-4 grid gap-3 rounded-lg border border-sky-100 bg-sky-50 p-4 text-sm sm:grid-cols-2 xl:grid-cols-5">
                        <VehicleContext label="Registration" value={vehicle.registration_number ?? vehicle.name} />
                        <VehicleContext label="Make" value={vehicle.make?.name ?? '-'} />
                        <VehicleContext label="Model" value={vehicle.model?.name ?? '-'} />
                        <VehicleContext label="Owner" value={currentCustomerOwner(vehicle, customer)} />
                        <VehicleContext label="Odometer" value={`${vehicle.odometer_reading ?? '-'} ${vehicle.odometer_unit ?? ''}`.trim()} />
                    </div>}
                    <div className="mt-4 grid gap-4 lg:grid-cols-2">
                        <Textarea label="Customer complaint" value={form.customer_complaint} error={errorFor('customer_complaint')} onChange={(event) => updateForm({ ...form, customer_complaint: event.target.value })} />
                        <Textarea label="Notes" value={form.notes} error={errorFor('notes')} onChange={(event) => updateForm({ ...form, notes: event.target.value })} />
                    </div>
                </Panel>
                )}
                {isCreating && activeTab === 'lines' && (
                    <Panel title="Job lines">
                        <p className="mb-4 text-sm text-slate-600">Add items to the draft and review their service prices. Lines are saved when you save the job.</p>
                        <VehicleServiceLineItemLookup
                            value={selectedItem}
                            required={false}
                            onChange={(item) => {
                                setSelectedItem(null);
                                if (item) {
                                    const value = lineValueWithItem(emptyLineForm(), item);
                                    setDraftLines((current) => [...current, { key: nextDraftLineKey--, value }]);
                                    formGuard.markDirty();
                                }
                            }}
                        />
                        <VehicleServiceLineTable
                            lines={draftLines.map(toDraftLinePreview)}
                            loading={false}
                            canManageLines
                            canViewInventory={false}
                            inventoryOnly={false}
                            mutationDisabled={submitting}
                            onEdit={(line) => {
                                const draftLine = draftLines.find((entry) => entry.key === line.id);
                                if (draftLine) setEditingDraftLine(draftLine);
                            }}
                            onQuantityChange={(line, quantity) => {
                                formGuard.markDirty();
                                setDraftLines((current) => current.map((entry) => entry.key === line.id
                                    ? { ...entry, value: { ...entry.value, quantity } }
                                    : entry));
                            }}
                            onRemove={(line) => setRemoveDraftLineId(line.id)}
                        />
                    </Panel>
                )}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{job ? 'Save job' : 'Save draft'}</Button>
                </div>
            </form>
            <VehicleServiceQuickVehicleModal
                key={`${quickVehicleModalOpen ? 'open' : 'closed'}:${quickVehicleNumber}`}
                open={quickVehicleModalOpen}
                initialVehicleNumber={quickVehicleNumber}
                onClose={() => setQuickVehicleModalOpen(false)}
                onCreated={(nextVehicle, nextCustomer) => {
                    setQuickVehicleModalOpen(false);
                    applyVehicle(nextVehicle);
                    setCustomer(nextCustomer);
                }}
            />
            <FormDrawer
                open={editingDraftLine !== null}
                title="Edit line"
                onClose={() => !submitting && setEditingDraftLine(null)}
                closeDisabled={submitting}
            >
                {editingDraftLine && (
                    <VehicleServiceLineForm
                        key={editingDraftLine.key}
                        value={editingDraftLine.value}
                        error={null}
                        saving={submitting}
                        onCancel={() => setEditingDraftLine(null)}
                        onSave={(value) => {
                            formGuard.markDirty();
                            setDraftLines((current) => current.map((line) => line.key === editingDraftLine.key
                                ? { ...line, value }
                                : line));
                            setEditingDraftLine(null);
                        }}
                    />
                )}
            </FormDrawer>
            <ConfirmDialog
                open={removeDraftLineId !== null}
                title="Remove line"
                message="This service line will be removed from the draft."
                confirmLabel="Remove line"
                onCancel={() => setRemoveDraftLineId(null)}
                onConfirm={() => {
                    if (removeDraftLineId === null) return;
                    formGuard.markDirty();
                    setDraftLines((current) => current.filter((line) => line.key !== removeDraftLineId));
                    setRemoveDraftLineId(null);
                }}
            />
        </>
    );
}

function toDraftLinePreview(draft: DraftJobLine, index: number): VehicleServiceJobLine {
    const payload = lineFormToPayload(draft.value);
    const preview = calculateLinePreview(draft.value);

    return {
        id: draft.key,
        line_number: index + 1,
        line_source_type: payload.line_source_type,
        item_id: payload.item_id ?? null,
        item: draft.value.item ? {
            id: draft.value.item.item_id ?? draft.value.item.id,
            code: draft.value.item.code,
            name: draft.value.item.name,
        } : null,
        item_variant_id: payload.item_variant_id ?? null,
        batch_id: payload.batch_id ?? null,
        batch: draft.value.item?.batch ?? null,
        batch_price_revision_id: payload.batch_price_revision_id ?? null,
        uom_id: draft.value.uom?.id,
        uom: draft.value.uom,
        description: payload.description,
        quantity: draft.value.quantity,
        unit_cost: draft.value.unit_cost,
        unit_price: draft.value.unit_price,
        discount_calculation_type: draft.value.discount_type,
        discount_rate: draft.value.discount_type === 'percentage' ? draft.value.discount_value : ZERO_AMOUNT,
        discount_amount: preview.discount,
        tax_calculation_type: draft.value.tax_type,
        tax_rate: draft.value.tax_type === 'percentage' ? draft.value.tax_value : ZERO_AMOUNT,
        tax_amount: preview.tax,
        charge_calculation_type: draft.value.charge_type,
        charge_rate: draft.value.charge_type === 'percentage' ? draft.value.charge_value : ZERO_AMOUNT,
        charge_amount: preview.charge,
        line_total: preview.total,
        is_inventory_tracked: Boolean(draft.value.item?.is_stockable),
        is_customer_supplied: draft.value.customer_supplied,
        is_external: draft.value.source === 'external_item',
        is_billable: draft.value.billable,
        is_employee_assignable: draft.value.source === 'labour_item',
        available_stock_quantity: draft.value.item?.available_stock_quantity,
        reserved_stock_quantity: draft.value.item?.reserved_stock_quantity,
        reorder_level: draft.value.item?.reorder_level,
        status: 'pending',
        children: [],
    };
}

function VehicleContext({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</span><strong className="mt-1 block text-slate-900">{value}</strong></div>;
}
