import { useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button, LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DataTable, type DataColumn } from "@/shared/components/DataTable";
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
    RENTAL_AGREEMENT_KIND,
    agreementDetailPath,
    rentalAgreementFinancialSide,
    rentalAgreementKindLabel,
    rentalAgreementPartyLabel,
    type RentalAgreementFinancialSide,
    type RentalAgreementPageMode,
} from "../rentalAgreementPresentation";
import {
    getRentalAgreement,
    listRentalUsageLogs,
    transitionRentalAgreement,
} from "../vehicleRentalApi";
import type { RentalUsageLog } from "../vehicleRentalTypes";

interface RentalAgreementDetailPageProps {
    mode?: RentalAgreementPageMode;
}

export default function RentalAgreementDetailPage({
    mode = "standard",
}: RentalAgreementDetailPageProps) {
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
    const expectedAgreementKind =
        mode === "lessee"
            ? RENTAL_AGREEMENT_KIND.customerRental
            : mode === "lessor"
                ? RENTAL_AGREEMENT_KIND.ownerSupply
                : null;
    const isExpectedAgreement =
        expectedAgreementKind === null ||
        row.agreement_kind === expectedAgreementKind;
    const correctAgreementMode = agreementModeForKind(row.agreement_kind);
    const correctAgreementLabel =
        correctAgreementMode === "lessee" ? "lessee agreement" : "lessor agreement";

    return (
        <RentalPage>
            <ContentHeader
                title={row.agreement_number}
                description={
                    row.agreement_kind === RENTAL_AGREEMENT_KIND.customerRental
                        ? "Lessee agreement, immutable billable rates, allocations and deposit obligation."
                        : row.agreement_kind === RENTAL_AGREEMENT_KIND.ownerSupply
                        ? "Lessor agreement, immutable payable rates and supplied vehicle allocations."
                        : "Agreement, immutable rate versions, allocations and deposit obligation."
                }
                actions={
                    isExpectedAgreement ? (
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
                    ) : (
                        <LinkButton
                            variant="secondary"
                            to={agreementDetailPath(correctAgreementMode, row.id)}
                        >
                            Open {correctAgreementLabel}
                        </LinkButton>
                    )
                }
            />
            {!isExpectedAgreement && (
                <div
                    className="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                    role="alert"
                >
                    {mode === "lessee"
                        ? "This record is not a lessee agreement."
                        : "This record is not a lessor agreement."}
                </div>
            )}
            {!isExpectedAgreement ? null : (
                <>
                    <ErrorAlert error={actionError ?? result.error} />
                    <div className="grid gap-5 lg:grid-cols-2">
                        <Panel title="Agreement">
                            <DetailGrid
                                items={[
                                    {
                                        label: "Kind",
                                        value: rentalAgreementKindLabel(
                                            row.agreement_kind,
                                        ),
                                    },
                                    {
                                        label: rentalAgreementPartyLabel(
                                            row.agreement_kind,
                                        ),
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
                    <AgreementRunningChartPanel
                        agreementId={row.id}
                        side={rentalAgreementFinancialSide(row.agreement_kind)}
                    />
                </>
            )}
        </RentalPage>
    );
}

function agreementModeForKind(kind: string): Exclude<RentalAgreementPageMode, "standard"> {
    return kind === RENTAL_AGREEMENT_KIND.ownerSupply ? "lessor" : "lessee";
}

function AgreementRunningChartPanel({
    agreementId,
    side,
}: {
    agreementId: number;
    side: RentalAgreementFinancialSide;
}) {
    const result = useApi(
        (signal) =>
            listRentalUsageLogs(
                {
                    agreement_id: agreementId,
                    financial_side: side,
                    per_page: 25,
                },
                signal,
            ),
        [agreementId, side],
    );
    const title =
        side === "revenue"
            ? "Lessee running chart"
            : "Lessor running chart";
    const factLabel =
        side === "revenue" ? "Billable facts" : "Payable facts";
    const commercialDistanceLabel =
        side === "revenue" ? "Billable KM" : "Payable KM";
    const columns: DataColumn<RentalUsageLog>[] = [
        {
            key: "usage",
            header: "Running chart",
            render: (row) =>
                row.allocation ? (
                    <Link
                        className="font-semibold text-blue-700 hover:underline"
                        to={`/vehicle-rental/running-chart?allocation_id=${row.allocation.id}`}
                    >
                        {row.usage_number}
                    </Link>
                ) : (
                    row.usage_number
                ),
        },
        {
            key: "date",
            header: "Date",
            render: (row) => formatDate(row.usage_date),
        },
        {
            key: "allocation",
            header: "Allocation",
            render: (row) =>
                readableRelation(
                    usageContextForSide(row, side)?.allocation ??
                        row.allocation,
                ),
        },
        {
            key: "physical",
            header: "Physical KM",
            render: (row) =>
                `${row.distance_km} total / ${row.net_operational_distance_km} net`,
        },
        {
            key: "commercial",
            header: commercialDistanceLabel,
            render: (row) =>
                usageFactForSide(row, side)?.commercial_distance_km ?? "-",
        },
        {
            key: "physical_status",
            header: "Physical",
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: "fact_status",
            header: factLabel,
            render: (row) => (
                <StatusBadge
                    status={usageFactForSide(row, side)?.status ?? "missing"}
                />
            ),
        },
    ];

    return (
        <Panel title={title} className="mt-5">
            <ErrorAlert error={result.error} />
            {result.loading ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={result.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    emptyMessage="No running-chart data has been recorded for this agreement."
                />
            )}
        </Panel>
    );
}

function usageFactForSide(
    usage: RentalUsageLog,
    side: RentalAgreementFinancialSide,
) {
    return usageContextForSide(usage, side)?.usage_fact ?? null;
}

function usageContextForSide(
    usage: RentalUsageLog,
    side: RentalAgreementFinancialSide,
) {
    return usage.contexts.find((context) => context.financial_side === side);
}
