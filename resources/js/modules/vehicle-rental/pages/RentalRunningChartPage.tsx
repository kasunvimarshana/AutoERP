import { useEffect, useMemo, useState, type FormEvent } from "react";
import { useSearchParams } from "react-router-dom";
import { useAuth } from "@/modules/auth/AuthProvider";
import { hasPermission } from "@/modules/auth/accessControl";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button } from "@/shared/components/Button";
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

const AGREEMENT_KIND_CUSTOMER_RENTAL = "customer_rental";
const APPLICABILITY_CUSTOMER = "customer";
const APPLICABILITY_OWNER = "owner";
const APPLICABILITY_BOTH = "both";
const APPLICABILITY_INTERNAL = "internal";
const RENTAL_MODE_WITH_DRIVER = "with_driver";

interface UsageForm {
    usage_date: string;
    started_at: string;
    ended_at: string;
    driver_assignment_id: string;
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
    reference_number: string;
    remarks: string;
}

const emptyForm = (): UsageForm => ({
    usage_date: businessDateInputValue(),
    started_at: "",
    ended_at: "",
    driver_assignment_id: "",
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

const newEvent = (key: number): EventDraft => ({
    key,
    event_type: "",
    applicability: "customer",
    occurred_at: "",
    quantity: "",
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
            readableRelation(allocation.vehicle),
            allocation.status,
        ].filter(Boolean).join(" - "),
    };
}

export default function RentalRunningChartPage() {
    const auth = useAuth();
    const [searchParams, setSearchParams] = useSearchParams();
    const allocationId = parsePositiveInteger(searchParams.get("allocation_id"));
    const [allocation, setAllocation] = useState<NamedResource | null>(null);
    const [allocationDetails, setAllocationDetails] = useState<RentalAllocation | null>(null);
    const [allocationIdToLoad, setAllocationIdToLoad] = useState<number | null>(allocationId);
    const [loadingAllocation, setLoadingAllocation] = useState(false);
    const [form, setForm] = useState<UsageForm>(emptyForm);
    const [events, setEvents] = useState<EventDraft[]>([]);
    const [nextEventKey, setNextEventKey] = useState(1);
    const [selectedUsageId, setSelectedUsageId] = useState<number | null>(null);
    const [physicalTransitionReason, setPhysicalTransitionReason] = useState("");
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const metadata = useApi((signal) => getRentalMetadata(signal), []);
    const selectedAllocation = allocationDetails;
    const selectedAllocationId = selectedAllocation?.id ?? null;
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
        let cancelled = false;
        queueMicrotask(() => {
            if (!cancelled) setAllocationIdToLoad(allocationId);
        });

        return () => {
            cancelled = true;
        };
    }, [allocationId]);

    useEffect(() => {
        if (!allocationIdToLoad) {
            let cancelled = false;
            queueMicrotask(() => {
                if (cancelled) return;
                setAllocation(null);
                setAllocationDetails(null);
                setLoadingAllocation(false);
            });

            return () => {
                cancelled = true;
            };
        }

        const controller = new AbortController();
        queueMicrotask(() => {
            if (controller.signal.aborted) return;
            setLoadingAllocation(true);
            setActionError(null);
        });

        void getRentalAllocation(allocationIdToLoad, controller.signal)
            .then((resource) => {
                setAllocation(allocationLookupValue(resource));
                setAllocationDetails(
                    resource.status === "active"
                    && resource.agreement?.agreement_kind === AGREEMENT_KIND_CUSTOMER_RENTAL
                        ? resource
                        : null,
                );
            })
            .catch((error: unknown) => {
                if (!controller.signal.aborted) {
                    setAllocation(null);
                    setAllocationDetails(null);
                    setActionError(toApiError(error));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoadingAllocation(false);
            });

        return () => controller.abort();
    }, [allocationIdToLoad]);

    const selectedUsage = useMemo(
        () =>
            (logs.data?.data ?? []).find((row) => row.id === selectedUsageId) ??
            null,
        [logs.data, selectedUsageId],
    );
    const requiresDriverAssignment =
        selectedAllocation?.agreement?.rental_mode === RENTAL_MODE_WITH_DRIVER;
    const driverAssignmentOptions = useMemo(
        () =>
            (selectedAllocation?.drivers ?? [])
                .filter((assignment) => assignment.status === "active")
                .map((assignment) => ({
                    value: String(assignment.id),
                    label: assignmentLabel(assignment),
                })),
        [selectedAllocation],
    );
    const eventTypeOptions = useMemo(
        () =>
            (metadata.data?.usage_event_types ?? []).map((value) => ({
                value,
                label: optionLabel(value),
            })),
        [metadata.data],
    );
    const eventApplicabilityOptions = useMemo(
        () => {
            const hasOwnerContext = selectedAllocation?.source_allocation !== null
                && selectedAllocation?.source_allocation !== undefined;
            const values = (metadata.data?.usage_event_applicabilities ?? []).filter(
                (value) =>
                    hasOwnerContext ||
                    value === APPLICABILITY_CUSTOMER ||
                    value === APPLICABILITY_INTERNAL,
            );

            return values.map((value) => ({
                value,
                label:
                    value === APPLICABILITY_CUSTOMER
                        ? "Customer / lessee only"
                        : value === APPLICABILITY_OWNER
                          ? "Vehicle owner / lessor only"
                          : value === APPLICABILITY_BOTH
                            ? "Both commercial sides"
                            : "Internal only",
            }));
        },
        [metadata.data, selectedAllocation],
    );

    const canRecord = hasPermission(auth, vehicleRentalPermissions.usageRecord);
    const canApprove = hasPermission(auth, vehicleRentalPermissions.usageApprove);

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
        if (selectedAllocation === null) return;
        setSaving(true);
        setActionError(null);
        try {
            await createRentalUsageLog(selectedAllocation.id, selectedAllocation.row_version, {
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
                events: events.map(({ key: _key, ...row }) => ({
                    ...row,
                    occurred_at: row.occurred_at || null,
                    reference_number: row.reference_number || null,
                    remarks: row.remarks || null,
                })),
                expected_source_allocation_version:
                    selectedAllocation.source_allocation?.row_version ?? null,
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
                        Review usage and facts
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
                </div>
            ),
        },
    ];

    return (
        <RentalPage>
            <ContentHeader
                title="Daily running chart"
                description="Record physical vehicle usage once, then approve customer billable and vehicle-owner payable facts independently."
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
                    label="Active vehicle allocation"
                    value={allocation}
                    onChange={(value) => {
                        setAllocation(value);
                        setAllocationDetails(null);
                        setAllocationIdToLoad(value?.id ?? null);
                        setSearchParams(value ? { allocation_id: String(value.id) } : {});
                        setForm(emptyForm());
                        setEvents([]);
                        setSelectedUsageId(null);
                    }}
                    agreementKind={AGREEMENT_KIND_CUSTOMER_RENTAL}
                    status="active"
                    required={canRecord}
                />
                <p className="mt-2 text-sm text-slate-500">
                    The selected allocation controls the vehicle, lessee agreement,
                    owner agreement and valid driver assignments.
                </p>
            </Panel>

            {canRecord && selectedAllocation !== null && (
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
                                    setForm({
                                        ...form,
                                        usage_date: event.target.value,
                                    })
                                }
                            />
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
                            <Select
                                label="Valid driver assignment"
                                required={requiresDriverAssignment}
                                placeholder={
                                    requiresDriverAssignment
                                        ? "Select company driver"
                                        : "No company driver"
                                }
                                hint={
                                    requiresDriverAssignment
                                        ? "Required for with-driver agreements."
                                        : undefined
                                }
                                value={form.driver_assignment_id}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        driver_assignment_id: event.target.value,
                                    })
                                }
                                options={driverAssignmentOptions}
                            />
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
                                                placeholder="Select event type"
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
                            <Button type="submit" loading={saving} disabled={selectedAllocation === null}>
                                Save physical usage
                            </Button>
                        </div>
                    </Panel>
                </form>
            )}

            <div className="mt-5">
                {loadingAllocation ? (
                    <LoadingState />
                ) : selectedAllocation === null ? (
                    <Panel>
                        <p className="text-sm text-slate-500">
                            Select an active allocation to view its running chart.
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
                        {canApprove && selectedUsage.status === "submitted" && (
                            <div className="mt-4 grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                                <Input
                                    label="Physical approval or rejection note"
                                    value={physicalTransitionReason}
                                    onChange={(event) =>
                                        setPhysicalTransitionReason(event.target.value)
                                    }
                                />
                                <div className="flex flex-wrap justify-end gap-2">
                                    <Button
                                        type="button"
                                        onClick={() =>
                                            void transition(selectedUsage, "approved")
                                        }
                                    >
                                        Approve physical usage
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() =>
                                            void transition(selectedUsage, "rejected")
                                        }
                                    >
                                        Reject physical usage
                                    </Button>
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
