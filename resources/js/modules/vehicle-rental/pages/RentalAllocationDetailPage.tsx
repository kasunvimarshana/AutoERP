import { useState, type FormEvent } from "react";
import { Link, useParams } from "react-router-dom";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button, LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DetailGrid } from "@/shared/components/DetailGrid";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Modal } from "@/shared/components/Modal";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { Textarea } from "@/shared/components/Textarea";
import { useApi } from "@/shared/hooks/useApi";
import { formatDate } from "@/shared/utils/formatDate";
import { readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import { RentalAssignDriverModal } from "../components/RentalAssignDriverModal";
import { getRentalAllocation } from "../vehicleRentalApi";

export default function RentalAllocationDetailPage() {
    const id = Number(useParams().id);
    const result = useApi((signal) => getRentalAllocation(id, signal), [id]);

    // Driver assignment modal state
    const [assignModalOpen, setAssignModalOpen] = useState(false);
    const [assignRole, setAssignRole] = useState<"primary" | "relief">("primary");
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
                            variant={row.status === 'planned' ? 'primary' : 'secondary'}
                            to={`/vehicle-rental/custody?allocation_id=${id}`}
                        >
                            {row.status === 'planned' ? 'Handover vehicle' : row.status === 'active' ? 'Return vehicle' : 'Custody'}
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

            {row.replacement_allocation && (
                <div className="mb-5 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <div className="font-semibold flex items-center gap-2">
                            <span>🔁 Vehicle Replaced</span>
                            {row.replacement_record?.replacement_number && (
                                <span className="font-mono text-xs px-2 py-0.5 rounded bg-amber-200 text-amber-900 font-bold">
                                    {row.replacement_record.replacement_number}
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-amber-800 mt-1">
                            This vehicle was replaced on {formatDate(row.replacement_record?.replacement_at ?? row.actual_returned_at)}
                            {row.replacement_record?.reason_code && ` (${row.replacement_record.reason_code.replaceAll('_', ' ')})`}
                            {row.replacement_record?.reason ? `: "${row.replacement_record.reason}"` : '.'}
                        </p>
                        {row.replacement_allocation.vehicle_source_type && (
                            <p className="text-xs text-amber-900/80 mt-1">
                                Replacement source: <span className="font-medium capitalize">{row.replacement_allocation.vehicle_source_type.replaceAll('_', ' ')}</span>
                            </p>
                        )}
                    </div>
                    <LinkButton
                        to={`/vehicle-rental/allocations/${row.replacement_allocation.id}`}
                        className="bg-amber-700 hover:bg-amber-800 text-white font-medium whitespace-nowrap"
                    >
                        View Replacement ({readableRelation(row.replacement_allocation.vehicle) || row.replacement_allocation.allocation_number}) →
                    </LinkButton>
                </div>
            )}

            {row.replaces_allocation && (
                <div className="mb-5 rounded-xl border border-blue-300 bg-blue-50 p-4 text-blue-950 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <div className="font-semibold flex items-center gap-2">
                            <span>🔁 Active Replacement Vehicle</span>
                            {row.replacement_origin?.replacement_number && (
                                <span className="font-mono text-xs px-2 py-0.5 rounded bg-blue-200 text-blue-900 font-bold">
                                    {row.replacement_origin.replacement_number}
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-blue-800 mt-1">
                            This allocation is serving as a replacement for original allocation {row.replaces_allocation.allocation_number}
                            {row.replaces_allocation.vehicle && ` (${readableRelation(row.replaces_allocation.vehicle)})`}
                            {row.replacement_origin?.replacement_at && ` since ${formatDate(row.replacement_origin.replacement_at)}`}
                            {row.replacement_origin?.reason_code && ` (${row.replacement_origin.reason_code.replaceAll('_', ' ')})`}
                            {row.replacement_origin?.reason ? `: "${row.replacement_origin.reason}"` : '.'}
                        </p>
                        {row.replaces_allocation.vehicle_source_type && (
                            <p className="text-xs text-blue-900/80 mt-1">
                                Original vehicle source: <span className="font-medium capitalize">{row.replaces_allocation.vehicle_source_type.replaceAll('_', ' ')}</span>
                            </p>
                        )}
                    </div>
                    <LinkButton
                        to={`/vehicle-rental/allocations/${row.replaces_allocation.id}`}
                        variant="secondary"
                        className="whitespace-nowrap"
                    >
                        ← View Original Allocation ({readableRelation(row.replaces_allocation.vehicle) || row.replaces_allocation.allocation_number})
                    </LinkButton>
                </div>
            )}
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
                            ...(row.vehicle_source_type === "owner_supplied"
                                ? [
                                      {
                                          label: "Vehicle owner",
                                          value:
                                              row.source_allocation?.agreement?.supplier?.name ??
                                              row.source_allocation?.allocation_number ??
                                              "-",
                                      },
                                      {
                                          label: "Owner agreement",
                                          value:
                                              row.source_allocation?.agreement?.agreement_number ??
                                              "-",
                                      },
                                  ]
                                : []),
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
                <Panel>
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 className="font-semibold text-slate-900">Drivers</h2>
                            <p className="text-xs text-slate-500">
                                Authorized drivers assigned to operate this allocated vehicle.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Link
                                to="/hr/drivers"
                                className="text-xs font-medium text-slate-500 hover:text-slate-800 hover:underline"
                            >
                                HR Drivers &rarr;
                            </Link>
                            {row.status !== "completed" && row.status !== "returned" && (
                                <Button
                                    variant="primary"
                                    className="text-xs px-2.5 py-1"
                                    onClick={() => {
                                        setAssignRole("primary");
                                        setAssignModalOpen(true);
                                    }}
                                >
                                    + Assign Driver
                                </Button>
                            )}
                        </div>
                    </div>
                    <div className="space-y-3">
                        {(row.drivers ?? []).map((driver) => (
                            <div
                                key={driver.id}
                                className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2"
                            >
                                <div>
                                    <div className="font-semibold text-slate-900 flex items-center gap-2">
                                        <span>{readableRelation(driver.employee)}</span>
                                        {driver.is_primary && (
                                            <span className="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-800 uppercase tracking-wider">
                                                Primary
                                            </span>
                                        )}
                                    </div>
                                    <div className="text-xs text-slate-500 mt-1">
                                        Role: <strong className="capitalize">{driver.assignment_role}</strong> &bull; From {formatDate(driver.assigned_from)} to {formatDate(driver.assigned_to)}
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <StatusBadge status={driver.status} />
                                    {row.status !== "completed" && row.status !== "returned" && (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            className="text-xs px-2 py-0.5"
                                            onClick={() => {
                                                setAssignRole("primary");
                                                setAssignModalOpen(true);
                                            }}
                                        >
                                            Change Driver
                                        </Button>
                                    )}
                                </div>
                            </div>
                        ))}
                        {(row.drivers ?? []).length === 0 && (
                            <div className="rounded-lg border border-dashed border-slate-300 p-6 text-center">
                                <p className="text-sm font-medium text-slate-700">
                                    No driver is assigned to this allocation.
                                </p>
                                <p className="text-xs text-slate-500 mt-1">
                                    Assign an authorized company driver from the HR registry to operate this vehicle.
                                </p>
                                {row.status !== "completed" && row.status !== "returned" && (
                                    <div className="mt-4">
                                        <Button
                                            variant="primary"
                                            onClick={() => {
                                                setAssignRole("primary");
                                                setAssignModalOpen(true);
                                            }}
                                        >
                                            + Assign Driver
                                        </Button>
                                    </div>
                                )}
                            </div>
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

            <RentalAssignDriverModal
                open={assignModalOpen}
                allocationId={id}
                allocationNumber={row.allocation_number}
                allocatedFrom={row.allocated_from}
                allocatedTo={row.allocated_to}
                initialRole={assignRole}
                onClose={() => setAssignModalOpen(false)}
                onSuccess={() => void result.reload()}
            />
        </RentalPage>
    );
}
