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
import { listRentalAgreements } from "../vehicleRentalApi";
import type { RentalAgreement } from "../vehicleRentalTypes";

export default function RentalAgreementListPage() {
    const [params] = useSearchParams();
    const initialKind = params.get("kind") ?? "";
    const [search, setSearch] = useState("");
    const [kind, setKind] = useState(initialKind);
    const [status, setStatus] = useState("");
    const [page, setPage] = useState(1);
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
                    to={`/vehicle-rental/agreements/${row.id}`}
                >
                    {row.agreement_number}
                </Link>
            ),
        },
        {
            key: "kind",
            header: "Kind",
            render: (row) => row.agreement_kind.replaceAll("_", " "),
        },
        {
            key: "party",
            header: "Customer / Owner",
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
                title="Rental agreements"
                description="Independent customer-revenue and vehicle-owner payable agreements with immutable rate versions."
                actions={
                    <LinkButton to="/vehicle-rental/agreements/create">
                        New agreement
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
                <Select
                    label="Agreement kind"
                    value={kind}
                    onChange={(e) => {
                        setKind(e.target.value);
                        setPage(1);
                    }}
                    options={[
                        { value: "customer_rental", label: "Customer rental" },
                        {
                            value: "owner_supply",
                            label: "Vehicle owner supply",
                        },
                    ]}
                />
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
