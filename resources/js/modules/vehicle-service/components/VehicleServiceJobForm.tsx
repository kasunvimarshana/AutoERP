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
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { addDecimal, isDecimalString } from '@/shared/utils/decimal';
import {
    createVehicleServiceJob,
    updateVehicleServiceJob,
} from '../vehicleServiceApi';
import type { CommissionType, VehicleServiceJob, VehicleServiceJobPayload, VehicleServiceJobType } from '../vehicleServiceTypes';
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
const vehicleLookupLabel = (vehicle: VehicleLookupResource): string => {
    const modelName = vehicle.model?.name?.trim();
    return [vehicle.code, modelName]
        .filter((value): value is string => typeof value === 'string' && value.trim() !== '')
        .join(' ');
};

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
                            placeholder="Search by vehicle number or model"
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
        </>
    );
}

function VehicleContext({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</span><strong className="mt-1 block text-slate-900">{value}</strong></div>;
}
