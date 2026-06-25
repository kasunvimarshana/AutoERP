import { useState, type FormEvent } from "react";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DataTable, type DataColumn } from "@/shared/components/DataTable";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { Textarea } from "@/shared/components/Textarea";
import { useApi } from "@/shared/hooks/useApi";
import { readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import {
    confirmRentalCustodyEvent,
    createRentalCustodyEvent,
    listRentalCustodyEvents,
} from "../vehicleRentalApi";
import type { RentalCustodyEvent } from "../vehicleRentalTypes";
const eventOptions = [
    "owner_to_company",
    "company_to_customer",
    "customer_to_company",
    "company_to_owner",
    "replacement_out",
    "replacement_in",
    "internal_transfer",
];
export default function RentalCustodyPage() {
    const [allocationId, setAllocationId] = useState("");
    const [refresh, setRefresh] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const [form, setForm] = useState({
        event_type: "company_to_customer",
        occurred_at: "",
        odometer: "0",
        fuel_level_percent: "",
        location: "",
        from_role: "company",
        to_role: "customer",
        condition_summary: "",
        damage_summary: "",
    });
    const result = useApi(
        (signal) =>
            listRentalCustodyEvents(
                {
                    vehicle_allocation_id: allocationId || undefined,
                    per_page: 50,
                },
                signal,
            ),
        [allocationId, refresh],
    );
    const submit = async (e: FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setError(null);
        try {
            await createRentalCustodyEvent(Number(allocationId), {
                ...form,
                fuel_level_percent: form.fuel_level_percent || null,
            });
            setRefresh((v) => v + 1);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSaving(false);
        }
    };
    const confirm = async (id: number) => {
        try {
            await confirmRentalCustodyEvent(id);
            setRefresh((v) => v + 1);
        } catch (err) {
            setError(toApiError(err));
        }
    };
    const columns: DataColumn<RentalCustodyEvent>[] = [
        { key: "event", header: "Event", render: (r) => r.event_number },
        {
            key: "vehicle",
            header: "Vehicle",
            render: (r) => readableRelation(r.vehicle),
        },
        {
            key: "type",
            header: "Type",
            render: (r) => r.event_type.replaceAll("_", " "),
        },
        {
            key: "roles",
            header: "Custody",
            render: (r) => `${r.from_role} → ${r.to_role}`,
        },
        { key: "odometer", header: "Odometer", render: (r) => r.odometer },
        {
            key: "status",
            header: "Status",
            render: (r) => <StatusBadge status={r.status} />,
        },
        {
            key: "actions",
            header: "",
            render: (r) =>
                r.status === "draft" ? (
                    <Button
                        variant="secondary"
                        onClick={() => void confirm(r.id)}
                    >
                        Confirm
                    </Button>
                ) : null,
        },
    ];
    return (
        <RentalPage>
            <ContentHeader
                title="Vehicle custody"
                description="Owner/company/customer handovers and returns with condition, fuel and odometer evidence."
            />
            <ErrorAlert error={error ?? result.error} />
            <Panel title="Record custody event">
                <form onSubmit={submit}>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Input
                            label="Allocation ID"
                            type="number"
                            min="1"
                            required
                            value={allocationId}
                            onChange={(e) => setAllocationId(e.target.value)}
                        />
                        <Select
                            label="Event type"
                            value={form.event_type}
                            onChange={(e) =>
                                setForm({ ...form, event_type: e.target.value })
                            }
                            options={eventOptions.map((value) => ({
                                value,
                                label: value.replaceAll("_", " "),
                            }))}
                        />
                        <Input
                            label="Occurred at"
                            type="datetime-local"
                            required
                            value={form.occurred_at}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    occurred_at: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="Odometer"
                            type="number"
                            min="0"
                            step="0.000001"
                            required
                            value={form.odometer}
                            onChange={(e) =>
                                setForm({ ...form, odometer: e.target.value })
                            }
                        />
                        <Input
                            label="Fuel level %"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value={form.fuel_level_percent}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    fuel_level_percent: e.target.value,
                                })
                            }
                        />
                        <Select
                            label="From"
                            value={form.from_role}
                            onChange={(e) =>
                                setForm({ ...form, from_role: e.target.value })
                            }
                            options={["owner", "company", "customer"].map(
                                (value) => ({ value, label: value }),
                            )}
                        />
                        <Select
                            label="To"
                            value={form.to_role}
                            onChange={(e) =>
                                setForm({ ...form, to_role: e.target.value })
                            }
                            options={["owner", "company", "customer"].map(
                                (value) => ({ value, label: value }),
                            )}
                        />
                        <Input
                            label="Location"
                            value={form.location}
                            onChange={(e) =>
                                setForm({ ...form, location: e.target.value })
                            }
                        />
                    </div>
                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        <Textarea
                            label="Condition"
                            value={form.condition_summary}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    condition_summary: e.target.value,
                                })
                            }
                        />
                        <Textarea
                            label="Damage"
                            value={form.damage_summary}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    damage_summary: e.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="mt-4 flex justify-end">
                        <Button type="submit" loading={saving}>
                            Save custody event
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
            </div>
        </RentalPage>
    );
}
