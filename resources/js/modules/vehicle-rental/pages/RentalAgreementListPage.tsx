import { useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DataTable, type DataColumn } from "@/shared/components/DataTable";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Pagination } from "@/shared/components/Pagination";
import { Select } from "@/shared/components/Select";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { useApi } from "@/shared/hooks/useApi";
import { useDebounce } from "@/shared/hooks/useDebounce";
import { formatDate } from "@/shared/utils/formatDate";
import { humanize, readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import {
    RENTAL_AGREEMENT_KIND_OPTIONS,
    agreementDetailPath,
    defaultAgreementKindForMode,
    isRentalAgreementKind,
    rentalAgreementKindLabel,
    type RentalAgreementPageMode,
} from "../rentalAgreementPresentation";
import { listRentalAgreements } from "../vehicleRentalApi";
import type { RentalAgreement } from "../vehicleRentalTypes";

const AGREEMENTS_PER_PAGE = 25;
const STATUS_OPTIONS = [
    "draft",
    "active",
    "suspended",
    "completed",
    "terminated",
    "cancelled",
].map((value) => ({ value, label: humanize(value) }));

interface RentalAgreementListPageProps {
    mode?: RentalAgreementPageMode;
}

export default function RentalAgreementListPage({
    mode = "standard",
}: RentalAgreementListPageProps) {
    const [params, setParams] = useSearchParams();
    const requestedKind = params.get("kind");
    const modeKind = defaultAgreementKindForMode(mode);
    const initialSelectedKind = isRentalAgreementKind(requestedKind)
        ? requestedKind
        : "";
    const [search, setSearch] = useState(params.get("search") ?? "");
    const [selectedKind, setSelectedKind] =
        useState<string>(initialSelectedKind);
    const [status, setStatus] = useState(params.get("status") ?? "");
    const kind = modeKind || selectedKind;
    const [pagination, setPagination] = useState({
        kind,
        page: positivePage(params.get("page")),
    });
    const page = pagination.kind === kind ? pagination.page : 1;
    const debounced = useDebounce(search);

    useEffect(() => {
        const next = new URLSearchParams();
        if (search) next.set("search", search);
        if (status) next.set("status", status);
        if (mode === "standard" && kind) next.set("kind", kind);
        if (page > 1) next.set("page", String(page));
        setParams(next, { replace: true });
    }, [kind, mode, page, search, setParams, status]);

    const result = useApi(
        (signal) =>
            listRentalAgreements(
                {
                    search: debounced || undefined,
                    agreement_kind: kind || undefined,
                    status: status || undefined,
                    page,
                    per_page: AGREEMENTS_PER_PAGE,
                },
                signal,
            ),
        [debounced, kind, status, page],
    );

    const columns = useMemo<DataColumn<RentalAgreement>[]>(() => {
        const values: DataColumn<RentalAgreement>[] = [
            {
                key: "number",
                header: "Agreement",
                render: (row) => (
                    <span className="font-semibold text-blue-700">
                        {row.agreement_number}
                    </span>
                ),
            },
        ];
        if (mode === "standard") {
            values.push({
                key: "kind",
                header: "Kind",
                render: (row) =>
                    rentalAgreementKindLabel(row.agreement_kind),
            });
        }
        values.push(
            {
                key: "party",
                header:
                    mode === "lessee"
                        ? "Customer / Lessee"
                        : mode === "lessor"
                          ? "Vehicle owner / Lessor"
                          : "Lessee / Lessor",
                render: (row) =>
                    readableRelation(row.customer ?? row.supplier),
            },
            {
                key: "period",
                header: "Contract period",
                render: (row) =>
                    `${formatDate(row.starts_at)} – ${formatDate(row.ends_at)}`,
            },
            {
                key: "mode",
                header: "Service type",
                render: (row) => humanize(row.rental_mode),
            },
            {
                key: "allocation",
                header: "Vehicle allocation",
                render: (row) => allocationSummary(row),
            },
            {
                key: "status",
                header: "Status",
                render: (row) => <StatusBadge status={row.status} />,
            },
        );

        return values;
    }, [mode]);

    const title =
        mode === "lessee"
            ? "Lessee agreements"
            : mode === "lessor"
              ? "Lessor agreements"
              : "Rental agreements";
    const createPath =
        mode === "lessee"
            ? "/vehicle-rental/lessee-agreements/create"
            : mode === "lessor"
              ? "/vehicle-rental/lessor-agreements/create"
              : "/vehicle-rental/agreements/create";
    const createLabel =
        mode === "lessee"
            ? "New lessee agreement"
            : mode === "lessor"
              ? "New lessor agreement"
              : "New agreement";

    return (
        <RentalPage>
            <ContentHeader
                title={title}
                description={
                    mode === "lessee"
                        ? "Customer contracts, billable pricing and vehicle-allocation readiness."
                        : mode === "lessor"
                          ? "Vehicle-owner contracts, payable pricing and supplied-vehicle readiness."
                          : "Independent customer revenue and vehicle-owner payable agreements."
                }
                actions={<LinkButton to={createPath}>{createLabel}</LinkButton>}
            />
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Input
                    label="Search"
                    placeholder="Search agreement, party, or vehicle"
                    value={search}
                    onChange={(event) => {
                        setSearch(event.target.value);
                        setPagination({ kind, page: 1 });
                    }}
                />
                {mode === "standard" && (
                    <Select
                        label="Agreement kind"
                        placeholder="All agreement kinds"
                        value={kind}
                        onChange={(event) => {
                            setSelectedKind(event.target.value);
                            setPagination({
                                kind: event.target.value,
                                page: 1,
                            });
                        }}
                        options={[...RENTAL_AGREEMENT_KIND_OPTIONS]}
                    />
                )}
                <Select
                    label="Status"
                    placeholder="All statuses"
                    value={status}
                    onChange={(event) => {
                        setStatus(event.target.value);
                        setPagination({ kind, page: 1 });
                    }}
                    options={STATUS_OPTIONS}
                />
            </div>
            <ErrorAlert error={result.error} inline />
            {result.loading ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={result.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    rowHref={(row) => agreementDetailPath(mode, row.id)}
                    rowBadge={(row) => <StatusBadge status={row.status} />}
                    mobileSummary={(row) => row.agreement_number}
                    mobileDetails={(row) => (
                        <div className="space-y-1">
                            <p>{readableRelation(row.customer ?? row.supplier)}</p>
                            <p className="text-slate-500">
                                {formatDate(row.starts_at)} – {formatDate(row.ends_at)}
                            </p>
                            <p className="text-slate-500">
                                {allocationSummary(row)}
                            </p>
                        </div>
                    )}
                    emptyMessage={`No ${title.toLowerCase()} match the current filters.`}
                />
            )}
            <Pagination
                meta={result.data?.meta}
                onPageChange={(nextPage) =>
                    setPagination({ kind, page: nextPage })
                }
            />
        </RentalPage>
    );
}

function allocationSummary(row: RentalAgreement): string {
    const allocations = row.allocations ?? [];
    if (allocations.length === 0) return "Not allocated";

    const active = allocations.find((allocation) =>
        ["planned", "active"].includes(allocation.status),
    );
    const selected = active ?? allocations[0];
    const vehicle = selected.vehicle;
    const vehicleLabel =
        vehicle?.registration_number ?? vehicle?.vehicle_number ?? vehicle?.name;

    return vehicleLabel
        ? `${vehicleLabel} (${humanize(selected.status)})`
        : `${allocations.length} allocation${allocations.length === 1 ? "" : "s"}`;
}

function positivePage(value: string | null): number {
    const parsed = Number(value);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : 1;
}
