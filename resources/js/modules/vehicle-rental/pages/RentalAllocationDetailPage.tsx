import { useParams } from "react-router-dom";
import { LinkButton } from "@/shared/components/Button";
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
import { getRentalAllocation } from "../vehicleRentalApi";

export default function RentalAllocationDetailPage() {
    const id = Number(useParams().id);
    const result = useApi((signal) => getRentalAllocation(id, signal), [id]);
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
                    </>
                }
            />
            <ErrorAlert error={result.error} />
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
                                    {driver.assignment_role} ·{" "}
                                    {formatDate(driver.assigned_from)} –{" "}
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
