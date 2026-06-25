import { useState, type FormEvent } from "react";
import { LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DataTable, type DataColumn } from "@/shared/components/DataTable";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { useApi } from "@/shared/hooks/useApi";
import { RentalPage } from "../components/RentalPage";
import type { RentalVehicle } from "../vehicleRentalTypes";
import { listAvailableRentalVehicles } from "../vehicleRentalApi";

const today = new Date().toISOString().slice(0, 10);

export default function VehicleAvailabilityPage() {
    const [draft, setDraft] = useState({
        start_at: `${today}T00:00`,
        end_at: `${today}T23:59`,
        search: "",
    });
    const [filters, setFilters] = useState(draft);
    const result = useApi(
        (signal) =>
            listAvailableRentalVehicles({ ...filters, per_page: 100 }, signal),
        [filters.start_at, filters.end_at, filters.search],
        Boolean(filters.start_at && filters.end_at),
    );

    const columns: DataColumn<RentalVehicle>[] = [
        {
            key: "number",
            header: "Vehicle",
            render: (row) =>
                row.vehicle_number ?? row.registration_number ?? `#${row.id}`,
        },
        {
            key: "registration",
            header: "Registration",
            render: (row) => row.registration_number ?? "-",
        },
        {
            key: "description",
            header: "Description",
            render: (row) =>
                [row.make, row.model].filter(Boolean).join(" ") || "-",
        },
        {
            key: "category",
            header: "Category",
            render: (row) => row.vehicle_category?.name ?? "-",
        },
        {
            key: "action",
            header: "",
            className: "text-right",
            render: (row) => (
                <LinkButton
                    variant="secondary"
                    to={`/vehicle-rental/allocations?vehicle_id=${row.id}`}
                >
                    Allocate
                </LinkButton>
            ),
        },
    ];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setFilters(draft);
    };

    return (
        <RentalPage>
            <ContentHeader
                title="Vehicle availability"
                description="Check availability against active and planned allocation periods."
            />
            <Panel>
                <form onSubmit={submit} className="grid gap-4 md:grid-cols-4">
                    <Input
                        label="Start"
                        type="datetime-local"
                        required
                        value={draft.start_at}
                        onChange={(event) =>
                            setDraft({ ...draft, start_at: event.target.value })
                        }
                    />
                    <Input
                        label="End"
                        type="datetime-local"
                        required
                        value={draft.end_at}
                        onChange={(event) =>
                            setDraft({ ...draft, end_at: event.target.value })
                        }
                    />
                    <Input
                        label="Search"
                        value={draft.search}
                        onChange={(event) =>
                            setDraft({ ...draft, search: event.target.value })
                        }
                        placeholder="Registration or vehicle number"
                    />
                    <div className="flex items-end">
                        <button
                            className="min-h-10 w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            type="submit"
                        >
                            Check availability
                        </button>
                    </div>
                </form>
            </Panel>
            <div className="mt-5">
                <ErrorAlert error={result.error} />
                {result.loading ? (
                    <LoadingState />
                ) : (
                    <DataTable
                        rows={result.data?.data ?? []}
                        columns={columns}
                        rowKey={(row) => row.id}
                        emptyMessage="No available vehicles match this period."
                    />
                )}
            </div>
        </RentalPage>
    );
}
