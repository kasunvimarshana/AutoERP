import { useState, type FormEvent } from "react";
import { useSearchParams } from "react-router-dom";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button, LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DataTable, type DataColumn } from "@/shared/components/DataTable";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Pagination } from "@/shared/components/Pagination";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { useApi } from "@/shared/hooks/useApi";
import { readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import {
    createRentalAllocation,
    listRentalAllocations,
} from "../vehicleRentalApi";
import type { RentalAllocation } from "../vehicleRentalTypes";

export default function RentalAllocationPage() {
    const [params] = useSearchParams();
    const agreementParam = params.get("agreement_id") ?? "";
    const [agreementId, setAgreementId] = useState(agreementParam);
    const [page, setPage] = useState(1);
    const [refresh, setRefresh] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const [form, setForm] = useState({
        vehicle_id: "",
        vehicle_source_type: "company_owned",
        source_allocation_id: "",
        vehicle_finance_agreement_id: "",
        allocated_from: "",
        allocated_to: "",
        start_odometer: "0",
    });
    const result = useApi(
        (signal) =>
            listRentalAllocations(
                { agreement_id: agreementId || undefined, page, per_page: 25 },
                signal,
            ),
        [agreementId, page, refresh],
    );
    const submit = async (e: FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setError(null);
        try {
            await createRentalAllocation(Number(agreementId), {
                ...form,
                vehicle_id: Number(form.vehicle_id),
                source_allocation_id: form.source_allocation_id
                    ? Number(form.source_allocation_id)
                    : null,
                vehicle_finance_agreement_id: form.vehicle_finance_agreement_id
                    ? Number(form.vehicle_finance_agreement_id)
                    : null,
                start_odometer: form.start_odometer,
            });
            setRefresh((v) => v + 1);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSaving(false);
        }
    };
    const columns: DataColumn<RentalAllocation>[] = [
        {
            key: "number",
            header: "Allocation",
            render: (r) => r.allocation_number,
        },
        {
            key: "agreement",
            header: "Agreement",
            render: (r) => readableRelation(r.agreement),
        },
        {
            key: "vehicle",
            header: "Vehicle",
            render: (r) => readableRelation(r.vehicle),
        },
        {
            key: "source",
            header: "Source",
            render: (r) => r.vehicle_source_type.replaceAll("_", " "),
        },
        {
            key: "period",
            header: "Period",
            render: (r) =>
                `${r.allocated_from.slice(0, 10)} – ${r.allocated_to?.slice(0, 10) ?? "open"}`,
        },
        {
            key: "status",
            header: "Status",
            render: (r) => <StatusBadge status={r.status} />,
        },
        {
            key: "actions",
            header: "",
            render: (r) => (
                <LinkButton
                    variant="secondary"
                    to={`/vehicle-rental/allocations/${r.id}`}
                >
                    View
                </LinkButton>
            ),
        },
    ];
    return (
        <RentalPage>
            <ContentHeader
                title="Vehicle allocations"
                description="Effective-dated vehicle and driver assignment with owner-source and finance traceability."
            />
            <ErrorAlert error={error ?? result.error} />
            <Panel title="Create allocation">
                <form onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Input
                            label="Agreement ID"
                            type="number"
                            min="1"
                            required
                            value={agreementId}
                            onChange={(e) => setAgreementId(e.target.value)}
                        />
                        <Input
                            label="Vehicle ID"
                            type="number"
                            min="1"
                            required
                            value={form.vehicle_id}
                            onChange={(e) =>
                                setForm({ ...form, vehicle_id: e.target.value })
                            }
                        />
                        <Select
                            label="Vehicle source"
                            value={form.vehicle_source_type}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    vehicle_source_type: e.target.value,
                                })
                            }
                            options={[
                                {
                                    value: "company_owned",
                                    label: "Company owned",
                                },
                                {
                                    value: "owner_supplied",
                                    label: "Owner supplied",
                                },
                                { value: "financed", label: "Financed" },
                            ]}
                        />
                        {form.vehicle_source_type === "owner_supplied" && (
                            <Input
                                label="Owner source allocation ID"
                                type="number"
                                min="1"
                                value={form.source_allocation_id}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        source_allocation_id: e.target.value,
                                    })
                                }
                            />
                        )}{" "}
                        {form.vehicle_source_type === "financed" && (
                            <Input
                                label="Finance agreement ID"
                                type="number"
                                min="1"
                                value={form.vehicle_finance_agreement_id}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        vehicle_finance_agreement_id:
                                            e.target.value,
                                    })
                                }
                            />
                        )}
                        <Input
                            label="From"
                            type="datetime-local"
                            required
                            value={form.allocated_from}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    allocated_from: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="To"
                            type="datetime-local"
                            value={form.allocated_to}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    allocated_to: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="Start odometer"
                            type="number"
                            min="0"
                            step="0.000001"
                            value={form.start_odometer}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    start_odometer: e.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="mt-4 flex justify-end">
                        <Button type="submit" loading={saving}>
                            Create allocation
                        </Button>
                    </div>
                </form>
            </Panel>
            <div className="mt-5">
                {result.loading ? (
                    <LoadingState />
                ) : (
                    <DataTable
                        rows={result.data?.data ?? []}
                        columns={columns}
                        rowKey={(r) => r.id}
                    />
                )}
                <Pagination meta={result.data?.meta} onPageChange={setPage} />
            </div>
        </RentalPage>
    );
}
