import { useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button, LinkButton } from "@/shared/components/Button";
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
import { RentalAssignDriverModal } from "../components/RentalAssignDriverModal";
import {
    getRentalAgreement,
    transitionRentalAgreement,
} from "../vehicleRentalApi";
import type { RentalAllocation } from "../vehicleRentalTypes";

export default function RentalAgreementDetailPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const result = useApi((signal) => getRentalAgreement(id, signal), [id]);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [selectedAllocationForDriver, setSelectedAllocationForDriver] = useState<RentalAllocation | null>(null);
    const [driverAssignRole, setDriverAssignRole] = useState<"primary" | "relief">("primary");

    const transition = async (status: string) => {
        if (!result.data) return;

        setBusy(true);
        setActionError(null);
        try {
            await transitionRentalAgreement(
                id,
                result.data.row_version,
                status,
            );
            navigate(0);
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    };

    if (result.loading) {
        return (
            <RentalPage>
                <LoadingState />
            </RentalPage>
        );
    }
    if (!result.data) {
        return (
            <RentalPage>
                <ErrorAlert error={result.error} />
            </RentalPage>
        );
    }

    const row = result.data;

    return (
        <RentalPage>
            <ContentHeader
                title={row.agreement_number}
                description="Agreement, immutable rate versions, allocations and deposit obligation."
                actions={
                    <>
                        <LinkButton
                            variant="secondary"
                            to={`/vehicle-rental/allocations?agreement_id=${id}`}
                        >
                            Allocations
                        </LinkButton>
                        {row.status === "draft" && (
                            <Button
                                loading={busy}
                                onClick={() => void transition("active")}
                            >
                                Activate
                            </Button>
                        )}
                        {row.status === "active" && (
                            <Button
                                variant="danger"
                                loading={busy}
                                onClick={() => void transition("terminated")}
                            >
                                Terminate
                            </Button>
                        )}
                    </>
                }
            />
            <ErrorAlert error={actionError ?? result.error} />
            <div className="grid gap-5 lg:grid-cols-2">
                <Panel title="Agreement">
                    <DetailGrid
                        items={[
                            {
                                label: "Kind",
                                value: row.agreement_kind.replaceAll("_", " "),
                            },
                            {
                                label: "Party",
                                value: readableRelation(
                                    row.customer ?? row.supplier,
                                ),
                            },
                            {
                                label: "Status",
                                value: <StatusBadge status={row.status} />,
                            },
                            {
                                label: "Agreement date",
                                value: formatDate(row.agreement_date),
                            },
                            {
                                label: "Start",
                                value: formatDate(row.starts_at),
                            },
                            { label: "End", value: formatDate(row.ends_at) },
                            {
                                label: "Mode",
                                value: row.rental_mode.replaceAll("_", " "),
                            },
                            {
                                label: "Billing",
                                value: `${row.billing_cycle} / ${row.billing_basis}`,
                            },
                        ]}
                    />
                </Panel>
                <Panel title="Active rate version">
                    {row.active_rate_version ? (
                        <>
                            <DetailGrid
                                items={[
                                    {
                                        label: "Version",
                                        value: String(
                                            row.active_rate_version
                                                .version_number,
                                        ),
                                    },
                                    {
                                        label: "Effective",
                                        value: formatDate(
                                            row.active_rate_version
                                                .effective_from,
                                        ),
                                    },
                                    {
                                        label: "Excess KM method",
                                        value: row.active_rate_version.excess_km_method.replaceAll(
                                            "_",
                                            " ",
                                        ),
                                    },
                                    {
                                        label: "Included KM",
                                        value: row.active_rate_version
                                            .included_km,
                                    },
                                ]}
                            />
                            <div className="mt-4 space-y-2">
                                {row.active_rate_version.components.map(
                                    (component) => (
                                        <div
                                            key={
                                                component.id ??
                                                component.component_code
                                            }
                                            className="flex justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm"
                                        >
                                            <span>
                                                {component.component_code.replaceAll(
                                                    "_",
                                                    " ",
                                                )}
                                            </span>
                                            <strong>
                                                {component.rate} /{" "}
                                                {component.unit}
                                            </strong>
                                        </div>
                                    ),
                                )}
                            </div>
                        </>
                    ) : (
                        <p className="text-sm text-slate-500">
                            No active rate version.
                        </p>
                    )}
                </Panel>
            </div>
            <Panel title="Vehicle allocations" className="mt-5">
                {row.allocations?.length ? (
                    <div className="space-y-3">
                        {row.allocations.map((allocation) => (
                            <div
                                key={allocation.id}
                                className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 rounded-lg border p-4 bg-white shadow-sm"
                            >
                                <div className="space-y-2">
                                    <div className="font-semibold text-slate-900 flex items-center gap-2">
                                        <Link
                                            to={`/vehicle-rental/allocations/${allocation.id}`}
                                            className="hover:underline text-blue-600"
                                        >
                                            {allocation.allocation_number}
                                        </Link>
                                        <span className="text-slate-400">-</span>
                                        <span>{readableRelation(allocation.vehicle)}</span>
                                    </div>
                                    <div className="text-xs text-slate-500">
                                        Period: {allocation.allocated_from?.slice(0, 10)} -{" "}
                                        {allocation.allocated_to?.slice(0, 10) ?? "open"}
                                    </div>
                                    {row.rental_mode === "with_driver" && (
                                        <div className="pt-1">
                                            {allocation.drivers && allocation.drivers.length > 0 ? (
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="text-xs font-semibold text-slate-700">Driver:</span>
                                                    {allocation.drivers.map((d) => (
                                                        <span
                                                            key={d.id}
                                                            className="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700"
                                                        >
                                                            <span>{readableRelation(d.employee)}</span>
                                                            <span className="rounded bg-blue-100 px-1 py-0.2 text-[10px] font-bold uppercase tracking-wider text-blue-800">
                                                                {d.assignment_role}
                                                            </span>
                                                        </span>
                                                    ))}
                                                    <Button
                                                        type="button"
                                                        variant="secondary"
                                                        className="text-xs px-2 py-0.5"
                                                        onClick={() => {
                                                            setSelectedAllocationForDriver(allocation);
                                                            setDriverAssignRole("primary");
                                                        }}
                                                    >
                                                        Change Driver
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="secondary"
                                                        className="text-xs px-2 py-0.5"
                                                        onClick={() => {
                                                            setSelectedAllocationForDriver(allocation);
                                                            setDriverAssignRole("relief");
                                                        }}
                                                    >
                                                        + Assign Relief
                                                    </Button>
                                                </div>
                                            ) : (
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="rounded border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">
                                                        No driver assigned
                                                    </span>
                                                    <Button
                                                        type="button"
                                                        variant="primary"
                                                        className="text-xs px-2.5 py-1"
                                                        onClick={() => {
                                                            setSelectedAllocationForDriver(allocation);
                                                            setDriverAssignRole("primary");
                                                        }}
                                                    >
                                                        + Assign Driver
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                                <div className="flex items-center gap-2 shrink-0">
                                    <StatusBadge status={allocation.status} />
                                    <LinkButton
                                        to={`/vehicle-rental/allocations/${allocation.id}`}
                                        variant="secondary"
                                        className="text-xs"
                                    >
                                        View &rarr;
                                    </LinkButton>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-slate-300 p-6 text-center">
                        <p className="text-sm font-medium text-slate-700">
                            No vehicle allocation yet for this agreement.
                        </p>
                        <p className="mt-1 text-xs text-slate-500">
                            To assign a vehicle and driver to this agreement, create a vehicle allocation.
                        </p>
                        <div className="mt-4">
                            <LinkButton
                                to={`/vehicle-rental/allocations?agreement_id=${id}`}
                                variant="primary"
                            >
                                + Allocate Vehicle &amp; Assign Driver
                            </LinkButton>
                        </div>
                    </div>
                )}
            </Panel>

            <RentalAssignDriverModal
                open={Boolean(selectedAllocationForDriver)}
                allocationId={selectedAllocationForDriver?.id ?? null}
                allocationNumber={selectedAllocationForDriver?.allocation_number}
                allocatedFrom={selectedAllocationForDriver?.allocated_from}
                allocatedTo={selectedAllocationForDriver?.allocated_to}
                initialRole={driverAssignRole}
                onClose={() => setSelectedAllocationForDriver(null)}
                onSuccess={() => {
                    setSelectedAllocationForDriver(null);
                    void result.reload();
                }}
            />
        </RentalPage>
    );
}
