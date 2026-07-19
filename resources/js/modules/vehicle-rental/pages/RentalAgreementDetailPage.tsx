import { useState } from "react";
import {
    Link,
    useNavigate,
    useParams,
    useSearchParams,
} from "react-router-dom";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button, LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { useConfirmDialog } from "@/shared/components/ConfirmDialog";
import { DataTable, type DataColumn } from "@/shared/components/DataTable";
import { DetailGrid } from "@/shared/components/DetailGrid";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { useApi } from "@/shared/hooks/useApi";
import { formatDate } from "@/shared/utils/formatDate";
import { humanize, readableRelation } from "@/shared/utils/object";
import { RentalAgreementPrintDocument } from "../components/RentalAgreementPrintDocument";
import { RentalPage } from "../components/RentalPage";
import {
    RENTAL_AGREEMENT_KIND,
    agreementDetailPath,
    agreementEditPath,
    agreementListPath,
    rentalAgreementFinancialSide,
    rentalAgreementKindLabel,
    rentalAgreementPartyLabel,
    type RentalAgreementFinancialSide,
    type RentalAgreementPageMode,
} from "../rentalAgreementPresentation";
import { rentalAgreementRateLabel } from "../rentalAgreementUi";
import {
    deleteRentalAgreement,
    getRentalAgreement,
    listRentalUsageLogs,
    transitionRentalAgreement,
} from "../vehicleRentalApi";
import type {
    RentalAgreement,
    RentalRateVersion,
    RentalUsageLog,
} from "../vehicleRentalTypes";

const AGREEMENT_STATUS = {
    draft: "draft",
    active: "active",
} as const;
const OPEN_ALLOCATION_STATUSES = new Set(["planned", "active"]);
const RUNNING_CHART_PAGE_SIZE = 25;

interface RentalAgreementDetailPageProps {
    mode?: RentalAgreementPageMode;
}

export default function RentalAgreementDetailPage({
    mode = "standard",
}: RentalAgreementDetailPageProps) {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const result = useApi((signal) => getRentalAgreement(id, signal), [id]);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();

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

    const destroyDraft = async () => {
        if (!result.data) return;

        const confirmed = await confirm({
            title: "Delete draft agreement?",
            message:
                "Only this draft agreement record is archived. Activated agreement history must be cancelled, completed, or terminated.",
            confirmLabel: "Delete draft",
            danger: true,
        });
        if (!confirmed) return;

        setBusy(true);
        setActionError(null);
        try {
            await deleteRentalAgreement(id, result.data.row_version);
            navigate(agreementListPath(mode), { replace: true });
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    };

    const confirmTransition = async (status: "active" | "terminated") => {
        if (!result.data) return;
        const row = result.data;
        if (status === "active" && activationIssues(row).length > 0) return;

        const confirmed = await confirm(
            status === "active"
                ? {
                      title: "Activate agreement?",
                      message:
                          "Review the party, period, billing policy, rates, units, Tax treatment, deposit and printable terms shown on this page. Activation freezes them into the immutable agreement snapshot.",
                      confirmLabel: "Activate agreement",
                      danger: false,
                  }
                : {
                      title: "Terminate agreement?",
                      message:
                          "Terminate only after all planned and active allocations have been closed.",
                      confirmLabel: "Terminate agreement",
                      danger: true,
                  },
        );
        if (confirmed) {
            await transition(status);
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
                <ErrorAlert error={result.error} inline />
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
        correctAgreementMode === "lessee"
            ? "lessee agreement"
            : "lessor agreement";
    const printRequested = searchParams.get("print") === "1";
    const draftRate = draftRateVersion(row);
    const displayedRate =
        row.status === AGREEMENT_STATUS.draft
            ? draftRate
            : row.active_rate_version ?? null;
    const issues = activationIssues(row);
    const hasOpenAllocation = Boolean(
        row.allocations?.some((allocation) =>
            OPEN_ALLOCATION_STATUSES.has(allocation.status),
        ),
    );
    const canManageAllocations =
        row.status === AGREEMENT_STATUS.draft ||
        row.status === AGREEMENT_STATUS.active;
    const allocationActionLabel =
        row.agreement_kind === RENTAL_AGREEMENT_KIND.customerRental
            ? "Assign vehicle"
            : "Register supplied vehicle";

    if (printRequested && isExpectedAgreement) {
        return (
            <RentalPage>
                {row.document_snapshot ? (
                    <RentalAgreementPrintDocument
                        snapshot={row.document_snapshot}
                        backPath={agreementDetailPath(mode, row.id)}
                    />
                ) : (
                    <div
                        className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                        role="alert"
                    >
                        The immutable agreement document becomes available after
                        the agreement is activated. Review the Draft commercial
                        terms on the agreement page before activation.
                    </div>
                )}
            </RentalPage>
        );
    }

    return (
        <RentalPage>
            <ContentHeader
                title={row.agreement_number}
                description={
                    row.agreement_kind ===
                    RENTAL_AGREEMENT_KIND.customerRental
                        ? "Customer contract, billable pricing, vehicle allocation and deposit obligation."
                        : row.agreement_kind ===
                            RENTAL_AGREEMENT_KIND.ownerSupply
                          ? "Vehicle-owner contract, payable pricing and supplied-vehicle allocation."
                          : "Rental agreement, governed pricing and vehicle allocations."
                }
                actions={
                    isExpectedAgreement ? (
                        <>
                            {canManageAllocations && (
                                <LinkButton
                                    to={`/vehicle-rental/allocations?agreement_id=${id}`}
                                >
                                    {hasOpenAllocation
                                        ? "View allocations"
                                        : allocationActionLabel}
                                </LinkButton>
                            )}
                            {row.status === AGREEMENT_STATUS.draft && (
                                <>
                                    <LinkButton
                                        variant="secondary"
                                        to={agreementEditPath(mode, row.id)}
                                    >
                                        Edit draft
                                    </LinkButton>
                                    <Button
                                        loading={busy}
                                        disabled={issues.length > 0}
                                        onClick={() =>
                                            void confirmTransition("active")
                                        }
                                    >
                                        Review and activate
                                    </Button>
                                    <Button
                                        variant="danger"
                                        loading={busy}
                                        onClick={() => void destroyDraft()}
                                    >
                                        Delete
                                    </Button>
                                </>
                            )}
                            {row.status === AGREEMENT_STATUS.active && (
                                <>
                                    {row.document_snapshot && (
                                        <LinkButton
                                            variant="secondary"
                                            to={`${agreementDetailPath(mode, row.id)}?print=1`}
                                        >
                                            Print agreement
                                        </LinkButton>
                                    )}
                                    <Button
                                        variant="danger"
                                        loading={busy}
                                        onClick={() =>
                                            void confirmTransition("terminated")
                                        }
                                    >
                                        Terminate
                                    </Button>
                                </>
                            )}
                            {row.status !== AGREEMENT_STATUS.draft &&
                                row.status !== AGREEMENT_STATUS.active &&
                                row.document_snapshot && (
                                    <LinkButton
                                        variant="secondary"
                                        to={`${agreementDetailPath(mode, row.id)}?print=1`}
                                    >
                                        Print agreement
                                    </LinkButton>
                                )}
                        </>
                    ) : (
                        <LinkButton
                            variant="secondary"
                            to={agreementDetailPath(
                                correctAgreementMode,
                                row.id,
                            )}
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
                    <ErrorAlert
                        error={actionError ?? result.error}
                        title="Agreement action failed"
                        inline
                    />
                    {row.status === AGREEMENT_STATUS.draft && (
                        <ActivationReadinessPanel issues={issues} />
                    )}
                    <div className="grid gap-5 lg:grid-cols-2">
                        <Panel title="Agreement overview">
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
                                        value: (
                                            <StatusBadge status={row.status} />
                                        ),
                                    },
                                    {
                                        label: "Agreement date",
                                        value: formatDate(row.agreement_date),
                                    },
                                    {
                                        label: "Executed",
                                        value: row.executed_at
                                            ? formatDate(row.executed_at)
                                            : "Not recorded",
                                    },
                                    {
                                        label: "Legal context",
                                        value: row.legal_context
                                            ? humanize(row.legal_context)
                                            : "Not selected",
                                    },
                                    {
                                        label: "Contract starts",
                                        value: formatDate(row.starts_at),
                                    },
                                    {
                                        label: "Contract ends",
                                        value: formatDate(row.ends_at),
                                    },
                                    {
                                        label: "Service type",
                                        value: humanize(row.rental_mode),
                                    },
                                    {
                                        label: "Billing policy",
                                        value: `${humanize(row.billing_cycle)} / ${humanize(row.billing_basis)} / ${humanize(row.proration_rule)}`,
                                    },
                                    {
                                        label: "Payment terms",
                                        value: formatPaymentTerms(
                                            row.payment_term_days,
                                        ),
                                    },
                                    {
                                        label: "Currency",
                                        value: readableRelation(row.currency),
                                    },
                                ]}
                            />
                            {row.remarks && (
                                <div className="mt-4 border-t border-slate-200 pt-4 text-sm">
                                    <h3 className="font-semibold">
                                        Printed agreement remarks
                                    </h3>
                                    <p className="mt-1 whitespace-pre-wrap text-slate-600">
                                        {row.remarks}
                                    </p>
                                </div>
                            )}
                        </Panel>
                        <CommercialTermsPanel
                            agreement={row}
                            rate={displayedRate}
                            draft={row.status === AGREEMENT_STATUS.draft}
                        />
                    </div>
                    <Panel title="Terms and conditions" className="mt-5">
                        {row.terms?.length ? (
                            <ol className="space-y-4">
                                {row.terms.map((term) => (
                                    <li
                                        key={term.id}
                                        className="rounded-lg border border-slate-200 p-4 text-sm"
                                    >
                                        <h3 className="font-semibold">
                                            {term.sequence}.{" "}
                                            {term.title ||
                                                `Clause ${term.sequence}`}
                                        </h3>
                                        <p className="mt-2 whitespace-pre-wrap leading-6 text-slate-600">
                                            {term.content}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        ) : (
                            <p className="text-sm text-slate-500">
                                No agreement-specific clauses have been recorded.
                            </p>
                        )}
                    </Panel>
                    {row.agreement_kind ===
                        RENTAL_AGREEMENT_KIND.customerRental &&
                        row.deposit_requirement && (
                            <Panel
                                title="Security deposit obligation"
                                className="mt-5"
                            >
                                <DetailGrid
                                    items={[
                                        {
                                            label: "Required",
                                            value: money(
                                                row.deposit_requirement
                                                    .required_amount,
                                                row.deposit_requirement.currency
                                                    ?.code,
                                            ),
                                        },
                                        {
                                            label: "Received",
                                            value: money(
                                                row.deposit_requirement
                                                    .received_amount,
                                                row.deposit_requirement.currency
                                                    ?.code,
                                            ),
                                        },
                                        {
                                            label: "Balance",
                                            value: money(
                                                row.deposit_requirement
                                                    .balance_amount,
                                                row.deposit_requirement.currency
                                                    ?.code,
                                            ),
                                        },
                                        {
                                            label: "Status",
                                            value: (
                                                <StatusBadge
                                                    status={
                                                        row.deposit_requirement
                                                            .status
                                                    }
                                                />
                                            ),
                                        },
                                    ]}
                                />
                                <div className="mt-4 flex flex-wrap gap-2">
                                    <LinkButton
                                        variant="secondary"
                                        to={`/vehicle-rental/deposits?agreement_id=${row.id}`}
                                    >
                                        View deposit activity
                                    </LinkButton>
                                </div>
                            </Panel>
                        )}
                    <Panel title="Vehicle allocations" className="mt-5">
                        {row.allocations?.length ? (
                            <div className="space-y-2">
                                {row.allocations.map((allocation) => (
                                    <Link
                                        key={allocation.id}
                                        to={`/vehicle-rental/allocations/${allocation.id}`}
                                        className="flex flex-col justify-between gap-2 rounded-lg border p-3 hover:bg-slate-50 sm:flex-row sm:items-center"
                                    >
                                        <span>
                                            {allocation.allocation_number} -{" "}
                                            {readableRelation(
                                                allocation.vehicle,
                                            )}
                                            <span className="ml-2 text-slate-500">
                                                {formatDate(
                                                    allocation.allocated_from,
                                                )}
                                                {allocation.allocated_to
                                                    ? ` – ${formatDate(allocation.allocated_to)}`
                                                    : ""}
                                            </span>
                                        </span>
                                        <StatusBadge status={allocation.status} />
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-slate-500">
                                    No vehicle allocation yet. Create a planned
                                    allocation for this agreement.
                                </p>
                                {canManageAllocations && (
                                    <LinkButton
                                        to={`/vehicle-rental/allocations?agreement_id=${row.id}`}
                                    >
                                        {allocationActionLabel}
                                    </LinkButton>
                                )}
                            </div>
                        )}
                    </Panel>
                    <AgreementRunningChartPanel
                        agreementId={row.id}
                        side={rentalAgreementFinancialSide(
                            row.agreement_kind,
                        )}
                    />
                    {confirmDialog}
                </>
            )}
        </RentalPage>
    );
}

function ActivationReadinessPanel({ issues }: { issues: string[] }) {
    const ready = issues.length === 0;

    return (
        <Panel title="Activation review" className="mb-5">
            <div
                className={`rounded-lg border p-4 text-sm ${ready ? "border-emerald-200 bg-emerald-50 text-emerald-800" : "border-amber-200 bg-amber-50 text-amber-800"}`}
                role="status"
            >
                {ready ? (
                    <>
                        <p className="font-semibold">
                            Ready for final activation review.
                        </p>
                        <p className="mt-1">
                            Review the complete Draft commercial terms, deposit and
                            printable clauses below. Activation makes this contract
                            snapshot immutable.
                        </p>
                    </>
                ) : (
                    <>
                        <p className="font-semibold">
                            Complete before activation:
                        </p>
                        <ul className="mt-2 list-disc space-y-1 pl-5">
                            {issues.map((issue) => (
                                <li key={issue}>{issue}</li>
                            ))}
                        </ul>
                    </>
                )}
            </div>
        </Panel>
    );
}

function CommercialTermsPanel({
    agreement,
    rate,
    draft,
}: {
    agreement: RentalAgreement;
    rate: RentalRateVersion | null;
    draft: boolean;
}) {
    return (
        <Panel title={draft ? "Draft commercial terms" : "Active rate version"}>
            {rate ? (
                <>
                    <DetailGrid
                        items={[
                            {
                                label: "Version",
                                value: String(rate.version_number),
                            },
                            {
                                label: "Effective from",
                                value: formatDate(rate.effective_from),
                            },
                            {
                                label: "Effective to",
                                value: rate.effective_to
                                    ? formatDate(rate.effective_to)
                                    : "Open ended",
                            },
                            {
                                label: "Billing policy",
                                value: `${humanize(rate.billing_cycle)} / ${humanize(rate.billing_basis)}`,
                            },
                            {
                                label: "Proration",
                                value: humanize(rate.proration_rule),
                            },
                            {
                                label: "Excess KM method",
                                value: humanize(rate.excess_km_method),
                            },
                            {
                                label: "Included KM",
                                value: rate.included_km,
                            },
                            {
                                label: "Currency",
                                value: readableRelation(agreement.currency),
                            },
                        ]}
                    />
                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="px-3 py-2">Component</th>
                                    <th className="px-3 py-2 text-right">Rate</th>
                                    <th className="px-3 py-2">Unit</th>
                                    <th className="px-3 py-2">Tax</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {rate.components.map((component) => (
                                    <tr
                                        key={
                                            component.id ??
                                            `${component.component_code}-${component.unit}`
                                        }
                                    >
                                        <td className="px-3 py-2 font-medium text-slate-900">
                                            {rentalAgreementRateLabel(
                                                agreement.agreement_kind,
                                                component.component_code,
                                            )}
                                        </td>
                                        <td className="px-3 py-2 text-right font-semibold">
                                            {component.rate}
                                        </td>
                                        <td className="px-3 py-2">
                                            {humanize(component.unit)}
                                        </td>
                                        <td className="px-3 py-2">
                                            {component.is_taxable
                                                ? "Taxable"
                                                : "Non-taxable"}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {rate.components.length === 0 && (
                        <p className="mt-4 text-sm text-rose-600">
                            No rate components are available for review.
                        </p>
                    )}
                </>
            ) : (
                <p className="text-sm text-rose-600" role="alert">
                    {draft
                        ? "No Draft rate version is available. Edit the agreement and add its contract rates before activation."
                        : "No active rate version."}
                </p>
            )}
        </Panel>
    );
}

function activationIssues(agreement: RentalAgreement): string[] {
    if (agreement.status !== AGREEMENT_STATUS.draft) return [];

    const issues: string[] = [];
    const party = agreement.customer ?? agreement.supplier;
    const draftRates =
        agreement.rate_versions?.filter(
            (version) => version.status === AGREEMENT_STATUS.draft,
        ) ?? [];

    if (!party) issues.push("Select the agreement party.");
    if (!agreement.starts_at || !agreement.ends_at) {
        issues.push("Complete the contract period.");
    }
    if (!agreement.executed_at) {
        issues.push("Record the signed/executed date.");
    }
    if (!agreement.legal_context) {
        issues.push("Select the legal context.");
    }
    if (draftRates.length !== 1) {
        issues.push("Provide exactly one Draft rate version.");
    } else if (draftRates[0].components.length === 0) {
        issues.push("Add at least one contract rate component.");
    }

    return issues;
}

function draftRateVersion(agreement: RentalAgreement): RentalRateVersion | null {
    return (
        agreement.rate_versions?.find(
            (version) => version.status === AGREEMENT_STATUS.draft,
        ) ?? null
    );
}

function formatPaymentTerms(value: number | null | undefined): string {
    if (value === null || value === undefined) return "Not specified";
    if (value === 0) return "Due immediately";
    return `${value} days`;
}

function money(value: string, currency?: string): string {
    return [currency, value].filter(Boolean).join(" ");
}

function agreementModeForKind(
    kind: string,
): Exclude<RentalAgreementPageMode, "standard"> {
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
                    per_page: RUNNING_CHART_PAGE_SIZE,
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
            render: (usage) =>
                usage.allocation ? (
                    <Link
                        className="font-semibold text-blue-700 hover:underline"
                        to={`/vehicle-rental/running-chart?allocation_id=${usage.allocation.id}`}
                    >
                        {usage.usage_number}
                    </Link>
                ) : (
                    usage.usage_number
                ),
        },
        {
            key: "date",
            header: "Date",
            render: (usage) => formatDate(usage.usage_date),
        },
        {
            key: "allocation",
            header: "Allocation",
            render: (usage) =>
                readableRelation(
                    usageContextForSide(usage, side)?.allocation ??
                        usage.allocation,
                ),
        },
        {
            key: "physical",
            header: "Physical KM",
            render: (usage) =>
                `${usage.distance_km} total / ${usage.net_operational_distance_km} net`,
        },
        {
            key: "commercial",
            header: commercialDistanceLabel,
            render: (usage) =>
                usageFactForSide(usage, side)?.commercial_distance_km ?? "-",
        },
        {
            key: "physical_status",
            header: "Physical",
            render: (usage) => <StatusBadge status={usage.status} />,
        },
        {
            key: "fact_status",
            header: factLabel,
            render: (usage) => (
                <StatusBadge
                    status={
                        usageFactForSide(usage, side)?.status ?? "missing"
                    }
                />
            ),
        },
    ];

    return (
        <Panel title={title} className="mt-5">
            <ErrorAlert error={result.error} inline />
            {result.loading ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={result.data?.data ?? []}
                    columns={columns}
                    rowKey={(usage) => usage.id}
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
    return usage.contexts.find(
        (context) => context.financial_side === side,
    );
}
