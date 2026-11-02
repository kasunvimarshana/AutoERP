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
import type { NamedResource } from "@/shared/types/common";
import { businessDateTimeInputValue } from "@/shared/utils/businessDate";
import { readableRelation } from "@/shared/utils/object";
import { parsePositiveInteger } from "@/shared/utils/routeParams";
import {
    RentalAllocationLookupSelect,
    RentalFinanceAgreementLookupSelect,
} from "../components/RentalLookups";
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
    const allocationId = parsePositiveInteger(useParams().id);
    const navigate = useNavigate();
    const allocation = useApi(
        (signal) => getRentalAllocation(allocationId ?? 0, signal),
        [allocationId],
        allocationId !== null,
    );
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [sourceAllocation, setSourceAllocation] = useState<NamedResource | null>(null);
    const [financeAgreement, setFinanceAgreement] = useState<NamedResource | null>(null);
    const [form, setForm] = useState({
        vehicle_source_type: "company_owned",
        replacement_at: businessDateTimeInputValue(),
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
        if (!allocationId || !vehicle) return;
        setSaving(true);
        setActionError(null);
        try {
            await replaceRentalVehicle(allocationId, {
                new_vehicle_id: vehicle.id,
                vehicle_source_type: form.vehicle_source_type,
                source_allocation_id: form.vehicle_source_type === "owner_supplied"
                    ? sourceAllocation?.id ?? null
                    : null,
                vehicle_finance_agreement_id: form.vehicle_source_type === "financed"
                    ? financeAgreement?.id ?? null
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

    if (!allocationId)
        return (
            <RentalPage>
                <ContentHeader
                    title="Invalid allocation"
                    description="The replacement route requires a valid allocation identifier."
                />
                <Panel>
                    <p className="text-sm text-slate-600">Open the replacement workflow from a valid rental allocation.</p>
                </Panel>
            </RentalPage>
        );

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
                        {
                            label: "Vehicle source",
                            value: allocation.data.vehicle_source_type.replaceAll(
                                "_",
                                " ",
                            ),
                        },
                        ...(allocation.data.vehicle_source_type === "owner_supplied"
                            ? [
                                  {
                                      label: "Current vehicle owner",
                                      value:
                                          allocation.data.source_allocation?.agreement?.supplier?.name ??
                                          allocation.data.source_allocation?.allocation_number ??
                                          "-",
                                  },
                                  {
                                      label: "Owner agreement",
                                      value:
                                          allocation.data.source_allocation?.agreement?.agreement_number ??
                                          "-",
                                  },
                              ]
                            : []),
                        { label: "Status", value: allocation.data.status },
                    ]}
                />
            </Panel>
            <div className="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sky-950 text-sm">
                <div className="font-semibold flex items-center gap-2">
                    <span>💡 Multi-Owner Fleet Replacement Support</span>
                </div>
                <p className="mt-1 text-sky-900 leading-relaxed">
                    The replacement vehicle can belong to the company fleet, the same vehicle owner, or a <strong>different owner / supplier</strong> under a separate supply agreement.
                    AutoERP links the new allocation to the replacement vehicle's distinct owner agreement, preserving separate contractual terms, validity periods, and cost rates without commingling running charts or odometers.
                </p>
            </div>
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
                            onChange={(event) => {
                                setForm({
                                    ...form,
                                    vehicle_source_type: event.target.value,
                                });
                                setSourceAllocation(null);
                                setFinanceAgreement(null);
                            }}
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
                            <div className="space-y-1">
                                <RentalAllocationLookupSelect
                                    value={sourceAllocation}
                                    onChange={setSourceAllocation}
                                    agreementKind="owner_supply"
                                    vehicleId={vehicle?.id}
                                    excludeId={allocationId}
                                    required
                                />
                                <p className="text-xs text-slate-500">
                                    Same or different owner supply allocation
                                </p>
                            </div>
                        )}
                        {form.vehicle_source_type === "financed" && (
                            <RentalFinanceAgreementLookupSelect
                                value={financeAgreement}
                                onChange={setFinanceAgreement}
                                required
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
                        <Select
                            label="Reason code"
                            value={form.reason_code}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    reason_code: event.target.value,
                                })
                            }
                            options={[
                                { value: "breakdown", label: "Breakdown / Mechanical Failure" },
                                { value: "accident", label: "Accident / Collision Damage" },
                                { value: "maintenance", label: "Routine Maintenance / Service" },
                                { value: "inspection_expiry", label: "License / Insurance Renewal" },
                                { value: "agreement_expiry", label: "Agreement Expiry / Owner Recall" },
                                { value: "client_request", label: "Client Request / Model Upgrade" },
                                { value: "other", label: "Other Operational Circumstances" },
                            ]}
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
                    {form.vehicle_source_type === "owner_supplied" && sourceAllocation && (
                        <div className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm flex flex-wrap items-center gap-2">
                            <span className="font-semibold text-slate-700">Linked Owner Supply:</span>
                            <span className="font-mono text-xs px-2 py-1 rounded bg-blue-100 text-blue-900 font-bold">
                                {sourceAllocation.name || sourceAllocation.code}
                            </span>
                            <span className="text-slate-500 text-xs">
                                Cost billing will be settled under this owner's agreement & rate terms.
                            </span>
                        </div>
                    )}
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
                            !newHandover.odometer ||
                            (form.vehicle_source_type === "owner_supplied" && !sourceAllocation) ||
                            (form.vehicle_source_type === "financed" && !financeAgreement)
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
