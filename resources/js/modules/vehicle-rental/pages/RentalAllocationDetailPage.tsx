import { useState } from "react";
import { useParams } from "react-router-dom";
import { Button, LinkButton } from "@/shared/components/Button";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DetailGrid } from "@/shared/components/DetailGrid";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { useApi } from "@/shared/hooks/useApi";
import { formatDate } from "@/shared/utils/formatDate";
import { readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import { cancelRentalAllocation, getRentalAllocation } from "../vehicleRentalApi";

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
