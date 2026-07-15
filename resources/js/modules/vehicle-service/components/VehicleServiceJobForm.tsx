import { useCallback, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi, type VehicleLookupResource } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import type { VehicleServiceCommissionDefault } from '../commissionTypes';
import {
    createVehicleServiceJob,
    getVehicleServiceJobCreateDefaults,
    updateVehicleServiceJob,
} from '../vehicleServiceApi';
import type { CommissionType, VehicleServiceJob, VehicleServiceJobPayload } from '../vehicleServiceTypes';
import { VehicleServiceQuickVehicleModal } from './VehicleServiceQuickVehicleModal';

const ZERO_AMOUNT = '0.000000';
const ORGANIZATION_DEFAULT_COMMISSION = 'organization_default' as const;
const COMMISSION_TYPE = {
    NONE: 'none',
    FIXED: 'fixed',
    PERCENTAGE: 'percentage',
} as const satisfies Record<string, CommissionType>;
const COMMISSION_TYPE_LABELS = {
    [COMMISSION_TYPE.NONE]: 'No commission',
    [COMMISSION_TYPE.FIXED]: 'Fixed amount',
    [COMMISSION_TYPE.PERCENTAGE]: 'Percentage of whole job',
} as const satisfies Record<CommissionType, string>;
type SupervisorCommissionSelection = CommissionType | typeof ORGANIZATION_DEFAULT_COMMISSION;
const SUPERVISOR_COMMISSION_OPTIONS = [
    { value: COMMISSION_TYPE.NONE, label: 'None' },
    { value: COMMISSION_TYPE.FIXED, label: 'Fixed' },
    { value: COMMISSION_TYPE.PERCENTAGE, label: 'Percentage of whole job' },
];
const SUPERVISOR_COMMISSION_CREATE_OPTIONS = [
    { value: ORGANIZATION_DEFAULT_COMMISSION, label: 'Use organization default' },
    ...SUPERVISOR_COMMISSION_OPTIONS,
];
const today = businessDateInputValue;
const decimal = (value: string, fallback = ZERO_AMOUNT) => value.trim() || fallback;
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
const displayDecimal = (value: string): string => {
    const [whole, fraction = ''] = value.split('.');
    const trimmedFraction = fraction.replace(/0+$/, '');

    return trimmedFraction ? `${whole}.${trimmedFraction}` : whole;
};
const commissionDefaultSummary = (value: VehicleServiceCommissionDefault): string => {
    const label = COMMISSION_TYPE_LABELS[value.commission_type];

    if (value.commission_type === COMMISSION_TYPE.NONE) return label;

    const suffix = value.commission_type === COMMISSION_TYPE.PERCENTAGE ? '%' : '';
    return `${label} — ${displayDecimal(value.commission_value)}${suffix}`;
};

export function VehicleServiceJobForm({ job }: { job?: VehicleServiceJob }) {
    const navigate = useNavigate();
    const isCreating = job === undefined;
    const organizationDefault = useApi(
        (signal) => getVehicleServiceJobCreateDefaults(signal),
        [],
        isCreating,
    );
    const [customer, setCustomer] = useState<NamedResource | null>(vehicleCustomer(job?.vehicle ?? null, job?.customer ?? null));
    const [billToCustomer, setBillToCustomer] = useState<NamedResource | null>(job?.bill_to_customer ?? vehicleCustomer(job?.vehicle ?? null, job?.customer ?? null));
    const [vehicle, setVehicle] = useState<VehicleLookupResource | null>(job?.vehicle ?? null);
    const [supervisor, setSupervisor] = useState<NamedResource | null>(job?.supervisor ?? null);
    const [quickVehicleNumber, setQuickVehicleNumber] = useState('');
    const [quickVehicleModalOpen, setQuickVehicleModalOpen] = useState(false);
    const [form, setForm] = useState({
        job_date: job?.job_date ?? today(),
        expected_delivery_date: job?.expected_delivery_date ?? '',
        supervisor_commission_type: (job?.supervisor_commission_type ?? ORGANIZATION_DEFAULT_COMMISSION) as SupervisorCommissionSelection,
        supervisor_commission_value: job?.supervisor_commission_value ?? ZERO_AMOUNT,
        odometer_reading: job?.odometer_reading ?? '',
        fuel_level: job?.fuel_level ?? '',
        priority: job?.priority ?? 'normal',
        customer_complaint: job?.inspection?.customer_complaint ?? '',
        notes: job?.notes ?? '',
    });
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);
    const updateForm = useCallback((next: Parameters<typeof setForm>[0]) => {
        formGuard.markDirty();
        setForm(next);
    }, [formGuard]);
    const errorFor = (key: string) => fieldError(error, key);

    const searchCustomer = useCallback((params: LookupLoadParams) => lookupApi.customers(params), []);
    const searchVehicle = useCallback((params: LookupLoadParams) => lookupApi.serviceVehicles(params), []);
    const searchSupervisor = useCallback((params: LookupLoadParams) => lookupApi.availableEmployees(params), []);

    const usesOrganizationDefault = isCreating
        && form.supervisor_commission_type === ORGANIZATION_DEFAULT_COMMISSION;
    const organizationDefaultUnavailable = usesOrganizationDefault
        && supervisor !== null
        && (organizationDefault.loading || organizationDefault.error !== null || organizationDefault.data === null);
    const displayedCommissionValue = usesOrganizationDefault
        ? organizationDefault.data?.commission_value ?? ''
        : form.supervisor_commission_value;
    const supervisorCommissionHint = job
        ? 'This stored whole-job commission snapshot can be explicitly updated.'
        : usesOrganizationDefault
            ? 'The resolved organization value is shown below.'
            : 'This explicit whole-job override will be stored on the job.';

    const supervisorCommissionPayload = (): Partial<Pick<
        VehicleServiceJobPayload,
        'supervisor_commission_type' | 'supervisor_commission_value'
    >> => {
        if (form.supervisor_commission_type === ORGANIZATION_DEFAULT_COMMISSION) {
            return {};
        }

        return {
            supervisor_commission_type: form.supervisor_commission_type,
            supervisor_commission_value: form.supervisor_commission_type === COMMISSION_TYPE.NONE
                ? ZERO_AMOUNT
                : decimal(form.supervisor_commission_value),
        };
    };

    const payload = (): VehicleServiceJobPayload => ({
        expected_version: job?.row_version,
        job_date: form.job_date,
        expected_delivery_date: form.expected_delivery_date || undefined,
        customer_id: customer?.id ?? 0,
        bill_to_customer_id: billToCustomer?.id ?? customer?.id ?? 0,
        vehicle_id: vehicle?.id ?? 0,
        supervisor_employee_id: supervisor?.id,
        ...supervisorCommissionPayload(),
        odometer_reading: form.odometer_reading || undefined,
        fuel_level: form.fuel_level || undefined,
        priority: form.priority || undefined,
        customer_complaint: form.customer_complaint,
        notes: form.notes || undefined,
    });

    const applyVehicle = useCallback((value: VehicleLookupResource | null) => {
        formGuard.markDirty();
        const nextCustomer = vehicleCustomer(value, null);
        setVehicle(value);
        setCustomer(nextCustomer);
        setBillToCustomer(nextCustomer);
        if (value?.odometer_reading && !form.odometer_reading) {
            updateForm((current) => ({ ...current, odometer_reading: value.odometer_reading ?? '' }));
        }
    }, [form.odometer_reading, formGuard, updateForm]);

    const applySupervisorCommissionType = useCallback((next: SupervisorCommissionSelection) => {
        updateForm((current) => ({
            ...current,
            supervisor_commission_type: next,
            supervisor_commission_value: next === ORGANIZATION_DEFAULT_COMMISSION || next === COMMISSION_TYPE.NONE
                ? ZERO_AMOUNT
                : current.supervisor_commission_value,
        }));
    }, [updateForm]);

    return (
        <>
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                if (organizationDefaultUnavailable) return;

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
                            formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()}
                            error={errorFor('vehicle_id')}
                            placeholder="Search by vehicle number or registration"
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
                        <GenericLookupSelect label="Bill-to customer" value={billToCustomer} onChange={(value) => { formGuard.markDirty(); setBillToCustomer(value); }} search={searchCustomer} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} error={errorFor('bill_to_customer_id')} placeholder="Defaults to vehicle owner" loadOnOpen minSearchLength={0} />
                        <GenericLookupSelect label="Supervisor" value={supervisor} onChange={(value) => { formGuard.markDirty(); setSupervisor(value); }} search={searchSupervisor} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} error={errorFor('supervisor_employee_id')} />
                        <Input label="Job date" type="date" value={form.job_date} error={errorFor('job_date')} onChange={(event) => updateForm({ ...form, job_date: event.target.value })} />
                        <Input label="Expected delivery" type="date" value={form.expected_delivery_date} error={errorFor('expected_delivery_date')} onChange={(event) => updateForm({ ...form, expected_delivery_date: event.target.value })} />
                        <DecimalInput label="Odometer" value={form.odometer_reading} error={errorFor('odometer_reading')} onChange={(event) => updateForm({ ...form, odometer_reading: event.target.value })} />
                        <Input label="Fuel level" value={form.fuel_level} error={errorFor('fuel_level')} onChange={(event) => updateForm({ ...form, fuel_level: event.target.value })} />
                        <Select label="Priority" value={form.priority} options={[
                            { value: 'low', label: 'Low' },
                            { value: 'normal', label: 'Normal' },
                            { value: 'high', label: 'High' },
                            { value: 'urgent', label: 'Urgent' },
                        ]} onChange={(event) => updateForm({ ...form, priority: event.target.value })} />
                        <Select
                            label="Supervisor commission"
                            value={form.supervisor_commission_type}
                            options={job ? SUPERVISOR_COMMISSION_OPTIONS : SUPERVISOR_COMMISSION_CREATE_OPTIONS}
                            hint={supervisorCommissionHint}
                            onChange={(event) => applySupervisorCommissionType(event.target.value as SupervisorCommissionSelection)}
                        />
                        <DecimalInput
                            label="Commission value"
                            value={displayedCommissionValue}
                            error={errorFor('supervisor_commission_value')}
                            disabled={form.supervisor_commission_type === ORGANIZATION_DEFAULT_COMMISSION
                                || form.supervisor_commission_type === COMMISSION_TYPE.NONE}
                            onChange={(event) => updateForm({ ...form, supervisor_commission_value: event.target.value })}
                        />
                        {isCreating && <div className="rounded-lg border border-sky-100 bg-sky-50 px-4 py-3 text-sm md:col-span-2 xl:col-span-4" aria-live="polite">
                            <p className="font-semibold text-sky-950">Organization commission default</p>
                            {organizationDefault.loading && <p className="mt-1 text-sky-800">Loading the active organization default...</p>}
                            {organizationDefault.error && <div className="mt-2 flex flex-wrap items-center justify-between gap-3">
                                <p className="text-rose-700">Unable to load the organization commission default. Retry, or choose an explicit commission before saving a job with a supervisor.</p>
                                <Button type="button" variant="secondary" className="min-h-8 px-3 py-1" onClick={organizationDefault.reload}>Retry</Button>
                            </div>}
                            {organizationDefault.data && <>
                                <strong className="mt-1 block text-sky-950">{commissionDefaultSummary(organizationDefault.data)}</strong>
                                <p className="mt-1 text-sky-800">This applies only when a supervisor is selected. The server confirms the active default again when the draft is saved.</p>
                            </>}
                        </div>}
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
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button type="submit" loading={submitting} disabled={organizationDefaultUnavailable}>{job ? 'Save job' : 'Save draft'}</Button>
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
        </>
    );
}

function VehicleContext({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</span><strong className="mt-1 block text-slate-900">{value}</strong></div>;
}
