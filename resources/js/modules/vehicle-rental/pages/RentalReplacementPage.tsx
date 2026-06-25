import { useState, type FormEvent } from "react";
import { useNavigate, useParams } from "react-router-dom";
import type { VehicleSummary } from "@/modules/vehicle/vehicleTypes";
import { VehicleLookupSelect } from "@/modules/vehicle/components/VehicleLookupSelect";
import { Button } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DetailGrid } from "@/shared/components/DetailGrid";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { Textarea } from "@/shared/components/Textarea";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { useApi } from "@/shared/hooks/useApi";
import { readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import { getRentalAllocation, replaceRentalVehicle } from "../vehicleRentalApi";

interface InspectionForm {
    odometer: string;
    fuel_level_percent: string;
    location: string;
    condition_summary: string;
    damage_summary: string;
}

const emptyInspection = (): InspectionForm => ({
    odometer: "",
    fuel_level_percent: "",
    location: "",
    condition_summary: "",
    damage_summary: "",
});

export default function RentalReplacementPage() {
    const allocationId = Number(useParams().id);
    const navigate = useNavigate();
    const allocation = useApi(
        (signal) => getRentalAllocation(allocationId, signal),
        [allocationId],
    );
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [form, setForm] = useState({
        vehicle_source_type: "company_owned",
        source_allocation_id: "",
        vehicle_finance_agreement_id: "",
        replacement_at: "",
        allocated_to: "",
        reason_code: "breakdown",
        reason: "",
        billing_continuity_rule: "continue_period",
    });
    const [oldReturn, setOldReturn] = useState<InspectionForm>(emptyInspection);
    const [newHandover, setNewHandover] =
        useState<InspectionForm>(emptyInspection);
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!vehicle) return;
        setSaving(true);
        setActionError(null);
        try {
            await replaceRentalVehicle(allocationId, {
                new_vehicle_id: vehicle.id,
                vehicle_source_type: form.vehicle_source_type,
                source_allocation_id: form.source_allocation_id
                    ? Number(form.source_allocation_id)
                    : null,
                vehicle_finance_agreement_id: form.vehicle_finance_agreement_id
                    ? Number(form.vehicle_finance_agreement_id)
                    : null,
                replacement_at: form.replacement_at,
                allocated_to: form.allocated_to || null,
                reason_code: form.reason_code || null,
                reason: form.reason || null,
                billing_continuity_rule: form.billing_continuity_rule,
                old_return: {
                    ...oldReturn,
                    fuel_level_percent: oldReturn.fuel_level_percent || null,
                },
                new_handover: {
                    ...newHandover,
                    fuel_level_percent: newHandover.fuel_level_percent || null,
                },
            });
            navigate(`/vehicle-rental/allocations/${allocationId}`);
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    if (allocation.loading)
        return (
            <RentalPage>
                <LoadingState />
            </RentalPage>
        );
    if (!allocation.data)
        return (
            <RentalPage>
                <ErrorAlert error={allocation.error} />
            </RentalPage>
        );

    return (
        <RentalPage>
            <ContentHeader
                title="Replace rental vehicle"
                description="Close the old allocation, record its return and hand over the replacement as one atomic transaction."
            />
            <ErrorAlert error={actionError ?? allocation.error} />
            <Panel title="Current allocation">
                <DetailGrid
                    items={[
                        {
                            label: "Allocation",
                            value: allocation.data.allocation_number,
                        },
                        {
                            label: "Agreement",
                            value: readableRelation(allocation.data.agreement),
                        },
                        {
                            label: "Current vehicle",
                            value: readableRelation(allocation.data.vehicle),
                        },
                        { label: "Status", value: allocation.data.status },
                    ]}
                />
            </Panel>
            <form onSubmit={submit} className="mt-5 space-y-5">
                <Panel title="Replacement">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <VehicleLookupSelect
                            value={vehicle}
                            onChange={setVehicle}
                        />
                        <Select
                            label="Vehicle source"
                            value={form.vehicle_source_type}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    vehicle_source_type: event.target.value,
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
                                required
                                value={form.source_allocation_id}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        source_allocation_id:
                                            event.target.value,
                                    })
                                }
                            />
                        )}
                        {form.vehicle_source_type === "financed" && (
                            <Input
                                label="Finance agreement ID"
                                type="number"
                                min="1"
                                required
                                value={form.vehicle_finance_agreement_id}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        vehicle_finance_agreement_id:
                                            event.target.value,
                                    })
                                }
                            />
                        )}
                        <Input
                            label="Replacement at"
                            type="datetime-local"
                            required
                            value={form.replacement_at}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    replacement_at: event.target.value,
                                })
                            }
                        />
                        <Input
                            label="New allocation end"
                            type="datetime-local"
                            value={form.allocated_to}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    allocated_to: event.target.value,
                                })
                            }
                        />
                        <Select
                            label="Continuity"
                            value={form.billing_continuity_rule}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    billing_continuity_rule: event.target.value,
                                })
                            }
                            options={[
                                {
                                    value: "continue_period",
                                    label: "Continue billing period",
                                },
                                {
                                    value: "split_period",
                                    label: "Split billing period",
                                },
                            ]}
                        />
                        <Input
                            label="Reason code"
                            value={form.reason_code}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    reason_code: event.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="mt-4">
                        <Textarea
                            label="Reason"
                            value={form.reason}
                            onChange={(event) =>
                                setForm({ ...form, reason: event.target.value })
                            }
                        />
                    </div>
                </Panel>
                <InspectionPanel
                    title="Old vehicle return"
                    value={oldReturn}
                    onChange={setOldReturn}
                />
                <InspectionPanel
                    title="Replacement vehicle handover"
                    value={newHandover}
                    onChange={setNewHandover}
                />
                <div className="flex justify-end">
                    <Button
                        type="submit"
                        loading={saving}
                        disabled={
                            !vehicle ||
                            !form.replacement_at ||
                            !oldReturn.odometer ||
                            !newHandover.odometer
                        }
                    >
                        Complete replacement
                    </Button>
                </div>
            </form>
        </RentalPage>
    );
}

function InspectionPanel({
    title,
    value,
    onChange,
}: {
    title: string;
    value: InspectionForm;
    onChange: (value: InspectionForm) => void;
}) {
    return (
        <Panel title={title}>
            <div className="grid gap-4 md:grid-cols-3">
                <Input
                    label="Odometer"
                    type="number"
                    min="0"
                    step="0.000001"
                    required
                    value={value.odometer}
                    onChange={(event) =>
                        onChange({ ...value, odometer: event.target.value })
                    }
                />
                <Input
                    label="Fuel level %"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    value={value.fuel_level_percent}
                    onChange={(event) =>
                        onChange({
                            ...value,
                            fuel_level_percent: event.target.value,
                        })
                    }
                />
                <Input
                    label="Location"
                    value={value.location}
                    onChange={(event) =>
                        onChange({ ...value, location: event.target.value })
                    }
                />
            </div>
            <div className="mt-4 grid gap-4 md:grid-cols-2">
                <Textarea
                    label="Condition"
                    value={value.condition_summary}
                    onChange={(event) =>
                        onChange({
                            ...value,
                            condition_summary: event.target.value,
                        })
                    }
                />
                <Textarea
                    label="Damage"
                    value={value.damage_summary}
                    onChange={(event) =>
                        onChange({
                            ...value,
                            damage_summary: event.target.value,
                        })
                    }
                />
            </div>
        </Panel>
    );
}
