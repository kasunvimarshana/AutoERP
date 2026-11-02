import { useEffect, useMemo, useState, type FormEvent } from "react";
import { useSearchParams } from "react-router-dom";
import { useAuth } from "@/modules/auth/AuthProvider";
import { hasPermission } from "@/modules/auth/accessControl";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button, LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DataTable, type DataColumn } from "@/shared/components/DataTable";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { Textarea } from "@/shared/components/Textarea";
import { useApi } from "@/shared/hooks/useApi";
import type { NamedResource } from "@/shared/types/common";
import { businessDateInputValue } from "@/shared/utils/businessDate";
import { formatDate } from "@/shared/utils/formatDate";
import { readableRelation } from "@/shared/utils/object";
import { parsePositiveInteger } from "@/shared/utils/routeParams";
import { RentalAllocationLookupSelect } from "../components/RentalLookups";
import { RentalPage } from "../components/RentalPage";
import { RentalUsageFactEditor } from "../components/RentalUsageFactEditor";
import {
    createRentalUsageLog,
    getRentalAllocation,
    getRentalMetadata,
    listRentalUsageLogs,
    transitionRentalUsageLog,
} from "../vehicleRentalApi";
import { vehicleRentalPermissions } from "../vehicleRentalPermissions";
import type {
    RentalAllocation,
    RentalUsageEventApplicability,
    RentalUsageLog,
} from "../vehicleRentalTypes";

interface UsageForm {
    usage_date: string;
    started_at: string;
    ended_at: string;
    driver_assignment_id: string;
    driver_in_time: string;
    driver_out_time: string;
    normal_hours_threshold: string;
    driver_hourly_rate: string;
    driver_daily_rate: string;
    driver_ot_hours: string;
    driver_ot_amount: string;
    driver_salary_amount: string;
    start_odometer: string;
    end_odometer: string;
    garage_distance_km: string;
    internal_distance_km: string;
    normal_overtime_minutes: string;
    double_overtime_minutes: string;
    triple_overtime_minutes: string;
    night_out_count: string;
    trip_from: string;
    trip_to: string;
    trip_purpose: string;
    odometer_variance_reason: string;
    remarks: string;
}

interface EventDraft {
    key: number;
    event_type: string;
    applicability: RentalUsageEventApplicability;
    occurred_at: string;
    quantity: string;
    unit: string;
    reference_number: string;
    remarks: string;
}

const emptyForm = (): UsageForm => ({
    usage_date: businessDateInputValue(),
    started_at: "",
    ended_at: "",
    driver_assignment_id: "",
    driver_in_time: "",
    driver_out_time: "",
    normal_hours_threshold: "12",
    driver_hourly_rate: "",
    driver_daily_rate: "",
    driver_ot_hours: "0.00",
    driver_ot_amount: "0.00",
    driver_salary_amount: "0.00",
    start_odometer: "",
    end_odometer: "",
    garage_distance_km: "0",
    internal_distance_km: "0",
    normal_overtime_minutes: "0",
    double_overtime_minutes: "0",
    triple_overtime_minutes: "0",
    night_out_count: "0",
    trip_from: "",
    trip_to: "",
    trip_purpose: "",
    odometer_variance_reason: "",
    remarks: "",
});

function parseTimeToMinutes(val: string): number | null {
    if (!val) return null;
    if (val.includes("T")) {
        const timePart = val.split("T")[1];
        const [h, m] = timePart.split(":").map(Number);
        return !isNaN(h) ? h * 60 + (m || 0) : null;
    }
    if (val.includes(":")) {
        const [h, m] = val.split(":").map(Number);
        return !isNaN(h) ? h * 60 + (m || 0) : null;
    }
    return null;
}

function calculateDutyMinutes(inVal: string, outVal: string): number | null {
    if (!inVal || !outVal) return null;

    const inHasDate = inVal.includes("-") && inVal.includes("T");
    const outHasDate = outVal.includes("-") && outVal.includes("T");

    if (inHasDate && outHasDate) {
        const inDate = new Date(inVal);
        const outDate = new Date(outVal);
        if (!isNaN(inDate.getTime()) && !isNaN(outDate.getTime())) {
            const diffMs = outDate.getTime() - inDate.getTime();
            return Math.max(0, Math.round(diffMs / 60000));
        }
    }

    const inMins = parseTimeToMinutes(inVal);
    const outMins = parseTimeToMinutes(outVal);
    if (inMins === null || outMins === null) return null;

    if (outMins >= inMins) {
        return outMins - inMins;
    }
    return (24 * 60 - inMins) + outMins;
}

function formatOnAndOffDisplay(startVal?: string | null, endVal?: string | null): string {
    if (!startVal && !endVal) return "-";
    const parse = (v?: string | null) => {
        if (!v) return null;
        if (v.includes("T")) {
            const [datePart, timePart] = v.split("T");
            const d = datePart.split("-").slice(1).join("/");
            const [hh, mm] = timePart.split(":");
            const hourNum = parseInt(hh, 10);
            const period = hourNum >= 12 ? "PM" : "AM";
            const hour12 = hourNum % 12 || 12;
            return {
                date: d,
                time: `${hour12}:${mm} ${period}`,
                fullDate: datePart,
            };
        }
        if (v.includes(":")) {
            const [hh, mm] = v.split(":");
            const hourNum = parseInt(hh, 10);
            const period = hourNum >= 12 ? "PM" : "AM";
            const hour12 = hourNum % 12 || 12;
            return {
                date: "",
                time: `${hour12}:${mm} ${period}`,
                fullDate: "",
            };
        }
        return { date: "", time: v, fullDate: "" };
    };

    const s = parse(startVal);
    const e = parse(endVal);

    if (!s && !e) return "-";
    if (s && !e) return `${s.date ? s.date + " " : ""}${s.time}`;
    if (!s && e) return `${e.date ? e.date + " " : ""}${e.time}`;

    const isDifferentDay = s!.fullDate && e!.fullDate && s!.fullDate !== e!.fullDate;
    if (isDifferentDay) {
        return `${s!.date} ${s!.time} - ${e!.date} ${e!.time} (+1d)`;
    }
    return `${s!.date ? s!.date + " " : ""}${s!.time} - ${e!.time}`;
}

const newEvent = (key: number): EventDraft => ({
    key,
    event_type: "",
    applicability: "customer",
    occurred_at: "",
    quantity: "",
    unit: "",
    reference_number: "",
    remarks: "",
});

const optionLabel = (value: string) =>
    value
        .split("_")
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(" ");

const assignmentLabel = (
    assignment: NonNullable<RentalAllocation["drivers"]>[number],
) => {
    const employee = readableRelation(assignment.employee);
    const period = `${formatDate(assignment.assigned_from)} - ${
        assignment.assigned_to ? formatDate(assignment.assigned_to) : "Open"
    }`;

    return `${employee} - ${optionLabel(assignment.assignment_role)} - ${period}`;
};

function allocationLookupValue(allocation: RentalAllocation): NamedResource {
    return {
        id: allocation.id,
        code: allocation.allocation_number,
        name: [
            allocation.allocation_number,
            allocation.vehicle?.registration_number ?? allocation.vehicle?.name,
            allocation.status,
        ]
            .filter(Boolean)
            .join(" - "),
    };
}

function allocationAgreementKind(allocation: RentalAllocation | null): string | null {
    const agreement = allocation?.agreement;

    return agreement && "agreement_kind" in agreement && typeof agreement.agreement_kind === "string"
        ? agreement.agreement_kind
        : null;
}

export default function RentalRunningChartPage() {
    const auth = useAuth();
    const [searchParams, setSearchParams] = useSearchParams();
    const initialAllocationId = parsePositiveInteger(searchParams.get("allocation_id"));

    const [allocation, setAllocation] = useState<NamedResource | null>(null);
    const [allocationDetails, setAllocationDetails] = useState<RentalAllocation | null>(null);
    const [allocationIdToLoad, setAllocationIdToLoad] = useState<number | null>(initialAllocationId);
    const [loadingAllocation, setLoadingAllocation] = useState(false);

    const [form, setForm] = useState<UsageForm>(emptyForm);
    const [events, setEvents] = useState<EventDraft[]>([]);
    const [nextEventKey, setNextEventKey] = useState(1);
    const [selectedUsageId, setSelectedUsageId] = useState<number | null>(null);
    const [physicalTransitionReason, setPhysicalTransitionReason] = useState("");
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    useEffect(() => {
        setAllocationIdToLoad(initialAllocationId);
    }, [initialAllocationId]);

    useEffect(() => {
        if (!allocationIdToLoad) {
            setAllocation(null);
            setAllocationDetails(null);
            setForm(emptyForm());
            setEvents([]);
            setSelectedUsageId(null);
            return;
        }

        const controller = new AbortController();
        setActionError(null);
        setLoadingAllocation(true);

        void getRentalAllocation(allocationIdToLoad, controller.signal)
            .then((resource) => {
                setAllocation(allocationLookupValue(resource));
                setAllocationDetails(resource);
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) {
                    setActionError(toApiError(requestError));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setLoadingAllocation(false);
                }
            });

        return () => controller.abort();
    }, [allocationIdToLoad]);

    const selectedAllocationId = allocation?.id ?? allocationIdToLoad;

    const metadata = useApi((signal) => getRentalMetadata(signal), []);
    const logs = useApi(
        (signal) =>
            listRentalUsageLogs(
                {
                    vehicle_allocation_id: selectedAllocationId ?? undefined,
                    per_page: 50,
                },
                signal,
            ),
        [selectedAllocationId],
        selectedAllocationId !== null,
    );

    useEffect(() => {
        if (!allocationDetails || allocationDetails.status !== "active") return;
        const latestLog = logs.data?.data?.[0];
        const suggestedOdometer =
            latestLog?.end_odometer ??
            (allocationDetails.start_odometer !== null &&
            allocationDetails.start_odometer !== undefined
                ? String(allocationDetails.start_odometer)
                : "");
        if (!suggestedOdometer) return;
        setForm((current) => {
            if (
                current.start_odometer === "" ||
                (latestLog?.end_odometer && current.start_odometer === String(allocationDetails.start_odometer))
            ) {
                return {
                    ...current,
                    start_odometer: String(suggestedOdometer),
                };
            }
            return current;
        });
    }, [allocationDetails, logs.data]);

    const selectedUsage = useMemo(
        () =>
            (logs.data?.data ?? []).find((row) => row.id === selectedUsageId) ??
            null,
        [logs.data, selectedUsageId],
    );

    const driverAssignmentOptions = useMemo(
        () => [
            { value: "", label: "No company driver" },
            ...(allocationDetails?.drivers ?? [])
                .filter((assignment) => assignment.status === "active")
                .map((assignment) => ({
                    value: String(assignment.id),
                    label: assignmentLabel(assignment),
                })),
        ],
        [allocationDetails],
    );

    useEffect(() => {
        if (!allocationDetails?.drivers) return;
        const activeAssignments = allocationDetails.drivers.filter(
            (assignment) => assignment.status === "active",
        );
        if (activeAssignments.length === 0) return;

        setForm((current) => {
            const baseDate = current.usage_date || new Date().toISOString().split("T")[0];

            if (current.driver_assignment_id) {
                const matched = activeAssignments.find(
                    (assignment) => String(assignment.id) === current.driver_assignment_id,
                );
                if (matched?.employee) {
                    const hourly = matched.employee.default_hourly_rate
                        ? String(Number(matched.employee.default_hourly_rate))
                        : current.driver_hourly_rate;
                    const daily = matched.employee.default_daily_rate
                        ? String(Number(matched.employee.default_daily_rate))
                        : current.driver_daily_rate;
                    const inTime = matched.employee.work_start_time
                        ? `${baseDate}T${matched.employee.work_start_time}`
                        : current.driver_in_time;
                    const outTime = matched.employee.work_end_time
                        ? `${baseDate}T${matched.employee.work_end_time}`
                        : current.driver_out_time;
                    const shiftHours = matched.employee.standard_shift_hours
                        ? String(matched.employee.standard_shift_hours)
                        : current.normal_hours_threshold;
                    if (
                        hourly !== current.driver_hourly_rate ||
                        daily !== current.driver_daily_rate ||
                        (!current.driver_in_time && inTime) ||
                        (!current.driver_out_time && outTime)
                    ) {
                        return {
                            ...current,
                            driver_hourly_rate: current.driver_hourly_rate || hourly,
                            driver_daily_rate: current.driver_daily_rate || daily,
                            driver_in_time: current.driver_in_time || inTime,
                            driver_out_time: current.driver_out_time || outTime,
                            normal_hours_threshold: current.normal_hours_threshold || shiftHours,
                        };
                    }
                }
                return current;
            }

            const primary =
                activeAssignments.find((assignment) => assignment.is_primary) ??
                activeAssignments[0];
            const hourly = primary.employee?.default_hourly_rate
                ? String(Number(primary.employee.default_hourly_rate))
                : "";
            const daily = primary.employee?.default_daily_rate
                ? String(Number(primary.employee.default_daily_rate))
                : "";
            const inTime = primary.employee?.work_start_time
                ? `${baseDate}T${primary.employee.work_start_time}`
                : current.driver_in_time;
            const outTime = primary.employee?.work_end_time
                ? `${baseDate}T${primary.employee.work_end_time}`
                : current.driver_out_time;
            const shiftHours = primary.employee?.standard_shift_hours
                ? String(primary.employee.standard_shift_hours)
                : current.normal_hours_threshold;

            return {
                ...current,
                driver_assignment_id: String(primary.id),
                driver_hourly_rate: hourly,
                driver_daily_rate: daily,
                driver_in_time: inTime,
                driver_out_time: outTime,
                normal_hours_threshold: shiftHours,
            };
        });
    }, [allocationDetails]);

    const onUsageDateChange = (newDate: string) => {
        setForm((current) => {
            const updateWithDate = (existing: string, defaultTime: string) => {
                if (!newDate) return existing;
                const timePart = existing && existing.includes("T")
                    ? existing.split("T")[1].slice(0, 5)
                    : (existing && existing.includes(":") ? existing.slice(0, 5) : defaultTime);
                return `${newDate}T${timePart}`;
            };

            const nextStarted = updateWithDate(current.started_at, "08:00");
            const nextEnded = updateWithDate(current.ended_at, "17:00");
            const nextDriverIn = current.driver_in_time
                ? updateWithDate(current.driver_in_time, "08:00")
                : "";
            const nextDriverOut = current.driver_out_time
                ? updateWithDate(current.driver_out_time, "17:00")
                : "";

            return {
                ...current,
                usage_date: newDate,
                started_at: nextStarted,
                ended_at: nextEnded,
                driver_in_time: nextDriverIn,
                driver_out_time: nextDriverOut,
            };
        });
    };

    const setSameDayAsUsageDate = (
        field: "started_at" | "driver_in_time",
        defaultTime: string,
    ) => {
        setForm((current) => {
            const baseDate = current.usage_date || new Date().toISOString().split("T")[0];
            const existing = current[field];
            const timePart = existing && existing.includes("T")
                ? existing.split("T")[1].slice(0, 5)
                : (existing && existing.includes(":") ? existing.slice(0, 5) : defaultTime);
            return {
                ...current,
                [field]: `${baseDate}T${timePart}`,
            };
        });
    };

    const setOvernightOff = (
        targetField: "ended_at" | "driver_out_time",
        referenceField: "started_at" | "driver_in_time",
    ) => {
        setForm((current) => {
            const refVal = current[referenceField] || current.usage_date;
            const targetVal = current[targetField];
            if (!refVal) return current;

            const refDateStr = refVal.includes("T") ? refVal.split("T")[0] : refVal;
            const refDate = new Date(refDateStr + "T00:00:00");
            refDate.setDate(refDate.getDate() + 1);
            const nextDayStr = refDate.toISOString().split("T")[0];

            const timePart = targetVal && targetVal.includes("T")
                ? targetVal.split("T")[1].slice(0, 5)
                : (targetVal && targetVal.includes(":") ? targetVal.slice(0, 5) : "06:00");

            return {
                ...current,
                [targetField]: `${nextDayStr}T${timePart}`,
            };
        });
    };

    const setSameDayOff = (
        targetField: "ended_at" | "driver_out_time",
        referenceField: "started_at" | "driver_in_time",
    ) => {
        setForm((current) => {
            const refVal = current[referenceField] || current.usage_date;
            const targetVal = current[targetField];
            if (!refVal) return current;

            const sameDayStr = refVal.includes("T") ? refVal.split("T")[0] : refVal;
            const timePart = targetVal && targetVal.includes("T")
                ? targetVal.split("T")[1].slice(0, 5)
                : (targetVal && targetVal.includes(":") ? targetVal.slice(0, 5) : "18:00");

            return {
                ...current,
                [targetField]: `${sameDayStr}T${timePart}`,
            };
        });
    };

    const onDriverAssignmentChange = (newAssignmentId: string) => {
        const selected = allocationDetails?.drivers?.find(
            (assignment) => String(assignment.id) === newAssignmentId,
        );
        const hourly = selected?.employee?.default_hourly_rate
            ? String(Number(selected.employee.default_hourly_rate))
            : "";
        const daily = selected?.employee?.default_daily_rate
            ? String(Number(selected.employee.default_daily_rate))
            : "";
        const baseDate = form.usage_date || (form.started_at && form.started_at.includes("T") ? form.started_at.split("T")[0] : new Date().toISOString().split("T")[0]);
        const inTime = selected?.employee?.work_start_time;
        const outTime = selected?.employee?.work_end_time;
        const shiftHours = selected?.employee?.standard_shift_hours
            ? String(selected.employee.standard_shift_hours)
            : "";
        setForm((current) => ({
            ...current,
            driver_assignment_id: newAssignmentId,
            driver_hourly_rate: hourly || current.driver_hourly_rate,
            driver_daily_rate: daily || current.driver_daily_rate,
            driver_in_time: inTime ? `${baseDate}T${inTime}` : current.driver_in_time,
            driver_out_time: outTime ? `${baseDate}T${outTime}` : current.driver_out_time,
            normal_hours_threshold: shiftHours || current.normal_hours_threshold,
        }));
    };

    const loadDriverSchedule = () => {
        const selected = allocationDetails?.drivers?.find(
            (assignment) => String(assignment.id) === form.driver_assignment_id,
        );
        const baseDate = form.usage_date || (form.started_at && form.started_at.includes("T") ? form.started_at.split("T")[0] : new Date().toISOString().split("T")[0]);
        const inTime = selected?.employee?.work_start_time || "08:00";
        const outTime = selected?.employee?.work_end_time || "17:00";
        const shiftHours = selected?.employee?.standard_shift_hours
            ? String(selected.employee.standard_shift_hours)
            : form.normal_hours_threshold;

        setForm((current) => ({
            ...current,
            driver_in_time: `${baseDate}T${inTime}`,
            driver_out_time: `${baseDate}T${outTime}`,
            normal_hours_threshold: shiftHours,
        }));
    };

    const syncTimesFromTrip = () => {
        setForm((current) => ({
            ...current,
            driver_in_time: current.started_at,
            driver_out_time: current.ended_at,
        }));
    };

    useEffect(() => {
        if (!form.started_at && !form.ended_at) return;
        setForm((current) => {
            let nextIn = current.driver_in_time;
            let nextOut = current.driver_out_time;
            if (!nextIn && current.started_at && current.started_at.includes("T")) {
                nextIn = current.started_at;
            }
            if (!nextOut && current.ended_at && current.ended_at.includes("T")) {
                nextOut = current.ended_at;
            }
            if (nextIn !== current.driver_in_time || nextOut !== current.driver_out_time) {
                return {
                    ...current,
                    driver_in_time: nextIn,
                    driver_out_time: nextOut,
                };
            }
            return current;
        });
    }, [form.started_at, form.ended_at]);

    useEffect(() => {
        const dutyMins = calculateDutyMinutes(form.driver_in_time, form.driver_out_time);
        if (dutyMins === null) return;

        const thresholdHours = Number(form.normal_hours_threshold) || 12;
        const thresholdMins = Math.round(thresholdHours * 60);
        const otMins = Math.max(0, dutyMins - thresholdMins);
        const otHours = otMins / 60;

        const isSunday = form.usage_date ? new Date(form.usage_date + "T00:00:00").getDay() === 0 : false;
        const normalOt = isSunday ? 0 : otMins;
        const doubleOt = isSunday ? otMins : 0;

        const hourly = Number(form.driver_hourly_rate) || 0;
        const daily = Number(form.driver_daily_rate) || 0;
        const otAmount = otHours * hourly;
        const salaryAmount = daily + otAmount;

        setForm((current) => {
            const nextNormal = String(normalOt);
            const nextDouble = String(doubleOt);
            const nextOtHours = otHours.toFixed(2);
            const nextOtAmount = otAmount.toFixed(2);
            const nextSalary = salaryAmount.toFixed(2);

            if (
                current.normal_overtime_minutes === nextNormal &&
                current.double_overtime_minutes === nextDouble &&
                current.driver_ot_hours === nextOtHours &&
                current.driver_ot_amount === nextOtAmount &&
                current.driver_salary_amount === nextSalary
            ) {
                return current;
            }

            return {
                ...current,
                normal_overtime_minutes: nextNormal,
                double_overtime_minutes: nextDouble,
                driver_ot_hours: nextOtHours,
                driver_ot_amount: nextOtAmount,
                driver_salary_amount: nextSalary,
            };
        });
    }, [
        form.driver_in_time,
        form.driver_out_time,
        form.normal_hours_threshold,
        form.driver_hourly_rate,
        form.driver_daily_rate,
        form.usage_date,
    ]);

    const dutySummary = useMemo(() => {
        const dutyMins = calculateDutyMinutes(form.driver_in_time, form.driver_out_time);
        if (dutyMins === null) {
            return {
                durationText: "0h 00m (0.00h)",
                otHoursText: "0.00 hrs",
                otCategory: "Normal",
                otAmountText: "0.00",
                totalSalaryText: Number(form.driver_daily_rate || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }),
            };
        }
        const hoursPart = Math.floor(dutyMins / 60);
        const minsPart = dutyMins % 60;
        const totalHours = (dutyMins / 60).toFixed(2);

        const thresholdHours = Number(form.normal_hours_threshold) || 12;
        const thresholdMins = Math.round(thresholdHours * 60);
        const otMins = Math.max(0, dutyMins - thresholdMins);
        const otHours = otMins / 60;

        const isSunday = form.usage_date ? new Date(form.usage_date + "T00:00:00").getDay() === 0 : false;
        const hourly = Number(form.driver_hourly_rate) || 0;
        const daily = Number(form.driver_daily_rate) || 0;
        const otAmount = otHours * hourly;
        const totalSalary = daily + otAmount;

        const isOvernight =
            form.driver_in_time.includes("T") &&
            form.driver_out_time.includes("T") &&
            form.driver_in_time.split("T")[0] !== form.driver_out_time.split("T")[0];

        return {
            durationText: `${hoursPart}h ${String(minsPart).padStart(2, "0")}m (${totalHours}h)${isOvernight ? " [Overnight +1d]" : ""}`,
            otHoursText: `${otHours.toFixed(2)} hrs (${otMins}m)`,
            otCategory: isSunday ? "Double OT (Sunday)" : (otMins > 0 ? "Normal OT" : "Standard"),
            otAmountText: otAmount.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }),
            totalSalaryText: totalSalary.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }),
        };
    }, [
        form.driver_in_time,
        form.driver_out_time,
        form.normal_hours_threshold,
        form.driver_hourly_rate,
        form.driver_daily_rate,
        form.usage_date,
    ]);

    const eventTypeOptions = useMemo(
        () => [
            { value: "", label: "Select event type" },
            ...(metadata.data?.usage_event_types ?? []).map((value) => ({
                value,
                label: optionLabel(value),
            })),
        ],
        [metadata.data],
    );

    const eventApplicabilityOptions = useMemo(
        () =>
            (metadata.data?.usage_event_applicabilities ?? []).map((value) => ({
                value,
                label:
                    value === "customer"
                        ? "Customer / lessee only"
                        : value === "owner"
                          ? "Vehicle owner / lessor only"
                          : value === "both"
                            ? "Both commercial sides"
                            : "Internal only",
            })),
        [metadata.data],
    );

    const canRecord = hasPermission(auth, vehicleRentalPermissions.usageRecord);
    const canApprove = hasPermission(auth, vehicleRentalPermissions.usageApprove);

    const isOwnerSupply =
        allocationAgreementKind(allocationDetails) === "owner_supply";
    const isAllocationClosed =
        allocationDetails !== null &&
        ["returned", "replaced", "completed"].includes(allocationDetails.status);
    const isAllocationPlanned = allocationDetails?.status === "planned";
    const isAllocationActive = allocationDetails?.status === "active";

    const addEvent = () => {
        setEvents((current) => [...current, newEvent(nextEventKey)]);
        setNextEventKey((current) => current + 1);
    };

    const updateEvent = (key: number, patch: Partial<EventDraft>) => {
        setEvents((current) =>
            current.map((row) => (row.key === key ? { ...row, ...patch } : row)),
        );
    };

    const save = async (event: FormEvent) => {
        event.preventDefault();
        if (!selectedAllocationId || !allocationDetails || !isAllocationActive || isOwnerSupply) {
            return;
        }
        setSaving(true);
        setActionError(null);
        try {
            await createRentalUsageLog(selectedAllocationId, {
                usage_date: form.usage_date,
                started_at: form.started_at,
                ended_at: form.ended_at,
                driver_assignment_id: form.driver_assignment_id
                    ? Number(form.driver_assignment_id)
                    : null,
                start_odometer: form.start_odometer,
                end_odometer: form.end_odometer,
                garage_distance_km: form.garage_distance_km,
                internal_distance_km: form.internal_distance_km,
                normal_overtime_minutes: Number(form.normal_overtime_minutes),
                double_overtime_minutes: Number(form.double_overtime_minutes),
                triple_overtime_minutes: Number(form.triple_overtime_minutes),
                night_out_count: form.night_out_count,
                trip_from: form.trip_from || null,
                trip_to: form.trip_to || null,
                trip_purpose: form.trip_purpose || null,
                odometer_variance_reason:
                    form.odometer_variance_reason || null,
                remarks: form.remarks || null,
                metadata: {
                    driver_in_time: form.driver_in_time || null,
                    driver_out_time: form.driver_out_time || null,
                    normal_hours_threshold: Number(form.normal_hours_threshold) || 12,
                    driver_hourly_rate: Number(form.driver_hourly_rate) || 0,
                    driver_daily_rate: Number(form.driver_daily_rate) || 0,
                    driver_ot_hours: Number(form.driver_ot_hours) || 0,
                    driver_ot_amount: Number(form.driver_ot_amount) || 0,
                    driver_salary_amount: Number(form.driver_salary_amount) || 0,
                },
                events: events.map(({ key: _key, ...row }) => ({
                    ...row,
                    occurred_at: row.occurred_at || null,
                    unit: row.unit || null,
                    reference_number: row.reference_number || null,
                    remarks: row.remarks || null,
                })),
            });
            setForm(emptyForm());
            setEvents([]);
            setSelectedUsageId(null);
            logs.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const transition = async (row: RentalUsageLog, status: string) => {
        setActionError(null);
        try {
            await transitionRentalUsageLog(
                row.id,
                row.row_version,
                status,
                physicalTransitionReason || undefined,
            );
            setPhysicalTransitionReason("");
            logs.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const contextStatus = (row: RentalUsageLog, side: "revenue" | "cost") =>
        row.contexts.find((context) => context.financial_side === side)?.usage_fact
            ?.status ?? "not_applicable";

    const columns: DataColumn<RentalUsageLog>[] = [
        {
            key: "number",
            header: "Running chart",
            render: (row) => row.usage_number,
        },
        {
            key: "date",
            header: "Date",
            render: (row) => formatDate(row.usage_date),
        },
        {
            key: "driver",
            header: "Driver assignment",
            render: (row) =>
                row.driver_assignment
                    ? readableRelation(row.driver_assignment.employee)
                    : "Without company driver",
        },
        {
            key: "vehicle_on_off",
            header: "Vehicle (ON - OFF)",
            render: (row) => formatOnAndOffDisplay(row.started_at, row.ended_at),
        },
        {
            key: "duty_time",
            header: "Driver Duty (ON - OFF)",
            render: (row) => {
                const inTime = row.metadata?.driver_in_time;
                const outTime = row.metadata?.driver_out_time;
                if (inTime && outTime) {
                    return formatOnAndOffDisplay(inTime, outTime);
                }
                return "-";
            },
        },
        {
            key: "driver_salary",
            header: "Driver OT / Salary",
            render: (row) => {
                const totalSalary = row.metadata?.driver_salary_amount;
                const otAmount = row.metadata?.driver_ot_amount;
                const otHours = row.metadata?.driver_ot_hours;
                if (totalSalary !== undefined && totalSalary !== null && Number(totalSalary) > 0) {
                    return (
                        <div className="text-xs">
                            <span className="font-semibold text-slate-900">
                                Rs. {Number(totalSalary).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </span>
                            {Number(otAmount) > 0 && (
                                <span className="block text-[11px] text-amber-700">
                                    OT ({otHours ?? "-"}h): Rs. {Number(otAmount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </span>
                            )}
                        </div>
                    );
                }
                const otMin = (row.normal_overtime_minutes || 0) + (row.double_overtime_minutes || 0);
                return otMin > 0 ? `${(otMin / 60).toFixed(1)}h OT` : "-";
            },
        },
        {
            key: "distance",
            header: "Physical KM",
            render: (row) =>
                `${row.distance_km} total / ${row.net_operational_distance_km} net`,
        },
        {
            key: "physical_status",
            header: "Physical",
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: "customer_status",
            header: "Customer facts",
            render: (row) => <StatusBadge status={contextStatus(row, "revenue")} />,
        },
        {
            key: "owner_status",
            header: "Owner facts",
            render: (row) => <StatusBadge status={contextStatus(row, "cost")} />,
        },
        {
            key: "actions",
            header: "",
            className: "text-right",
            render: (row) => (
                <div className="flex flex-wrap justify-end gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => setSelectedUsageId(row.id)}
                    >
                        Manage facts
                    </Button>
                    {canRecord && row.status === "draft" && (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => void transition(row, "submitted")}
                        >
                            Submit physical usage
                        </Button>
                    )}
                    {canApprove && row.status === "submitted" && (
                        <>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => void transition(row, "approved")}
                            >
                                Approve physical usage
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => void transition(row, "rejected")}
                            >
                                Reject
                            </Button>
                        </>
                    )}
                </div>
            ),
        },
    ];

    return (
        <RentalPage>
            <ContentHeader
                title="Daily running chart"
                description="Record physical vehicle usage once, then approve customer billable and vehicle-owner payable facts independently."
                actions={
                    <LinkButton
                        to={
                            allocation?.id
                                ? `/vehicle-rental/running-chart/report?allocation_id=${allocation.id}`
                                : "/vehicle-rental/running-chart/report"
                        }
                        variant="secondary"
                    >
                        📄 Running Chart Report (A4 Sheet)
                    </LinkButton>
                }
            />
            <ErrorAlert
                error={
                    actionError ??
                    metadata.error ??
                    logs.error
                }
            />
            <Panel title="Rental allocation">
                <RentalAllocationLookupSelect
                    value={allocation}
                    onChange={(val) => {
                        setAllocation(val);
                        setAllocationDetails(null);
                        setAllocationIdToLoad(val?.id ?? null);
                        setSearchParams(
                            val?.id ? { allocation_id: String(val.id) } : {},
                        );
                        setForm(emptyForm());
                        setEvents([]);
                        setSelectedUsageId(null);
                    }}
                />
                <p className="mt-2 text-sm text-slate-500">
                    The selected allocation controls the vehicle, customer agreement,
                    owner agreement and valid driver assignments.
                </p>
                {allocationDetails?.replacement_allocation && (
                    <div className="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-950 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span className="font-bold">Notice:</span> This vehicle was replaced by{" "}
                            <span className="font-semibold">{readableRelation(allocationDetails.replacement_allocation.vehicle) || allocationDetails.replacement_allocation.allocation_number}</span>.
                            Usage after the replacement date should be recorded on the replacement vehicle's allocation.
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                if (allocationDetails.replacement_allocation?.id) {
                                    setAllocationIdToLoad(allocationDetails.replacement_allocation.id);
                                    setSearchParams({ allocation_id: String(allocationDetails.replacement_allocation.id) });
                                }
                            }}
                        >
                            Switch to Replacement Chart -&gt;
                        </Button>
                    </div>
                )}
                {allocationDetails?.replaces_allocation && (
                    <div className="mt-3 rounded-lg border border-blue-300 bg-blue-50 p-3 text-xs text-blue-950 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span className="font-bold">Replacement Vehicle:</span> This vehicle is serving as a replacement for{" "}
                            <span className="font-semibold">{readableRelation(allocationDetails.replaces_allocation.vehicle) || allocationDetails.replaces_allocation.allocation_number}</span>.
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                if (allocationDetails.replaces_allocation?.id) {
                                    setAllocationIdToLoad(allocationDetails.replaces_allocation.id);
                                    setSearchParams({ allocation_id: String(allocationDetails.replaces_allocation.id) });
                                }
                            }}
                        >
                            &lt;- View Original Vehicle Chart
                        </Button>
                    </div>
                )}
            </Panel>

            {loadingAllocation && (
                <div className="mt-5">
                    <LoadingState />
                </div>
            )}

            {allocationDetails && (
                <Panel title="Allocation details" className="mt-5">
                    <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div>
                            <div className="text-xs text-slate-500">Status</div>
                            <div className="mt-1 font-medium">
                                <StatusBadge status={allocationDetails.status} />
                            </div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Vehicle</div>
                            <div className="mt-1 font-medium">
                                {readableRelation(allocationDetails.vehicle) || "-"}
                            </div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Agreement</div>
                            <div className="mt-1 font-medium">
                                {readableRelation(allocationDetails.agreement) || "-"}
                            </div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-500">Start odometer</div>
                            <div className="mt-1 font-medium">
                                {allocationDetails.start_odometer ?? "-"}
                            </div>
                        </div>
                    </div>
                </Panel>
            )}

            {isAllocationPlanned && (
                <Panel
                    title="Vehicle handover required"
                    className="mt-5 border-amber-200 bg-amber-50 text-amber-900"
                >
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p className="font-semibold text-amber-900">
                                Allocation is currently Planned
                            </p>
                            <p className="mt-1 text-sm text-amber-800">
                                Daily running charts can only be recorded after the vehicle has been handed over to the customer. Confirm vehicle custody handover to activate this allocation.
                            </p>
                        </div>
                        <LinkButton
                            variant="primary"
                            to={`/vehicle-rental/custody?allocation_id=${allocationDetails?.id}`}
                        >
                            Handover vehicle now
                        </LinkButton>
                    </div>
                </Panel>
            )}

            {isOwnerSupply && (
                <Panel
                    title="Customer rental required"
                    className="mt-5 border-amber-200 bg-amber-50 text-amber-900"
                >
                    <p className="text-sm text-amber-800">
                        Daily running charts can only be recorded against customer rental allocations. This allocation belongs to an owner supply agreement.
                    </p>
                </Panel>
            )}

            {isAllocationClosed && (
                <Panel title="Allocation closed" className="mt-5">
                    <p className="text-sm text-slate-600">
                        This allocation is {allocationDetails?.status}. Vehicle custody has ended and no further running charts can be recorded. Past running charts remain available below for review.
                    </p>
                </Panel>
            )}

            {canRecord &&
                isAllocationActive &&
                !isOwnerSupply && (
                    <form onSubmit={save} className="mt-5 space-y-5">
                    <Panel title="Physical usage">
                        <p className="mb-4 text-sm text-slate-600">
                            Enter actual time and odometer facts. Customer billable and
                            owner payable values are reviewed separately after saving.
                        </p>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Input
                                label="Usage date"
                                type="date"
                                required
                                value={form.usage_date}
                                onChange={(event) =>
                                    onUsageDateChange(event.target.value)
                                }
                            />
                            <div>
                                <Input
                                    label="Actual start"
                                    type="datetime-local"
                                    required
                                    value={form.started_at}
                                    onChange={(event) =>
                                        setForm({
                                            ...form,
                                            started_at: event.target.value,
                                        })
                                    }
                                />
                                <div className="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                                    <span className="font-medium text-slate-600">Vehicle ON Time</span>
                                    <button
                                        type="button"
                                        onClick={() => setSameDayAsUsageDate("started_at", "08:00")}
                                        className="text-indigo-600 hover:text-indigo-800 underline"
                                    >
                                        Same as Usage Date
                                    </button>
                                </div>
                            </div>
                            <div>
                                <Input
                                    label="Actual finish"
                                    type="datetime-local"
                                    required
                                    value={form.ended_at}
                                    onChange={(event) =>
                                        setForm({
                                            ...form,
                                            ended_at: event.target.value,
                                        })
                                    }
                                />
                                <div className="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                                    <span className="font-medium text-slate-600">Vehicle OFF Time</span>
                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setSameDayOff("ended_at", "started_at")}
                                            className="text-indigo-600 hover:text-indigo-800 underline"
                                        >
                                            Same Day OFF
                                        </button>
                                        <span>|</span>
                                        <button
                                            type="button"
                                            onClick={() => setOvernightOff("ended_at", "started_at")}
                                            className="text-indigo-600 hover:text-indigo-800 underline font-medium"
                                        >
                                            +1 Day (Overnight OFF)
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <Select
                                label="Valid driver assignment"
                                value={form.driver_assignment_id}
                                onChange={(event) =>
                                    onDriverAssignmentChange(event.target.value)
                                }
                                options={driverAssignmentOptions}
                            />
                        </div>

                        {/* Driver In/Out & Overtime Salary Calculation Section */}
                        <div className="mt-4 rounded-lg border border-indigo-200 bg-indigo-50/50 p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-indigo-100 pb-2">
                                <div>
                                    <h4 className="text-sm font-bold text-indigo-950">
                                        Driver Duty, ON & OFF Times (With Date) &amp; Overtime Salary
                                    </h4>
                                    <p className="text-xs text-indigo-700">
                                        Enter driver duty ON and OFF date &amp; times to automatically calculate exact duty hours, overnight shifts, and daily salary.
                                    </p>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={loadDriverSchedule}
                                    >
                                        Load driver regular hours
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={syncTimesFromTrip}
                                    >
                                        Sync from Vehicle ON/OFF
                                    </Button>
                                </div>
                            </div>
                            <div className="mt-3 grid gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
                                <div>
                                    <Input
                                        label="Driver ON Time (Duty Start Date & Time)"
                                        type="datetime-local"
                                        value={form.driver_in_time}
                                        onChange={(event) =>
                                            setForm({ ...form, driver_in_time: event.target.value })
                                        }
                                    />
                                    <div className="mt-1 flex items-center gap-1.5 text-[11px] text-slate-500">
                                        <button
                                            type="button"
                                            onClick={() => setSameDayAsUsageDate("driver_in_time", "08:00")}
                                            className="text-indigo-600 hover:text-indigo-800 underline"
                                        >
                                            Same as Usage Date
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <Input
                                        label="Driver OFF Time (Duty Finish Date & Time)"
                                        type="datetime-local"
                                        value={form.driver_out_time}
                                        onChange={(event) =>
                                            setForm({ ...form, driver_out_time: event.target.value })
                                        }
                                    />
                                    <div className="mt-1 flex items-center gap-2 text-[11px] text-slate-500">
                                        <button
                                            type="button"
                                            onClick={() => setSameDayOff("driver_out_time", "driver_in_time")}
                                            className="text-indigo-600 hover:text-indigo-800 underline"
                                        >
                                            Same Day OFF
                                        </button>
                                        <span>|</span>
                                        <button
                                            type="button"
                                            onClick={() => setOvernightOff("driver_out_time", "driver_in_time")}
                                            className="text-indigo-600 hover:text-indigo-800 underline font-medium"
                                        >
                                            +1 Day (Overnight OFF)
                                        </button>
                                    </div>
                                </div>
                                <Select
                                    label="Daily shift standard"
                                    value={form.normal_hours_threshold}
                                    onChange={(event) =>
                                        setForm({ ...form, normal_hours_threshold: event.target.value })
                                    }
                                    options={[
                                        { value: "12", label: "12 Hours (Corporate standard)" },
                                        { value: "8", label: "8 Hours (Standard shift)" },
                                        { value: "9", label: "9 Hours" },
                                        { value: "10", label: "10 Hours" },
                                    ]}
                                />
                                <Input
                                    label="Driver base salary (Rs.)"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={form.driver_daily_rate}
                                    onChange={(event) =>
                                        setForm({ ...form, driver_daily_rate: event.target.value })
                                    }
                                />
                                <Input
                                    label="Driver OT rate (Rs./hr)"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={form.driver_hourly_rate}
                                    onChange={(event) =>
                                        setForm({ ...form, driver_hourly_rate: event.target.value })
                                    }
                                />
                            </div>

                            {/* Live calculation banner */}
                            <div className="mt-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 pt-2 border-t border-indigo-100 text-xs">
                                <div className="rounded bg-white p-2 border border-indigo-100">
                                    <span className="text-slate-500">Duty Duration</span>
                                    <p className="mt-0.5 font-bold text-indigo-950">
                                        {dutySummary.durationText}
                                    </p>
                                </div>
                                <div className="rounded bg-white p-2 border border-indigo-100">
                                    <span className="text-slate-500">Overtime Hours</span>
                                    <p className="mt-0.5 font-bold text-amber-700">
                                        {dutySummary.otHoursText}
                                    </p>
                                </div>
                                <div className="rounded bg-white p-2 border border-indigo-100">
                                    <span className="text-slate-500">OT Category</span>
                                    <p className="mt-0.5 font-bold text-slate-800">
                                        {dutySummary.otCategory}
                                    </p>
                                </div>
                                <div className="rounded bg-white p-2 border border-indigo-100">
                                    <span className="text-slate-500">Calculated OT Pay</span>
                                    <p className="mt-0.5 font-bold text-amber-800">
                                        Rs. {dutySummary.otAmountText}
                                    </p>
                                </div>
                                <div className="rounded bg-white p-2 border border-indigo-200">
                                    <span className="text-slate-500">Total Driver Salary</span>
                                    <p className="mt-0.5 font-black text-indigo-900">
                                        Rs. {dutySummary.totalSalaryText}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Input
                                label="Start odometer"
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                value={form.start_odometer}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        start_odometer: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="End odometer"
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                value={form.end_odometer}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        end_odometer: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Garage KM"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.garage_distance_km}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        garage_distance_km: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Internal / non-commercial KM"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.internal_distance_km}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        internal_distance_km: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Normal OT minutes"
                                type="number"
                                min="0"
                                value={form.normal_overtime_minutes}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        normal_overtime_minutes: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Double OT minutes"
                                type="number"
                                min="0"
                                value={form.double_overtime_minutes}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        double_overtime_minutes: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Triple OT minutes"
                                type="number"
                                min="0"
                                value={form.triple_overtime_minutes}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        triple_overtime_minutes: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Night-outs"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.night_out_count}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        night_out_count: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="From"
                                value={form.trip_from}
                                onChange={(event) =>
                                    setForm({ ...form, trip_from: event.target.value })
                                }
                            />
                            <Input
                                label="To"
                                value={form.trip_to}
                                onChange={(event) =>
                                    setForm({ ...form, trip_to: event.target.value })
                                }
                            />
                            <Input
                                label="Purpose"
                                value={form.trip_purpose}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        trip_purpose: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Textarea
                                label="Odometer variance reason"
                                value={form.odometer_variance_reason}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        odometer_variance_reason: event.target.value,
                                    })
                                }
                            />
                            <Textarea
                                label="Remarks"
                                value={form.remarks}
                                onChange={(event) =>
                                    setForm({ ...form, remarks: event.target.value })
                                }
                            />
                        </div>
                    </Panel>

                    <Panel title="Usage events and other charges">
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm text-slate-600">
                                Classify every event by the commercial side it affects.
                                Internal-only events remain operational and are not billed.
                            </p>
                            <Button type="button" variant="secondary" onClick={addEvent}>
                                Add event
                            </Button>
                        </div>
                        {events.length === 0 ? (
                            <p className="text-sm text-slate-500">
                                No parking, toll, waiting, fuel, damage, repair or other
                                events recorded.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {events.map((row, index) => (
                                    <div
                                        key={row.key}
                                        className="rounded-lg border border-slate-200 p-4"
                                    >
                                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                            <Select
                                                label={`Event ${index + 1}`}
                                                required
                                                value={row.event_type}
                                                onChange={(event) =>
                                                    updateEvent(row.key, {
                                                        event_type: event.target.value,
                                                    })
                                                }
                                                options={eventTypeOptions}
                                            />
                                            <Select
                                                label="Applies to"
                                                required
                                                value={row.applicability}
                                                onChange={(event) =>
                                                    updateEvent(row.key, {
                                                        applicability: event.target
                                                            .value as RentalUsageEventApplicability,
                                                    })
                                                }
                                                options={eventApplicabilityOptions}
                                            />
                                            <Input
                                                label="Occurred at"
                                                type="datetime-local"
                                                value={row.occurred_at}
                                                onChange={(event) =>
                                                    updateEvent(row.key, {
                                                        occurred_at: event.target.value,
                                                    })
                                                }
                                            />
                                            <Input
                                                label="Quantity / amount basis"
                                                type="number"
                                                min="0"
                                                step="0.000001"
                                                required
                                                value={row.quantity}
                                                onChange={(event) =>
                                                    updateEvent(row.key, {
                                                        quantity: event.target.value,
                                                    })
                                                }
                                            />
                                            <Input
                                                label="Unit"
                                                value={row.unit}
                                                onChange={(event) =>
                                                    updateEvent(row.key, {
                                                        unit: event.target.value,
                                                    })
                                                }
                                            />
                                            <Input
                                                label="Reference"
                                                value={row.reference_number}
                                                onChange={(event) =>
                                                    updateEvent(row.key, {
                                                        reference_number:
                                                            event.target.value,
                                                    })
                                                }
                                            />
                                            <Input
                                                label="Remarks"
                                                value={row.remarks}
                                                onChange={(event) =>
                                                    updateEvent(row.key, {
                                                        remarks: event.target.value,
                                                    })
                                                }
                                            />
                                        </div>
                                        <div className="mt-3 flex justify-end">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                onClick={() =>
                                                    setEvents((current) =>
                                                        current.filter(
                                                            (eventRow) =>
                                                                eventRow.key !== row.key,
                                                        ),
                                                    )
                                                }
                                            >
                                                Remove event
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                        <div className="mt-4 flex justify-end">
                            <Button type="submit" loading={saving}>
                                Save physical usage
                            </Button>
                        </div>
                    </Panel>
                </form>
            )}

            <div className="mt-5">
                {!selectedAllocationId ? (
                    <Panel>
                        <p className="text-sm text-slate-500">
                            Select an allocation to view and record running charts.
                        </p>
                    </Panel>
                ) : logs.loading ? (
                    <LoadingState />
                ) : (
                    <DataTable
                        rows={logs.data?.data ?? []}
                        columns={columns}
                        rowKey={(row) => row.id}
                        emptyMessage="No running-chart entries exist for this allocation."
                    />
                )}
            </div>

            {selectedUsage && (
                <div className="mt-5 space-y-5">
                    <Panel title={`Physical usage - ${selectedUsage.usage_number}`}>
                        <div className="grid gap-3 md:grid-cols-3">
                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500">
                                    Actual period
                                </p>
                                <p className="mt-1 text-sm">
                                    {selectedUsage.started_at} - {selectedUsage.ended_at}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500">
                                    Actual distance
                                </p>
                                <p className="mt-1 text-sm">
                                    {selectedUsage.distance_km} km total / {" "}
                                    {selectedUsage.net_operational_distance_km} km net
                                </p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500">
                                    Status
                                </p>
                                <div className="mt-1">
                                    <StatusBadge status={selectedUsage.status} />
                                </div>
                            </div>
                        </div>
                        {selectedUsage.metadata && (
                            <div className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Driver Duty &amp; Overtime Reconciliation
                                </h4>
                                <div className="mt-2 grid gap-3 sm:grid-cols-2 md:grid-cols-4 text-xs">
                                    <div>
                                        <span className="text-slate-500">In / Out Time:</span>
                                        <p className="font-semibold text-slate-900 mt-0.5">
                                            {selectedUsage.metadata.driver_in_time || "-"} to {selectedUsage.metadata.driver_out_time || "-"}
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Shift Threshold:</span>
                                        <p className="font-semibold text-slate-900 mt-0.5">
                                            {selectedUsage.metadata.normal_hours_threshold || "12"} hrs
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Overtime Hours:</span>
                                        <p className="font-semibold text-amber-800 mt-0.5">
                                            {selectedUsage.metadata.driver_ot_hours ? `${selectedUsage.metadata.driver_ot_hours} hrs` : "0.00 hrs"}
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Driver OT Amount:</span>
                                        <p className="font-bold text-amber-900 mt-0.5">
                                            Rs. {Number(selectedUsage.metadata.driver_ot_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Base Daily Salary:</span>
                                        <p className="font-semibold text-slate-900 mt-0.5">
                                            Rs. {Number(selectedUsage.metadata.driver_daily_rate || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Hourly OT Rate:</span>
                                        <p className="font-semibold text-slate-900 mt-0.5">
                                            Rs. {Number(selectedUsage.metadata.driver_hourly_rate || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}/hr
                                        </p>
                                    </div>
                                    <div className="sm:col-span-2">
                                        <span className="text-slate-500">Total Driver Day Salary:</span>
                                        <p className="font-black text-slate-950 text-sm mt-0.5">
                                            Rs. {Number(selectedUsage.metadata.driver_salary_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                        {canApprove && selectedUsage.status === "approved" && (
                            <div className="mt-4 grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                                <Input
                                    label="Physical usage reversal reason"
                                    value={physicalTransitionReason}
                                    onChange={(event) =>
                                        setPhysicalTransitionReason(
                                            event.target.value,
                                        )
                                    }
                                />
                                <Button
                                    type="button"
                                    variant="secondary"
                                    disabled={!physicalTransitionReason.trim()}
                                    onClick={() =>
                                        void transition(selectedUsage, "reversed")
                                    }
                                >
                                    Reverse physical usage
                                </Button>
                            </div>
                        )}
                    </Panel>

                    {selectedUsage.contexts.map((context) =>
                        context.usage_fact ? (
                            <RentalUsageFactEditor
                                key={`${context.usage_fact.id}:${context.usage_fact.row_version}`}
                                fact={context.usage_fact}
                                canRecord={canRecord}
                                canApprove={canApprove}
                                onSaved={logs.reload}
                            />
                        ) : null,
                    )}
                </div>
            )}
        </RentalPage>
    );
}
