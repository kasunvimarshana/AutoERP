import { useState, type FormEvent } from "react";
import { useParams } from "react-router-dom";
import { searchEmployees } from "@/modules/hr/hrApi";
import type { EmployeeSummary } from "@/modules/hr/hrTypes";
import { Button, LinkButton } from "@/shared/components/Button";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DetailGrid } from "@/shared/components/DetailGrid";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { GenericLookupSelect } from "@/shared/components/GenericLookupSelect";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { useApi } from "@/shared/hooks/useApi";
import { formatDate } from "@/shared/utils/formatDate";
import { readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import {
    assignRentalDriver,
    cancelRentalAllocation,
    getRentalAllocation,
} from "../vehicleRentalApi";
import type { RentalAllocation } from "../vehicleRentalTypes";

const DRIVER_ASSIGNABLE_STATUSES = new Set(["planned", "active"]);
const DRIVER_ROLE_OPTIONS = [
    { value: "primary", label: "Primary" },
    { value: "relief", label: "Relief" },
];
const PRIMARY_ASSIGNMENT_OPTIONS = [
    { value: "yes", label: "Yes" },
    { value: "no", label: "No" },
];

interface DriverForm {
    assignmentRole: string;
    assignedFrom: string;
    assignedTo: string;
    primary: string;
    remarks: string;
}

function ownershipLabel(value: unknown): string {
    if (!value || typeof value !== "object") return "-";
    const ownership = value as Record<string, unknown>;

    return [
        ownership.owner_name_snapshot,
        typeof ownership.ownership_type === "string"
            ? ownership.ownership_type.replaceAll("_", " ")
            : null,
    ].filter(Boolean).join(" - ") || "-";
}

function toDateTimeLocal(value?: string | null): string {
    if (!value) return "";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "";

    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);

    return local.toISOString().slice(0, 16);
}

function toIsoDateTime(value: string): string {
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : date.toISOString();
}

function employeeLabel(employee: EmployeeSummary): string {
    return [
        employee.employee_number,
        employee.display_name || employee.name,
    ].filter(Boolean).join(" - ");
}

function driverFormDefaults(
    allocatedFrom?: string | null,
    allocatedTo?: string | null,
): DriverForm {
    return {
        assignmentRole: "primary",
        assignedFrom: toDateTimeLocal(allocatedFrom),
        assignedTo: toDateTimeLocal(allocatedTo),
        primary: "yes",
        remarks: "",
    };
}

function DriverAssignmentForm({
    row,
    onAssigned,
    onActionError,
}: {
    row: RentalAllocation;
    onAssigned: () => void;
    onActionError: (error: ApiError | null) => void;
}) {
    const driverDefaults = driverFormDefaults(row.allocated_from, row.allocated_to);
    const [selectedDriver, setSelectedDriver] = useState<EmployeeSummary | null>(null);
    const [assigningDriver, setAssigningDriver] = useState(false);
    const [driverForm, setDriverForm] = useState<DriverForm>(driverDefaults);

    const assignDriver = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!selectedDriver) return;

        setAssigningDriver(true);
        onActionError(null);
        try {
            await assignRentalDriver(row.id, row.row_version, {
                employee_id: selectedDriver.id,
                assignment_role: driverForm.assignmentRole,
                assigned_from: toIsoDateTime(driverForm.assignedFrom),
                assigned_to: driverForm.assignedTo
                    ? toIsoDateTime(driverForm.assignedTo)
                    : null,
                is_primary: driverForm.primary === "yes",
                remarks: driverForm.remarks || null,
            });
            setSelectedDriver(null);
            setDriverForm(driverDefaults);
            onAssigned();
        } catch (error) {
            onActionError(toApiError(error));
        } finally {
            setAssigningDriver(false);
        }
    };

    return (
        <form
            className="space-y-3 border-t border-slate-200 pt-4"
            onSubmit={assignDriver}
        >
            <GenericLookupSelect<EmployeeSummary>
                label="Driver"
                value={selectedDriver}
                onChange={setSelectedDriver}
                search={searchEmployees}
                formatLabel={employeeLabel}
                placeholder="Search active employee"
                required
            />
            <div className="grid gap-3 sm:grid-cols-2">
                <Select
                    label="Role"
                    value={driverForm.assignmentRole}
                    options={DRIVER_ROLE_OPTIONS}
                    onChange={(event) => setDriverForm({
                        ...driverForm,
                        assignmentRole: event.target.value,
                    })}
                    required
                />
                <Select
                    label="Primary"
                    value={driverForm.primary}
                    options={PRIMARY_ASSIGNMENT_OPTIONS}
                    onChange={(event) => setDriverForm({
                        ...driverForm,
                        primary: event.target.value,
                    })}
                    required
                />
                <Input
                    label="From"
                    type="datetime-local"
                    value={driverForm.assignedFrom}
                    onChange={(event) => setDriverForm({
                        ...driverForm,
                        assignedFrom: event.target.value,
                    })}
                    required
                />
                <Input
                    label="To"
                    type="datetime-local"
                    value={driverForm.assignedTo}
                    onChange={(event) => setDriverForm({
                        ...driverForm,
                        assignedTo: event.target.value,
                    })}
                />
            </div>
            <Input
                label="Remarks"
                value={driverForm.remarks}
                onChange={(event) => setDriverForm({
                    ...driverForm,
                    remarks: event.target.value,
                })}
            />
            <div className="flex justify-end">
                <Button
                    type="submit"
                    loading={assigningDriver}
                    disabled={!selectedDriver}
                >
                    Assign driver
                </Button>
            </div>
        </form>
    );
}

export default function RentalAllocationDetailPage() {
    const id = Number(useParams().id);
    const [refresh, setRefresh] = useState(0);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [cancelling, setCancelling] = useState(false);
    const result = useApi((signal) => getRentalAllocation(id, signal), [id, refresh]);

    if (result.loading)
        return (
            <RentalPage>
                <LoadingState />
            </RentalPage>
        );
    if (!result.data)
        return (
            <RentalPage>
                <ErrorAlert error={result.error} />
            </RentalPage>
        );
    const row = result.data;
    const canAssignDriver = DRIVER_ASSIGNABLE_STATUSES.has(row.status);
    const cancel = async () => {
        if (!window.confirm("Cancel this planned allocation?")) return;

        setCancelling(true);
        setActionError(null);
        try {
            await cancelRentalAllocation(row.id, row.row_version);
            setRefresh((value) => value + 1);
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setCancelling(false);
        }
    };

    return (
        <RentalPage>
            <ContentHeader
                title={row.allocation_number}
                description="Vehicle, driver and custody context for this allocation."
                actions={
                    <>
                        <LinkButton
                            variant="secondary"
                            to={`/vehicle-rental/custody?allocation_id=${id}`}
                        >
                            Custody
                        </LinkButton>
                        <LinkButton
                            variant="secondary"
                            to={`/vehicle-rental/running-chart?allocation_id=${id}`}
                        >
                            Running chart
                        </LinkButton>
                        {row.status === "active" && (
                            <LinkButton
                                variant="secondary"
                                to={`/vehicle-rental/allocations/${id}/replacement`}
                            >
                                Replace vehicle
                            </LinkButton>
                        )}
                        {row.status === "planned" && (
                            <Button
                                variant="secondary"
                                loading={cancelling}
                                onClick={cancel}
                            >
                                Cancel allocation
                            </Button>
                        )}
                    </>
                }
            />
            <ErrorAlert error={actionError ?? result.error} />
            <div className="grid gap-5 lg:grid-cols-2">
                <Panel title="Allocation">
                    <DetailGrid
                        items={[
                            {
                                label: "Agreement",
                                value: readableRelation(row.agreement),
                            },
                            {
                                label: "Vehicle",
                                value: readableRelation(row.vehicle),
                            },
                            {
                                label: "Source",
                                value: row.vehicle_source_type.replaceAll(
                                    "_",
                                    " ",
                                ),
                            },
                            {
                                label: "Ownership",
                                value: ownershipLabel(row.ownership),
                            },
                            {
                                label: "Source allocation",
                                value: readableRelation(row.source_allocation),
                            },
                            {
                                label: "Finance agreement",
                                value: readableRelation(row.finance_agreement),
                            },
                            {
                                label: "Status",
                                value: <StatusBadge status={row.status} />,
                            },
                            {
                                label: "From",
                                value: formatDate(row.allocated_from),
                            },
                            {
                                label: "To",
                                value: formatDate(row.allocated_to),
                            },
                            {
                                label: "Actual return",
                                value: formatDate(row.actual_returned_at),
                            },
                            {
                                label: "Start odometer",
                                value: row.start_odometer ?? "-",
                            },
                            {
                                label: "End odometer",
                                value: row.end_odometer ?? "-",
                            },
                        ]}
                    />
                </Panel>
                <Panel title="Drivers">
                    <div className="space-y-3">
                        {(row.drivers ?? []).map((driver) => (
                            <div
                                key={driver.id}
                                className="rounded-lg border border-slate-200 p-3"
                            >
                                <div className="font-medium">
                                    {readableRelation(driver.employee)}
                                </div>
                                <div className="text-sm text-slate-500">
                                    {driver.assignment_role} -{" "}
                                    {formatDate(driver.assigned_from)} -{" "}
                                    {formatDate(driver.assigned_to)}
                                </div>
                            </div>
                        ))}
                        {(row.drivers ?? []).length === 0 && (
                            <p className="text-sm text-slate-500">
                                No driver is assigned.
                            </p>
                        )}
                        {canAssignDriver && (
                            <DriverAssignmentForm
                                key={`${row.id}-${row.row_version}`}
                                row={row}
                                onAssigned={() => setRefresh((value) => value + 1)}
                                onActionError={setActionError}
                            />
                        )}
                    </div>
                </Panel>
            </div>
            <Panel title="Custody-controlled lifecycle" className="mt-5">
                <p className="text-sm text-slate-600">
                    Activate and close this allocation by confirming the
                    matching handover or return event. This keeps vehicle
                    custody, odometer and availability history consistent.
                </p>
            </Panel>
        </RentalPage>
    );
}
