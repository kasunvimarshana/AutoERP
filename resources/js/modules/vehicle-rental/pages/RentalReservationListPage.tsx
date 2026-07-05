import { useState } from "react";
import { Link } from "react-router-dom";
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
import { listRentalReservations } from "../vehicleRentalApi";
import type { RentalReservation } from "../vehicleRentalTypes";

export default function RentalReservationListPage() {
    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("");
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi(
        (signal) =>
            listRentalReservations(
                {
                    search: debounced || undefined,
                    status: status || undefined,
                    page,
                    per_page: 25,
                },
                signal,
            ),
        [debounced, status, page],
    );
    const columns: DataColumn<RentalReservation>[] = [
        {
            key: "number",
            header: "Reservation",
            render: (row) => (
                <Link
                    className="font-semibold text-blue-700 hover:underline"
                    to={`/vehicle-rental/reservations/${row.id}`}
                >
                    {row.reservation_number}
                </Link>
            ),
        },
        {
            key: "customer",
            header: "Customer",
            render: (row) => readableRelation(row.customer),
        },
        {
            key: "vehicle",
            header: "Vehicle",
            render: (row) => readableRelation(row.requested_vehicle),
        },
        {
            key: "period",
            header: "Period",
            render: (row) =>
                `${formatDate(row.requested_start_at)} - ${formatDate(row.requested_end_at)}`,
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
                title="Rental reservations"
                description="Capture customer demand before creating a binding rental agreement."
                actions={
                    <LinkButton to="/vehicle-rental/reservations/create">
                        New reservation
                    </LinkButton>
                }
            />
            <div className="mb-4 grid gap-4 md:grid-cols-2">
                <Input
                    label="Search"
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setPage(1);
                    }}
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
                        "pending",
                        "confirmed",
                        "converted",
                        "cancelled",
                        "expired",
                    ].map((value) => ({
                        value,
                        label: value.replaceAll("_", " "),
                    }))}
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
