import { useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
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
import { readableRelation } from "@/shared/utils/object";
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

interface RentalAgreementListPageProps {
    mode?: RentalAgreementPageMode;
}

export default function RentalAgreementListPage({
    mode = "standard",
}: RentalAgreementListPageProps) {
    const [params] = useSearchParams();
    const requestedKind = params.get("kind");
    const modeKind = defaultAgreementKindForMode(mode);
    const initialSelectedKind = isRentalAgreementKind(requestedKind)
        ? requestedKind
        : "";
    const [search, setSearch] = useState("");
    const [selectedKind, setSelectedKind] =
        useState<string>(initialSelectedKind);
    const [status, setStatus] = useState("");
    const kind = modeKind || selectedKind;
    const [pagination, setPagination] = useState({
        kind,
        page: 1,
    });
    const page = pagination.kind === kind ? pagination.page : 1;
    const setPage = (nextPage: number) => {
        setPagination({ kind, page: nextPage });
    };
    const debounced = useDebounce(search);
    const result = useApi(
        (signal) =>
            listRentalAgreements(
                {
                    search: debounced || undefined,
                    agreement_kind: kind || undefined,
                    status: status || undefined,
                    page,
                    per_page: 25,
                },
                signal,
            ),
        [debounced, kind, status, page],
    );
    const columns: DataColumn<RentalAgreement>[] = [
        {
            key: "number",
            header: "Agreement",
            render: (row) => (
                <Link
                    className="font-semibold text-blue-700 hover:underline"
                    to={agreementDetailPath(mode, row.id)}
                >
                    {row.agreement_number}
                </Link>
            ),
        },
        {
            key: "kind",
            header: "Kind",
            render: (row) => rentalAgreementKindLabel(row.agreement_kind),
        },
        {
            key: "party",
            header:
                mode === "lessee"
                    ? "Lessee"
                    : mode === "lessor"
                        ? "Lessor"
                        : "Lessee / Lessor",
            render: (row) => readableRelation(row.customer ?? row.supplier),
        },
        {
            key: "period",
            header: "Period",
            render: (row) =>
                `${formatDate(row.starts_at)} - ${formatDate(row.ends_at)}`,
        },
        {
            key: "mode",
            header: "Mode",
            render: (row) => row.rental_mode.replaceAll("_", " "),
        },
        {
            key: "status",
            header: "Status",
            render: (row) => <StatusBadge status={row.status} />,
        },
    ];
    return (
        <RentalPage>
            <ContentHeader
                title={
                    mode === "lessee"
                        ? "Lessee agreements"
                        : mode === "lessor"
                        ? "Lessor agreements"
                        : "Rental agreements"
                }
                description={
                    mode === "lessee"
                        ? "Customer-side vehicle rental agreements used for lessee billing and deposits."
                        : mode === "lessor"
                        ? "Supplier-side vehicle rental agreements used for owner payable calculations."
                        : "Independent lessee revenue and lessor payable agreements with immutable rate versions."
                }
                actions={
                    <LinkButton
                        to={
                            mode === "lessee"
                                ? "/vehicle-rental/lessee-agreements/create"
                                : mode === "lessor"
                                ? "/vehicle-rental/lessor-agreements/create"
                                : "/vehicle-rental/agreements/create"
                        }
                    >
                        {mode === "lessee"
                            ? "New lessee agreement"
                            : mode === "lessor"
                            ? "New lessor agreement"
                            : "New agreement"}
                    </LinkButton>
                }
            />
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Input
                    label="Search"
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setPage(1);
                    }}
                />
                {mode === "standard" && (
                    <Select
                        label="Agreement kind"
                        value={kind}
                        onChange={(e) => {
                            setSelectedKind(e.target.value);
                            setPage(1);
                        }}
                        options={[...RENTAL_AGREEMENT_KIND_OPTIONS]}
                    />
                )}
                <Select
                    label="Status"
                    value={status}
                    onChange={(e) => {
                        setStatus(e.target.value);
                        setPage(1);
                    }}
                    options={[
                        "draft",
                        "active",
                        "suspended",
                        "completed",
                        "terminated",
                        "cancelled",
                    ].map((value) => ({ value, label: value }))}
                />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={result.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                />
            )}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </RentalPage>
    );
}
