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
import {
    getRentalAgreement,
    transitionRentalAgreement,
} from "../vehicleRentalApi";

export default function RentalAgreementDetailPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const result = useApi((signal) => getRentalAgreement(id, signal), [id]);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

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
                    <div className="space-y-2">
                        {row.allocations.map((allocation) => (
                            <Link
                                key={allocation.id}
                                to={`/vehicle-rental/allocations/${allocation.id}`}
                                className="flex justify-between rounded-lg border p-3 hover:bg-slate-50"
                            >
                                <span>
                                    {allocation.allocation_number} -{" "}
                                    {readableRelation(allocation.vehicle)}
                                </span>
                                <StatusBadge status={allocation.status} />
                            </Link>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-slate-500">
                        No vehicle allocation yet.
                    </p>
                )}
            </Panel>
        </RentalPage>
    );
}
