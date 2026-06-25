import { useMemo, useState, type FormEvent } from "react";
import { useSearchParams } from "react-router-dom";
import type { EmployeeSummary } from "@/modules/hr/hrTypes";
import { useAuth } from "@/modules/auth/AuthProvider";
import { hasPermission } from "@/modules/auth/accessControl";
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
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { useApi } from "@/shared/hooks/useApi";
import { businessDateInputValue } from "@/shared/utils/businessDate";
import { formatDate } from "@/shared/utils/formatDate";
import { readableRelation } from "@/shared/utils/object";
import { parsePositiveInteger } from "@/shared/utils/routeParams";
import { RentalDriverLookupSelect } from "../components/RentalDriverLookupSelect";
import { RentalPage } from "../components/RentalPage";
import {
    createRentalUsageLog,
    listRentalAllocations,
    listRentalUsageLogs,
    transitionRentalUsageLog,
} from "../vehicleRentalApi";
import { vehicleRentalPermissions } from "../vehicleRentalPermissions";
import type { RentalUsageLog } from "../vehicleRentalTypes";

interface UsageForm {
    usage_date: string;
    started_at: string;
    ended_at: string;
    start_odometer: string;
    end_odometer: string;
    garage_distance_km: string;
    internal_distance_km: string;
    working_minutes: string;
    normal_overtime_minutes: string;
    double_overtime_minutes: string;
    triple_overtime_minutes: string;
    night_out_count: string;
    trip_from: string;
    trip_to: string;
    trip_purpose: string;
    remarks: string;
}

const emptyForm = (): UsageForm => ({
    usage_date: businessDateInputValue(),
    started_at: "",
    ended_at: "",
    start_odometer: "",
    end_odometer: "",
    garage_distance_km: "0",
    internal_distance_km: "0",
    working_minutes: "0",
    normal_overtime_minutes: "0",
    double_overtime_minutes: "0",
    triple_overtime_minutes: "0",
    night_out_count: "0",
    trip_from: "",
    trip_to: "",
    trip_purpose: "",
    remarks: "",
});

export default function RentalRunningChartPage() {
    const auth = useAuth();
    const [searchParams, setSearchParams] = useSearchParams();
    const allocationId = parsePositiveInteger(searchParams.get("allocation_id"));
    const [form, setForm] = useState<UsageForm>(emptyForm);
    const [driver, setDriver] = useState<EmployeeSummary | null>(null);
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const allocations = useApi(
        (signal) =>
            listRentalAllocations({ status: "active", per_page: 100 }, signal),
        [],
    );
    const logs = useApi(
        (signal) =>
            listRentalUsageLogs(
                {
                    vehicle_allocation_id: allocationId ?? undefined,
                    per_page: 50,
                },
                signal,
            ),
        [allocationId],
        allocationId !== null,
    );

    const allocationOptions = useMemo(
        () =>
            (allocations.data?.data ?? []).map((row) => ({
                value: String(row.id),
                label: `${row.allocation_number} · ${readableRelation(row.vehicle)}`,
            })),
        [allocations.data],
    );
    const canRecord = hasPermission(auth, vehicleRentalPermissions.usageRecord);
    const canApprove = hasPermission(auth, vehicleRentalPermissions.usageApprove);

    const save = async (event: FormEvent) => {
        event.preventDefault();
        if (allocationId === null) return;
        setSaving(true);
        setActionError(null);
        try {
            await createRentalUsageLog(allocationId, {
                ...form,
                driver_id: driver?.id ?? null,
                started_at: form.started_at || null,
                ended_at: form.ended_at || null,
                start_odometer: form.start_odometer,
                end_odometer: form.end_odometer,
                garage_distance_km: form.garage_distance_km,
                internal_distance_km: form.internal_distance_km,
                working_minutes: Number(form.working_minutes),
                normal_overtime_minutes: Number(form.normal_overtime_minutes),
                double_overtime_minutes: Number(form.double_overtime_minutes),
                triple_overtime_minutes: Number(form.triple_overtime_minutes),
                night_out_count: form.night_out_count,
            });
            setForm(emptyForm());
            setDriver(null);
            logs.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const transition = async (row: RentalUsageLog, status: string) => {
        setActionError(null);
        try {
            await transitionRentalUsageLog(row.id, status);
            logs.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const columns: DataColumn<RentalUsageLog>[] = [
        {
            key: "number",
            header: "Running chart",
            render: (row) => row.usage_number,
        },
        {
            key: "date",
            header: "Date",
            render: (row) => formatDate(row.usage_date),
        },
        {
            key: "driver",
            header: "Driver",
            render: (row) => readableRelation(row.driver),
        },
        {
            key: "distance",
            header: "KM",
            render: (row) =>
                `${row.distance_km} (${row.chargeable_distance_km} chargeable)`,
        },
        {
            key: "overtime",
            header: "Overtime",
            render: (row) =>
                `${row.normal_overtime_minutes}/${row.double_overtime_minutes}/${row.triple_overtime_minutes} min`,
        },
        {
            key: "status",
            header: "Status",
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: "actions",
            header: "",
            className: "text-right",
            render: (row) => (
                <div className="flex justify-end gap-2">
                    {canRecord && row.status === "draft" && (
                        <Button
                            variant="ghost"
                            onClick={() => void transition(row, "submitted")}
                        >
                            Submit
                        </Button>
                    )}
                    {canApprove && row.status === "submitted" && (
                        <Button
                            variant="ghost"
                            onClick={() => void transition(row, "approved")}
                        >
                            Approve
                        </Button>
                    )}
                    {canApprove && row.status === "submitted" && (
                        <Button
                            variant="ghost"
                            onClick={() => void transition(row, "rejected")}
                        >
                            Reject
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <RentalPage>
            <ContentHeader
                title="Daily running chart"
                description="Record one operational usage stream that feeds independent customer revenue and owner cost calculations."
            />
            <ErrorAlert
                error={actionError ?? allocations.error ?? logs.error}
            />
            <Panel title="Context">
                <Select
                    label="Active allocation"
                    value={allocationId !== null ? String(allocationId) : ""}
                    onChange={(event) =>
                        setSearchParams(
                            event.target.value
                                ? { allocation_id: event.target.value }
                                : {},
                        )
                    }
                    options={allocationOptions}
                />
            </Panel>
            {canRecord && allocationId !== null && (
                <form onSubmit={save} className="mt-5 space-y-5">
                    <Panel title="New usage entry">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Input
                                label="Usage date"
                                type="date"
                                required
                                value={form.usage_date}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        usage_date: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Started at"
                                type="datetime-local"
                                value={form.started_at}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        started_at: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Ended at"
                                type="datetime-local"
                                value={form.ended_at}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        ended_at: event.target.value,
                                    })
                                }
                            />
                            <RentalDriverLookupSelect
                                value={driver}
                                onChange={setDriver}
                            />
                            <Input
                                label="Start odometer"
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                value={form.start_odometer}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        start_odometer: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="End odometer"
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                value={form.end_odometer}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        end_odometer: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Garage KM"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.garage_distance_km}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        garage_distance_km: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Internal KM"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.internal_distance_km}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        internal_distance_km:
                                            event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Working minutes"
                                type="number"
                                min="0"
                                value={form.working_minutes}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        working_minutes: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Normal OT minutes"
                                type="number"
                                min="0"
                                value={form.normal_overtime_minutes}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        normal_overtime_minutes:
                                            event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Double OT minutes"
                                type="number"
                                min="0"
                                value={form.double_overtime_minutes}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        double_overtime_minutes:
                                            event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Triple OT minutes"
                                type="number"
                                min="0"
                                value={form.triple_overtime_minutes}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        triple_overtime_minutes:
                                            event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Night-outs"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.night_out_count}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        night_out_count: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="From"
                                value={form.trip_from}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        trip_from: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="To"
                                value={form.trip_to}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        trip_to: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Purpose"
                                value={form.trip_purpose}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        trip_purpose: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="mt-4">
                            <Textarea
                                label="Remarks"
                                value={form.remarks}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        remarks: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="mt-4 flex justify-end">
                            <Button type="submit" loading={saving}>
                                Save usage
                            </Button>
                        </div>
                    </Panel>
                </form>
            )}
            <div className="mt-5">
                {!allocationId ? (
                    <Panel>
                        <p className="text-sm text-slate-500">
                            Select an active allocation to view its running
                            chart.
                        </p>
                    </Panel>
                ) : logs.loading ? (
                    <LoadingState />
                ) : (
                    <DataTable
                        rows={logs.data?.data ?? []}
                        columns={columns}
                        rowKey={(row) => row.id}
                        emptyMessage="No running-chart entries exist for this allocation."
                    />
                )}
            </div>
        </RentalPage>
    );
}
