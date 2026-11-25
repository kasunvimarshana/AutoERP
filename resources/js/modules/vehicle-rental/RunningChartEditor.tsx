/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useState, useEffect, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Textarea } from '@/shared/components/Textarea';
import { requestLookup } from '@/shared/api/lookupRequest';
import { toApiError, ApiError } from '@/shared/api/apiError';
import { formatBusinessDateTimeWithSeconds, businessDateTimeInputValueWithSeconds } from '@/shared/utils/businessDate';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { createChart, updateChart } from './runningChartApi';
import {
    AirConditioningMode,
    calculateOtMinutes,
    COUNT_LABELS,
    DISTANCE_LABELS,
    DRIVER_EMPLOYEE_API,
    DriverIdentitySource,
    formatMinutes,
    type ChartFacts,
    type RunningChart,
} from './runningCharts';
import { localTimestampValue, operationalTimeZoneLabel, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset, type VehicleUse } from './vehicleUse';

const employees = (params: LookupLoadParams) => requestLookup<Record<string, unknown>>(DRIVER_EMPLOYEE_API, params)
    .then(result => ({ ...result, data: result.data.map(row => ({ id: Number(row.id), name: String(row.display_name ?? row.name ?? row.employee_number ?? ''), code: String(row.employee_number ?? row.code ?? '') })) }));
const driverSourceOptions = [
    { value: DriverIdentitySource.Employee, label: 'Employee driver' },
    { value: DriverIdentitySource.External, label: 'External driver' },
];
const acOptions = [
    { value: AirConditioningMode.NonAc, label: 'Non-AC' },
    { value: AirConditioningMode.Front, label: 'Front AC' },
    { value: AirConditioningMode.Dual, label: 'Dual AC' },
];

export interface InitialDriverState {
    source?: DriverIdentitySource;
    employee?: NamedResource | null;
    externalName?: string;
    externalReference?: string;
}

function freshLocalNow(): string {
    return businessDateTimeInputValueWithSeconds();
}

export function RunningChartEditor({
    use,
    chart,
    correction,
    initialDriver,
    initialOdometer,
    initialStartTime,
    onSaved,
    onCancel,
}: {
    use: VehicleUse;
    chart?: RunningChart;
    correction?: boolean;
    initialDriver?: InitialDriverState;
    initialOdometer?: string;
    initialStartTime?: string;
    onSaved: () => void;
    onCancel: () => void;
}) {
    const custodyStart = use.handed_over_at ? localTimestampValue(use.handed_over_at) : undefined;
    const custodyEnd = use.returned_at ? localTimestampValue(use.returned_at) : undefined;

    const [reference, setReference] = useState(correction ? '' : chart?.reference ?? '');
    const [start, setStart] = useState(() => {
        if (chart) return localTimestampValue(chart.starts_at);
        if (initialStartTime) return initialStartTime;
        if (custodyStart) return custodyStart;
        return '';
    });
    const [end, setEnd] = useState(chart ? localTimestampValue(chart.ends_at) : '');
    const [values, setValues] = useState<Record<string, string>>(() => {
        const initial = Object.fromEntries(
            [...Object.keys(DISTANCE_LABELS), ...Object.keys(COUNT_LABELS)].map(key => [
                key,
                String(chart?.[key as keyof ChartFacts] ?? ''),
            ])
        );
        if (!chart && initialOdometer && !initial.start_odometer) {
            initial.start_odometer = initialOdometer;
        }
        return initial;
    });
    const [ac, setAc] = useState<AirConditioningMode | ''>(chart?.ac_mode ?? '');
    const [driverSource, setDriverSource] = useState<DriverIdentitySource | ''>(chart?.driver_identity_source ?? (!chart && initialDriver?.source ? initialDriver.source : ''));
    const [driverEmployee, setDriverEmployee] = useState<NamedResource | null>(() => {
        if (chart?.driver?.source === DriverIdentitySource.Employee && chart.driver.employee_id) {
            return { id: chart.driver.employee_id, name: chart.driver.name, code: chart.driver.reference };
        }
        if (!chart && initialDriver?.source === DriverIdentitySource.Employee && initialDriver.employee) {
            return initialDriver.employee;
        }
        return null;
    });
    const [externalDriverName, setExternalDriverName] = useState(
        chart?.driver?.source === DriverIdentitySource.External
            ? chart.driver.name
            : (!chart && initialDriver?.source === DriverIdentitySource.External ? initialDriver.externalName ?? '' : '')
    );
    const [externalDriverReference, setExternalDriverReference] = useState(
        chart?.driver?.source === DriverIdentitySource.External
            ? chart.driver.reference
            : (!chart && initialDriver?.source === DriverIdentitySource.External ? initialDriver.externalReference ?? '' : '')
    );
    const [driverObservation, setDriverObservation] = useState(chart?.driver_observation ?? '');
    const [notes, setNotes] = useState(chart?.notes ?? '');
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);

    const isWithDriver = use.customer_agreement?.driver_mode === 'with_driver'
        || use.owner_agreement?.driver_mode === 'with_driver';

    const agreementWorkingHours = (use.customer_agreement?.driver_mode === 'with_driver' ? use.customer_agreement.working_hours : null)
        ?? (use.owner_agreement?.driver_mode === 'with_driver' ? use.owner_agreement.working_hours : null)
        ?? use.customer_agreement?.working_hours
        ?? use.customer_agreement?.terms?.working_hours
        ?? use.owner_agreement?.working_hours
        ?? use.owner_agreement?.terms?.working_hours
        ?? null;

    const initialBaseline = agreementWorkingHours
        ? String(parseFloat(agreementWorkingHours))
        : '8.0';

    const [baselineHours, setBaselineHours] = useState<string>(initialBaseline);

    const tripDurationMinutes = (() => {
        if (!start || !end) return 0;
        try {
            const s = new Date(start).getTime();
            const e = new Date(end).getTime();
            if (isNaN(s) || isNaN(e) || e <= s) return 0;
            return Math.floor((e - s) / 60000);
        } catch {
            return 0;
        }
    })();

    const parsedBaseline = parseFloat(baselineHours);
    const calculatedOtMinutes = (!isNaN(parsedBaseline) && parsedBaseline > 0 && tripDurationMinutes > 0)
        ? Math.max(0, tripDurationMinutes - Math.round(parsedBaseline * 60))
        : 0;

    const initialCalculatedOt = calculateOtMinutes(
        chart?.starts_at_input ?? chart?.starts_at ?? '',
        chart?.ends_at_input ?? chart?.ends_at ?? '',
        initialBaseline
    );

    const [isOtManuallyAdjusted, setIsOtManuallyAdjusted] = useState<boolean>(() => {
        if (!chart || chart.normal_ot_minutes === null || chart.normal_ot_minutes === undefined) {
            return false;
        }
        return chart.normal_ot_minutes !== initialCalculatedOt;
    });

    useEffect(() => {
        if (!isOtManuallyAdjusted && start && end) {
            queueMicrotask(() => {
                if (calculatedOtMinutes > 0) {
                    setValues(v => ({ ...v, normal_ot_minutes: String(calculatedOtMinutes) }));
                } else {
                    setValues(v => (v.normal_ot_minutes && v.normal_ot_minutes !== '' ? { ...v, normal_ot_minutes: '' } : v));
                }
            });
        }
    }, [calculatedOtMinutes, isOtManuallyAdjusted, start, end]);

    function applyCalculatedOt() {
        setValues(v => ({ ...v, normal_ot_minutes: calculatedOtMinutes > 0 ? String(calculatedOtMinutes) : '' }));
        setIsOtManuallyAdjusted(false);
    }

    function applyToDoubleOt() {
        setValues(v => ({
            ...v,
            normal_ot_minutes: '',
            double_ot_minutes: calculatedOtMinutes > 0 ? String(calculatedOtMinutes) : '',
        }));
        setIsOtManuallyAdjusted(true);
        setUserToggledOpen(true);
    }

    const isOtOverridden = isOtManuallyAdjusted && (
        (values.normal_ot_minutes || '') !== (calculatedOtMinutes > 0 ? String(calculatedOtMinutes) : '')
    );

    const hasErrorInDetails = Boolean(error && Object.keys(error.fields).some(k => [
        'garage_km', 'commercial_km', 'double_ot_minutes',
        'triple_ot_minutes', 'night_outs', 'ac_mode', 'driver_identity_source',
        'driver_employee_id', 'driver_name_snapshot', 'driver_reference_snapshot',
        'driver_observation', 'notes',
    ].includes(k)));

    const hasDetailsContent = Boolean(
        isWithDriver ||
        driverSource ||
        ac ||
        initialDriver?.source ||
        values.garage_km ||
        values.commercial_km ||
        values.double_ot_minutes ||
        values.triple_ot_minutes ||
        values.night_outs ||
        driverObservation ||
        notes
    );
    const [userToggledOpen, setUserToggledOpen] = useState<boolean | null>(null);
    const detailsOpen = userToggledOpen !== null ? userToggledOpen : (hasDetailsContent || hasErrorInDetails);

    const isReferenceRequired = Boolean(chart && !correction);
    const isReferenceValid = isReferenceRequired ? Boolean(reference.trim()) : true;
    const [mountTime] = useState(() => freshLocalNow());
    const isFutureTrip = Boolean(end && end > mountTime);

    async function submit(event: FormEvent) {
        event.preventDefault(); setSaving(true); setError(null);
        try {
            if (start && end && end <= start) {
                setError(new ApiError(
                    'Usage end must follow usage start.',
                    422,
                    'VALIDATION_FAILED',
                    'validation_error',
                    { ends_at: ['Usage end must follow usage start.'] },
                ));
                setSaving(false);
                return;
            }

            if (values.start_odometer && values.end_odometer) {
                const startOdo = parseFloat(values.start_odometer);
                const endOdo = parseFloat(values.end_odometer);
                if (!isNaN(startOdo) && !isNaN(endOdo) && endOdo < startOdo) {
                    setError(new ApiError(
                        'End odometer cannot be lower than start odometer.',
                        422,
                        'VALIDATION_FAILED',
                        'validation_error',
                        { end_odometer: ['End odometer cannot be lower than start odometer.'] },
                    ));
                    setSaving(false);
                    return;
                }
            }

            let startIso = '';
            let endIso = '';
            try {
                if (start) startIso = timestampWithOffset(start);
                if (end) endIso = timestampWithOffset(end);
            } catch {
                // fall through to backend
            }

            if (custodyStart && startIso) {
                let custodyStartIso = '';
                try {
                    custodyStartIso = timestampWithOffset(custodyStart);
                } catch {
                    // fall through
                }
                if (custodyStartIso && new Date(startIso).getTime() < new Date(custodyStartIso).getTime() - 60_000) {
                    setError(new ApiError(
                        'Usage must fall within actual customer custody.',
                        422,
                        'VALIDATION_FAILED',
                        'validation_error',
                        {
                            period: ['Usage must fall within actual customer custody.'],
                            starts_at: [use.handed_over_at ? `Usage cannot start before vehicle handover (${formatBusinessDateTimeWithSeconds(use.handed_over_at)}).` : 'Usage must fall within actual customer custody.'],
                        },
                    ));
                    setSaving(false);
                    return;
                }
            }

            if (custodyEnd && endIso) {
                let custodyEndIso = '';
                try {
                    custodyEndIso = timestampWithOffset(custodyEnd);
                } catch {
                    // fall through
                }
                if (custodyEndIso && new Date(startIso).getTime() >= new Date(custodyEndIso).getTime()) {
                    setError(new ApiError(
                        'Usage must fall within actual customer custody.',
                        422,
                        'VALIDATION_FAILED',
                        'validation_error',
                        {
                            period: ['Usage must fall within actual customer custody.'],
                            starts_at: [use.returned_at ? `Usage cannot start after vehicle return (${formatBusinessDateTimeWithSeconds(use.returned_at)}).` : 'Usage must fall within actual customer custody.'],
                        },
                    ));
                    setSaving(false);
                    return;
                }
                if (custodyEndIso && new Date(endIso).getTime() > new Date(custodyEndIso).getTime() + 60_000) {
                    setError(new ApiError(
                        'Usage must fall within actual customer custody.',
                        422,
                        'VALIDATION_FAILED',
                        'validation_error',
                        {
                            period: ['Usage must fall within actual customer custody.'],
                            ends_at: [use.returned_at ? `Usage cannot end after vehicle return (${formatBusinessDateTimeWithSeconds(use.returned_at)}).` : 'Usage must fall within actual customer custody.'],
                        },
                    ));
                    setSaving(false);
                    return;
                }
            }

            const trimmedReference = reference.trim();
            const parseDistance = (val?: string) => {
                const t = val?.trim();
                return t ? t : null;
            };
            const parseCount = (val?: string) => {
                const t = val?.trim();
                return t === undefined || t === '' ? null : Number(t);
            };
            const facts: ChartFacts = {
                ...(trimmedReference ? { reference: trimmedReference } : (isReferenceRequired ? { reference: '' } : {})),
                starts_at: timestampWithOffset(start),
                ends_at: timestampWithOffset(end),
                start_odometer: parseDistance(values.start_odometer),
                end_odometer: parseDistance(values.end_odometer),
                garage_km: parseDistance(values.garage_km),
                commercial_km: parseDistance(values.commercial_km),
                normal_ot_minutes: parseCount(values.normal_ot_minutes),
                double_ot_minutes: parseCount(values.double_ot_minutes),
                triple_ot_minutes: parseCount(values.triple_ot_minutes),
                night_outs: parseCount(values.night_outs),
                ac_mode: ac || null,
                driver_identity_source: driverSource || null,
                driver_employee_id: driverSource === DriverIdentitySource.Employee ? driverEmployee?.id ?? null : null,
                driver_name_snapshot: driverSource === DriverIdentitySource.External ? externalDriverName.trim() || null : null,
                driver_reference_snapshot: driverSource === DriverIdentitySource.External ? externalDriverReference.trim() || null : null,
                driver_observation: driverObservation.trim() || null,
                notes: notes.trim() || null,
            };
            if (chart && !correction) await updateChart(chart, facts); else await createChart(use, facts, correction ? chart?.id : undefined);
            onSaved();
        } catch (failure) { setError(toApiError(failure)); } finally { setSaving(false); }
    }

    const startOdo = parseFloat(values.start_odometer);
    const endOdo = parseFloat(values.end_odometer);
    const hasValidOdo = !isNaN(startOdo) && !isNaN(endOdo);
    const calculatedTripDistance = hasValidOdo && endOdo >= startOdo ? (endOdo - startOdo).toFixed(2) : null;

    const decimal = (key: string, label: string, hint?: string) => (
        <Input
            key={key}
            label={label}
            value={values[key]}
            inputMode="decimal"
            onChange={e => setValues(v => ({ ...v, [key]: e.target.value }))}
            error={error?.fields[key]?.[0]}
            hint={hint}
        />
    );
    const count = (key: string, label: string) => {
        const val = values[key];
        const num = val ? parseInt(val, 10) : 0;
        const isOtField = key.includes('ot_minutes');

        return (
            <div key={key} className="space-y-1">
                <Input
                    label={label}
                    type="number"
                    min={0}
                    step={1}
                    inputMode="numeric"
                    value={val}
                    onChange={e => {
                        const nextVal = e.target.value;
                        setValues(v => ({ ...v, [key]: nextVal }));
                        if (key === 'normal_ot_minutes') {
                            setIsOtManuallyAdjusted(true);
                        }
                    }}
                    error={error?.fields[key]?.[0]}
                />
                {isOtField && num > 0 && (
                    <p className="text-[11px] text-slate-500 font-medium px-1">
                        Equivalent: {formatMinutes(num)} ({(num / 60).toFixed(2)} hrs)
                    </p>
                )}
            </div>
        );
    };

    return <form aria-label="Running Chart" onSubmit={submit} className="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h4 className="font-semibold">{correction ? `Correct ${chart?.reference}` : chart ? 'Edit draft Running Chart' : 'Record Running Chart'}</h4>
        <p>Record actual usage in {operationalTimeZoneLabel()}. Leave unknown observations blank; enter zero only when verified.</p><ErrorAlert error={error} inline />
        <fieldset disabled={saving} className="space-y-4">
            <Input label="Chart reference" required={isReferenceRequired} value={reference} onChange={e => setReference(e.target.value)} error={error?.fields.reference?.[0]} hint={!isReferenceRequired ? 'Leave blank to automatically generate a unique chart reference' : undefined} />
            <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1">
                    <Input
                        label="Usage start"
                        type="datetime-local"
                        step={OPERATIONAL_TIME_STEP_SECONDS}
                        required
                        value={start}
                        onChange={e => setStart(e.target.value)}
                        error={error?.fields.starts_at?.[0]}
                        hint={use.handed_over_at ? `Vehicle in custody since: ${formatBusinessDateTimeWithSeconds(use.handed_over_at)}` : undefined}
                    />
                    <div className="flex flex-wrap gap-2 text-xs">
                        {custodyStart && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => setStart(custodyStart)}
                            >
                                Use custody start time
                            </Button>
                        )}
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                const now = freshLocalNow();
                                const target = end && end < now ? end : now;
                                setStart(target);
                            }}
                        >
                            Use current time
                        </Button>
                    </div>
                </div>
                <div className="space-y-1">
                    <Input
                        label="Usage end"
                        type="datetime-local"
                        step={OPERATIONAL_TIME_STEP_SECONDS}
                        required
                        value={end}
                        onChange={e => setEnd(e.target.value)}
                        error={error?.fields.ends_at?.[0]}
                        hint={use.returned_at ? `Vehicle returned: ${formatBusinessDateTimeWithSeconds(use.returned_at)}` : 'Usage end must follow usage start.'}
                    />
                    <div className="flex flex-wrap gap-2 text-xs">
                        {custodyEnd && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => setEnd(custodyEnd)}
                            >
                                Use custody return time
                            </Button>
                        )}
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                const now = freshLocalNow();
                                const target = custodyEnd && custodyEnd < now ? custodyEnd : now;
                                setEnd(target);
                            }}
                        >
                            Use current time
                        </Button>
                    </div>
                </div>
            </div>
            {isFutureTrip && (
                <div className="rounded-lg border border-sky-200 bg-sky-50 p-2.5 text-xs text-sky-800">
                    This trip extends into the future. It will be saved as a draft and can be finalized as physical evidence after the trip concludes.
                </div>
            )}

            {/* Working Hours & Overtime Baseline Calculation Card */}
            <div className="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 space-y-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h5 className="text-sm font-semibold text-indigo-950 flex items-center gap-1.5">
                            <span>Working Hours & Overtime Baseline</span>
                            {isWithDriver && (
                                <span className="rounded bg-indigo-100 px-1.5 py-0.5 text-[11px] font-medium text-indigo-800">
                                    With driver
                                </span>
                            )}
                        </h5>
                        <p className="text-xs text-indigo-700">
                            Overtime is calculated automatically from actual trip duration against agreed baseline hours.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <label htmlFor="rc-baseline-hours" className="text-xs font-medium text-indigo-900 whitespace-nowrap">
                            Agreed baseline:
                        </label>
                        <div className="flex items-center gap-1">
                            <input
                                id="rc-baseline-hours"
                                type="number"
                                step="0.5"
                                min="0"
                                max="24"
                                value={baselineHours}
                                onChange={e => setBaselineHours(e.target.value)}
                                className="w-16 rounded border border-indigo-300 bg-white px-2 py-1 text-xs font-semibold text-indigo-950 shadow-sm focus:border-indigo-500 focus:outline-none"
                                title="Agreed standard daily working hours baseline"
                            />
                            <span className="text-xs text-indigo-800 font-medium">hrs/day</span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 text-xs">
                    <div className="rounded-lg bg-white p-2.5 shadow-sm border border-indigo-100">
                        <span className="text-slate-500 block">Trip duration</span>
                        <span className="font-semibold text-slate-800 text-sm">
                            {tripDurationMinutes > 0 ? formatMinutes(tripDurationMinutes) : '—'}
                        </span>
                    </div>
                    <div className="rounded-lg bg-white p-2.5 shadow-sm border border-indigo-100">
                        <span className="text-slate-500 block">Agreed baseline</span>
                        <span className="font-semibold text-slate-800 text-sm">
                            {!isNaN(parsedBaseline) && parsedBaseline > 0 ? `${parsedBaseline} hrs (${Math.round(parsedBaseline * 60)}m)` : 'Not set'}
                        </span>
                    </div>
                    <div className="rounded-lg bg-white p-2.5 shadow-sm border border-indigo-100">
                        <span className="text-slate-500 block">Calculated OT</span>
                        <span className={`font-semibold text-sm ${calculatedOtMinutes > 0 ? 'text-indigo-700' : 'text-slate-600'}`}>
                            {calculatedOtMinutes > 0 ? `${calculatedOtMinutes} mins (${formatMinutes(calculatedOtMinutes)})` : '0 mins'}
                        </span>
                    </div>
                    <div className="rounded-lg bg-white p-2.5 shadow-sm border border-indigo-100">
                        <label htmlFor="rc-normal-ot-minutes" className="text-slate-500 block font-medium">
                            Normal OT (minutes)
                        </label>
                        <div className="mt-1 flex items-center gap-1.5">
                            <input
                                id="rc-normal-ot-minutes"
                                type="number"
                                min={0}
                                step={1}
                                inputMode="numeric"
                                value={values.normal_ot_minutes}
                                onChange={e => {
                                    setValues(v => ({ ...v, normal_ot_minutes: e.target.value }));
                                    setIsOtManuallyAdjusted(true);
                                }}
                                placeholder="0"
                                className="w-20 rounded border border-indigo-300 bg-white px-2 py-0.5 text-sm font-semibold text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none"
                            />
                        </div>
                        {values.normal_ot_minutes && parseInt(values.normal_ot_minutes, 10) > 0 ? (
                            <p className="mt-1 text-[11px] text-slate-500 font-medium px-1">
                                Equivalent: {formatMinutes(parseInt(values.normal_ot_minutes, 10))} ({(parseInt(values.normal_ot_minutes, 10) / 60).toFixed(2)} hrs)
                            </p>
                        ) : null}
                        {error?.fields.normal_ot_minutes?.[0] && (
                            <p className="mt-1 text-xs text-rose-600">{error.fields.normal_ot_minutes[0]}</p>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-indigo-100 text-xs">
                    <div className="flex items-center gap-1.5 text-indigo-800">
                        {values.double_ot_minutes && !values.normal_ot_minutes ? (
                            <span className="inline-flex items-center gap-1 text-purple-700 font-medium">
                                <span>↳</span>
                                <span>Applied {values.double_ot_minutes} mins to Double OT (calculated OT was {calculatedOtMinutes} mins)</span>
                            </span>
                        ) : isOtOverridden ? (
                            <span className="inline-flex items-center gap-1 text-amber-700 font-medium">
                                <span>✎</span>
                                <span>Manually adjusted ({values.normal_ot_minutes || '0'} mins vs calculated {calculatedOtMinutes} mins)</span>
                            </span>
                        ) : calculatedOtMinutes > 0 ? (
                            <span className="inline-flex items-center gap-1 text-emerald-700 font-medium">
                                <span>✓</span>
                                <span>Applied {calculatedOtMinutes} mins OT automatically based on {parsedBaseline} hrs baseline</span>
                            </span>
                        ) : tripDurationMinutes > 0 ? (
                            <span className="text-slate-600">
                                Trip duration is within agreed baseline hours ({parsedBaseline} hrs). No overtime calculated.
                            </span>
                        ) : (
                            <span className="text-slate-500">
                                Enter usage start and end times to calculate trip duration and overtime.
                            </span>
                        )}
                    </div>
                    <div className="flex flex-wrap gap-1.5">
                        {isOtOverridden && (
                            <Button
                                type="button"
                                variant="secondary"
                                className="!py-0.5 !px-2 !text-xs"
                                onClick={applyCalculatedOt}
                            >
                                Reset to calculated ({calculatedOtMinutes}m)
                            </Button>
                        )}
                        {calculatedOtMinutes > 0 && (
                            <Button
                                type="button"
                                variant="secondary"
                                className="!py-0.5 !px-2 !text-xs"
                                onClick={applyToDoubleOt}
                            >
                                Apply to Double OT
                            </Button>
                        )}
                    </div>
                </div>
            </div>

            <div className="space-y-1">
                <div className="grid gap-3 sm:grid-cols-2">
                    {decimal('start_odometer', DISTANCE_LABELS.start_odometer, initialOdometer ? 'Prefilled from previous odometer reading (verify physical meter)' : 'Physical vehicle meter at departure')}
                    {decimal('end_odometer', DISTANCE_LABELS.end_odometer, 'Physical vehicle meter at return')}
                </div>
                {calculatedTripDistance !== null && (
                    <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600 px-1">
                        <span>
                            Total physical distance: <strong className="text-slate-800">{calculatedTripDistance} km</strong> <span className="text-slate-500">(Formula: End Odometer − Start Odometer)</span>
                        </span>
                        {!values.commercial_km && (
                            <Button
                                type="button"
                                variant="secondary"
                                className="!py-0.5 !px-2 !text-xs"
                                onClick={() => setValues(v => ({ ...v, commercial_km: calculatedTripDistance }))}
                            >
                                Set commercial distance to {calculatedTripDistance} km
                            </Button>
                        )}
                    </div>
                )}
            </div>

            <details
                open={detailsOpen}
                onToggle={e => setUserToggledOpen(e.currentTarget.open)}
            >
                <summary className="cursor-pointer font-medium flex items-center justify-between">
                    <span>Additional usage observations</span>
                    {isWithDriver && (
                        <span className="text-xs font-normal text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                            Agreement: With driver
                        </span>
                    )}
                </summary>
                <div className="space-y-3 pt-3">
                    {isWithDriver && !driverSource && (
                        <p className="rounded-md border border-sky-200 bg-sky-50 p-2 text-xs text-sky-800">
                            This agreement includes driver services. Select Employee driver or External driver below.
                        </p>
                    )}
                    <div className="grid gap-3 sm:grid-cols-2">
                        {decimal('garage_km', DISTANCE_LABELS.garage_km)}
                        {decimal('commercial_km', DISTANCE_LABELS.commercial_km)}
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {Object.entries(COUNT_LABELS).filter(([key]) => key !== 'normal_ot_minutes').map(([key, label]) => count(key, label))}
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <Select label="Air conditioning" placeholder="Not recorded" options={acOptions} value={ac} onChange={e => setAc(e.target.value as AirConditioningMode | '')} error={error?.fields.ac_mode?.[0]} />
                        <Select
                            label="Driver identity"
                            placeholder="Not recorded"
                            options={driverSourceOptions}
                            value={driverSource}
                            onChange={e => {
                                const source = e.target.value as DriverIdentitySource | '';
                                setDriverSource(source);
                                if (source !== DriverIdentitySource.Employee) setDriverEmployee(null);
                                setUserToggledOpen(true);
                            }}
                            error={error?.fields.driver_identity_source?.[0]}
                        />
                    </div>
                    {driverSource === DriverIdentitySource.Employee && (
                        <LookupSelect
                            label="Employee driver"
                            value={driverEmployee}
                            onChange={setDriverEmployee}
                            search={employees}
                            required
                            loadOnOpen
                            minSearchLength={0}
                            error={error?.fields.driver_employee_id?.[0]}
                        />
                    )}
                    {driverSource === DriverIdentitySource.External && (
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Input label="External driver name" value={externalDriverName} onChange={e => setExternalDriverName(e.target.value)} required error={error?.fields.driver_name_snapshot?.[0]} />
                            <Input label="External driver reference" value={externalDriverReference} onChange={e => setExternalDriverReference(e.target.value)} required error={error?.fields.driver_reference_snapshot?.[0]} hint="Use a stable business reference, not a temporary note." />
                        </div>
                    )}
                    <Textarea label="Driver observation (optional)" value={driverObservation} onChange={e => setDriverObservation(e.target.value)} error={error?.fields.driver_observation?.[0]} />
                    <p className="text-xs text-slate-500">This is operational evidence only; employee pay remains owned by HR.</p>
                    <Textarea label="Notes" value={notes} onChange={e => setNotes(e.target.value)} error={error?.fields.notes?.[0]} />
                </div>
            </details>
        </fieldset>
        <div className="flex flex-wrap gap-2">
            <Button
                type="submit"
                loading={saving}
                disabled={!isReferenceValid || !start || !end || (driverSource === DriverIdentitySource.Employee && !driverEmployee) || (driverSource === DriverIdentitySource.External && (!externalDriverName.trim() || !externalDriverReference.trim()))}
            >
                Save draft
            </Button>
            <Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>
                Cancel
            </Button>
        </div>
    </form>;
}
