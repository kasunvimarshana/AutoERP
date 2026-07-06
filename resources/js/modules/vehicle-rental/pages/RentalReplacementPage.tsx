import { useEffect, useState, type FormEvent } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { listVehicleOwnerships } from "@/modules/vehicle/vehicleOwnershipApi";
import type { VehicleSummary } from "@/modules/vehicle/vehicleTypes";
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
import type { PartyVehicleRelationship } from "@/shared/types/partyVehicle";
import {
    RentalAvailableVehicleLookupSelect,
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

type AllocationLookupValue = NamedResource & { vehicle?: VehicleSummary | null };

const SOURCE_TYPE_COMPANY_OWNED = "company_owned";
const SOURCE_TYPE_OWNER_SUPPLIED = "owner_supplied";
const SOURCE_TYPE_FINANCED = "financed";
const OWNERSHIP_LOOKUP_PAGE_SIZE = 100;

const emptyInspection = (): InspectionForm => ({
    odometer: "",
    fuel_level_percent: "",
    location: "",
    condition_summary: "",
    damage_summary: "",
});

function toDateTimeLocal(value: string | null | undefined): string {
    if (!value) return "";

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "";

    const offset = date.getTimezoneOffset() * 60_000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

function toIsoDateTime(value: string): string {
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : date.toISOString();
}

function ownershipCoversPeriod(ownership: PartyVehicleRelationship, startsAtValue: string, endsAtValue: string): boolean {
    const startedAt = new Date(ownership.started_at);
    const endedAt = ownership.ended_at ? new Date(ownership.ended_at) : null;
    const periodStartsAt = new Date(startsAtValue);
    const periodEndsAt = new Date(endsAtValue);

    if (
        Number.isNaN(startedAt.getTime()) ||
        Number.isNaN(periodStartsAt.getTime()) ||
        Number.isNaN(periodEndsAt.getTime()) ||
        (endedAt !== null && Number.isNaN(endedAt.getTime()))
    ) {
        return false;
    }

    return startedAt.getTime() <= periodStartsAt.getTime()
        && (endedAt === null || endedAt.getTime() >= periodEndsAt.getTime());
}

function ownershipLabel(ownership: PartyVehicleRelationship | null): string {
    if (ownership === null) return "";

    return [
        ownership.vehicle.registration_number ?? ownership.vehicle.number,
        ownership.owner.name,
        ownership.ownership_type?.replaceAll("_", " "),
    ].filter(Boolean).join(" - ");
}

export default function RentalReplacementPage() {
    const allocationId = parsePositiveInteger(useParams().id);
    const navigate = useNavigate();
    const allocation = useApi(
        (signal) => getRentalAllocation(allocationId ?? 0, signal),
        [allocationId],
        allocationId !== null,
    );
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [sourceAllocation, setSourceAllocation] = useState<AllocationLookupValue | null>(null);
    const [financeAgreement, setFinanceAgreement] = useState<NamedResource | null>(null);
    const [companyOwnership, setCompanyOwnership] = useState<PartyVehicleRelationship | null>(null);
    const [companyOwnershipLoading, setCompanyOwnershipLoading] = useState(false);
    const [companyOwnershipHint, setCompanyOwnershipHint] = useState<string | null>(null);
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

    useEffect(() => {
        setCompanyOwnership(null);
        setCompanyOwnershipHint(null);

        const allocationEnd = form.allocated_to || toDateTimeLocal(allocation.data?.agreement?.ends_at ?? allocation.data?.allocated_to);
        if (form.vehicle_source_type !== SOURCE_TYPE_COMPANY_OWNED || !vehicle || !form.replacement_at || !allocationEnd) {
            setCompanyOwnershipLoading(false);

            return;
        }

        const selectedVehicle = vehicle;
        const selectedStartAt = toIsoDateTime(form.replacement_at);
        const selectedEndAt = toIsoDateTime(allocationEnd);
        const controller = new AbortController();
        setCompanyOwnershipLoading(true);

        void listVehicleOwnerships("company", {
            vehicle_id: selectedVehicle.id,
            status: "active",
            per_page: OWNERSHIP_LOOKUP_PAGE_SIZE,
        }, controller.signal).then((response) => {
            if (controller.signal.aborted) return;

            const ownership = response.data.find((row) => row.is_current && ownershipCoversPeriod(row, selectedStartAt, selectedEndAt))
                ?? response.data.find((row) => ownershipCoversPeriod(row, selectedStartAt, selectedEndAt))
                ?? null;

            setCompanyOwnership(ownership);
            setCompanyOwnershipHint(ownership === null
                ? "No active company ownership covers this replacement period."
                : null);
        }).catch((error: unknown) => {
            if (!controller.signal.aborted) setActionError(toApiError(error));
        }).finally(() => {
            if (!controller.signal.aborted) setCompanyOwnershipLoading(false);
        });

        return () => controller.abort();
    }, [
        allocation.data?.agreement?.ends_at,
        allocation.data?.allocated_to,
        form.allocated_to,
        form.replacement_at,
        form.vehicle_source_type,
        vehicle,
        vehicle?.id,
    ]);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        const agreementVersion = allocation.data?.agreement?.row_version;
        if (!allocationId || !vehicle || !allocation.data || !agreementVersion) return;
        if (form.vehicle_source_type === SOURCE_TYPE_COMPANY_OWNED && companyOwnership === null) {
            setCompanyOwnershipHint("No active company ownership covers this replacement period.");

            return;
        }
        setSaving(true);
        setActionError(null);
        try {
            await replaceRentalVehicle(allocationId, allocation.data.row_version, agreementVersion, {
                new_vehicle_id: vehicle.id,
                vehicle_source_type: form.vehicle_source_type,
                vehicle_ownership_id: form.vehicle_source_type === SOURCE_TYPE_COMPANY_OWNED
                    ? companyOwnership?.id ?? null
                    : null,
                source_allocation_id: form.vehicle_source_type === SOURCE_TYPE_OWNER_SUPPLIED
                    ? sourceAllocation?.id ?? null
                    : null,
                vehicle_finance_agreement_id: form.vehicle_source_type === SOURCE_TYPE_FINANCED
                    ? financeAgreement?.id ?? null
                    : null,
                replacement_at: toIsoDateTime(form.replacement_at),
                allocated_to: form.allocated_to ? toIsoDateTime(form.allocated_to) : null,
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

    const replacementEnd = form.allocated_to || toDateTimeLocal(allocation.data.agreement?.ends_at ?? allocation.data.allocated_to);
    const replacementStartAt = form.replacement_at ? toIsoDateTime(form.replacement_at) : null;
    const replacementEndAt = replacementEnd ? toIsoDateTime(replacementEnd) : null;
    const agreementVersion = allocation.data.agreement?.row_version;
    const allocationStart = toDateTimeLocal(allocation.data.allocated_from);
    const allocationEnd = toDateTimeLocal(allocation.data.agreement?.ends_at ?? allocation.data.allocated_to);
    const usesOwnerSourceAllocation = form.vehicle_source_type === SOURCE_TYPE_OWNER_SUPPLIED;

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
                        {usesOwnerSourceAllocation ? (
                            <Input
                                label="Vehicle"
                                value={readableRelation(vehicle)}
                                readOnly
                                required
                                hint="Selected from the owner source allocation."
                            />
                        ) : (
                            <RentalAvailableVehicleLookupSelect
                                value={vehicle}
                                onChange={(value) => {
                                    setVehicle(value);
                                    setSourceAllocation(null);
                                    setFinanceAgreement(null);
                                    setCompanyOwnership(null);
                                    setCompanyOwnershipHint(null);
                                }}
                                startAt={replacementStartAt}
                                endAt={replacementEndAt}
                                required
                            />
                        )}
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
                                setCompanyOwnership(null);
                                setCompanyOwnershipHint(null);
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
                        {form.vehicle_source_type === SOURCE_TYPE_COMPANY_OWNED && (
                            <Input
                                label="Company vehicle ownership"
                                value={companyOwnershipLoading ? "Checking ownership..." : ownershipLabel(companyOwnership)}
                                readOnly
                                required
                                hint={companyOwnershipHint ?? undefined}
                            />
                        )}
                        {usesOwnerSourceAllocation && (
                            <RentalAllocationLookupSelect
                                value={sourceAllocation}
                                onChange={(value) => {
                                    const selected = value as AllocationLookupValue | null;
                                    setSourceAllocation(selected);
                                    setVehicle(selected?.vehicle ?? null);
                                    setFinanceAgreement(null);
                                    setCompanyOwnership(null);
                                    setCompanyOwnershipHint(null);
                                }}
                                agreementKind="owner_supply"
                                coversStartAt={replacementStartAt}
                                coversEndAt={replacementEndAt}
                                openOnly
                                disabled={!replacementStartAt || !replacementEndAt}
                                excludeId={allocationId}
                                required
                            />
                        )}
                        {form.vehicle_source_type === SOURCE_TYPE_FINANCED && (
                            <RentalFinanceAgreementLookupSelect
                                value={financeAgreement}
                                onChange={setFinanceAgreement}
                                vehicleId={vehicle?.id}
                                coversStartAt={replacementStartAt}
                                coversEndAt={replacementEndAt}
                                activeOnly
                                disabled={!vehicle}
                                required
                            />
                        )}
                        <Input
                            label="Replacement at"
                            type="datetime-local"
                            required
                            value={form.replacement_at}
                            min={allocationStart || undefined}
                            max={replacementEnd || allocationEnd || undefined}
                            onChange={(event) => {
                                setForm({
                                    ...form,
                                    replacement_at: event.target.value,
                                });
                                setVehicle(null);
                                setSourceAllocation(null);
                                setFinanceAgreement(null);
                                setCompanyOwnership(null);
                                setCompanyOwnershipHint(null);
                            }}
                        />
                        <Input
                            label="New allocation end"
                            type="datetime-local"
                            value={form.allocated_to}
                            min={form.replacement_at || allocationStart || undefined}
                            max={allocationEnd || undefined}
                            onChange={(event) => {
                                setForm({
                                    ...form,
                                    allocated_to: event.target.value,
                                });
                                setVehicle(null);
                                setSourceAllocation(null);
                                setFinanceAgreement(null);
                                setCompanyOwnership(null);
                                setCompanyOwnershipHint(null);
                            }}
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
                            !agreementVersion ||
                            !form.replacement_at ||
                            !oldReturn.odometer ||
                            !newHandover.odometer ||
                            companyOwnershipLoading ||
                            (form.vehicle_source_type === SOURCE_TYPE_COMPANY_OWNED && !companyOwnership) ||
                            (form.vehicle_source_type === SOURCE_TYPE_OWNER_SUPPLIED && !sourceAllocation) ||
                            (form.vehicle_source_type === SOURCE_TYPE_FINANCED && !financeAgreement)
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
