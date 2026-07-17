import { useState, type FormEvent } from "react";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button } from "@/shared/components/Button";
import { DetailGrid } from "@/shared/components/DetailGrid";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { Panel } from "@/shared/components/Panel";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { Textarea } from "@/shared/components/Textarea";
import {
    transitionRentalUsageFact,
    updateRentalUsageFact,
} from "../vehicleRentalApi";
import type { RentalUsageFact } from "../vehicleRentalTypes";

interface FactForm {
    started_at: string;
    ended_at: string;
    start_odometer: string;
    end_odometer: string;
    commercial_distance_km: string;
    normal_overtime_minutes: string;
    double_overtime_minutes: string;
    triple_overtime_minutes: string;
    night_out_count: string;
    reference_number: string;
    variance_reason: string;
    remarks: string;
}

interface RentalUsageFactEditorProps {
    fact: RentalUsageFact;
    canRecord: boolean;
    canApprove: boolean;
    onSaved: () => void;
}

const toDateTimeInput = (value: string) => {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value.slice(0, 16);
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);
    return local.toISOString().slice(0, 16);
};

const formFromFact = (fact: RentalUsageFact): FactForm => ({
    started_at: toDateTimeInput(fact.started_at),
    ended_at: toDateTimeInput(fact.ended_at),
    start_odometer: fact.start_odometer,
    end_odometer: fact.end_odometer,
    commercial_distance_km: fact.commercial_distance_km,
    normal_overtime_minutes: String(fact.normal_overtime_minutes),
    double_overtime_minutes: String(fact.double_overtime_minutes),
    triple_overtime_minutes: String(fact.triple_overtime_minutes),
    night_out_count: fact.night_out_count,
    reference_number: fact.reference_number ?? "",
    variance_reason: fact.variance_reason ?? "",
    remarks: fact.remarks ?? "",
});

const sideTitle = (side: RentalUsageFact["financial_side"]) =>
    side === "revenue"
        ? "Customer / lessee billable facts"
        : "Vehicle owner / lessor payable facts";

export function RentalUsageFactEditor({
    fact,
    canRecord,
    canApprove,
    onSaved,
}: RentalUsageFactEditorProps) {
    const [form, setForm] = useState<FactForm>(() => formFromFact(fact));
    const [editingVariance, setEditingVariance] = useState(false);
    const [dirty, setDirty] = useState(false);
    const [transitionReason, setTransitionReason] = useState("");
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const editable = canRecord && ["draft", "rejected"].includes(fact.status);

    const change = (field: keyof FactForm, value: string) => {
        setForm((current) => ({ ...current, [field]: value }));
        setDirty(true);
    };

    const save = async (event: FormEvent) => {
        event.preventDefault();
        if (!editable || !editingVariance || !dirty) return;
        setBusy(true);
        setError(null);
        try {
            await updateRentalUsageFact(fact.id, {
                expected_version: fact.row_version,
                ...form,
                normal_overtime_minutes: Number(form.normal_overtime_minutes),
                double_overtime_minutes: Number(form.double_overtime_minutes),
                triple_overtime_minutes: Number(form.triple_overtime_minutes),
            });
            setDirty(false);
            setEditingVariance(false);
            onSaved();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    const transition = async (status: string) => {
        if (dirty) return;
        setBusy(true);
        setError(null);
        try {
            await transitionRentalUsageFact(
                fact.id,
                fact.row_version,
                status,
                transitionReason || undefined,
            );
            onSaved();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    return (
        <form onSubmit={save}>
            <Panel title={sideTitle(fact.financial_side)}>
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-slate-600">
                        Derived from the approved physical Running Chart. Edit only when
                        this commercial side has a documented variance.
                    </p>
                    <StatusBadge status={fact.status} />
                </div>
                <ErrorAlert error={error} />

                {!editingVariance ? (
                    <>
                        <DetailGrid
                            items={[
                                {
                                    label: "Commercial period",
                                    value: `${fact.started_at} - ${fact.ended_at}`,
                                },
                                {
                                    label: "Commercial odometer",
                                    value: `${fact.start_odometer} - ${fact.end_odometer}`,
                                },
                                {
                                    label:
                                        fact.financial_side === "revenue"
                                            ? "Billable kilometres"
                                            : "Payable kilometres",
                                    value: fact.commercial_distance_km,
                                },
                                {
                                    label: "Normal OT minutes",
                                    value: fact.normal_overtime_minutes,
                                },
                                {
                                    label: "Double OT minutes",
                                    value: fact.double_overtime_minutes,
                                },
                                {
                                    label: "Triple OT minutes",
                                    value: fact.triple_overtime_minutes,
                                },
                                {
                                    label: "Night-outs",
                                    value: fact.night_out_count,
                                },
                                {
                                    label: "Customer / owner reference",
                                    value: fact.reference_number ?? "-",
                                },
                                {
                                    label: "Variance reason",
                                    value: fact.variance_reason ?? "No variance recorded",
                                },
                            ]}
                        />
                        {editable && (
                            <div className="mt-4 flex justify-end">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => setEditingVariance(true)}
                                >
                                    Record commercial variance
                                </Button>
                            </div>
                        )}
                    </>
                ) : (
                    <>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Input
                                label="Commercial start"
                                type="datetime-local"
                                required
                                value={form.started_at}
                                onChange={(event) =>
                                    change("started_at", event.target.value)
                                }
                            />
                            <Input
                                label="Commercial finish"
                                type="datetime-local"
                                required
                                value={form.ended_at}
                                onChange={(event) =>
                                    change("ended_at", event.target.value)
                                }
                            />
                            <Input
                                label="Commercial start odometer"
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                value={form.start_odometer}
                                onChange={(event) =>
                                    change("start_odometer", event.target.value)
                                }
                            />
                            <Input
                                label="Commercial end odometer"
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                value={form.end_odometer}
                                onChange={(event) =>
                                    change("end_odometer", event.target.value)
                                }
                            />
                            <Input
                                label={
                                    fact.financial_side === "revenue"
                                        ? "Billable kilometres"
                                        : "Payable kilometres"
                                }
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                value={form.commercial_distance_km}
                                onChange={(event) =>
                                    change("commercial_distance_km", event.target.value)
                                }
                            />
                            <Input
                                label="Normal OT minutes"
                                type="number"
                                min="0"
                                value={form.normal_overtime_minutes}
                                onChange={(event) =>
                                    change("normal_overtime_minutes", event.target.value)
                                }
                            />
                            <Input
                                label="Double OT minutes"
                                type="number"
                                min="0"
                                value={form.double_overtime_minutes}
                                onChange={(event) =>
                                    change("double_overtime_minutes", event.target.value)
                                }
                            />
                            <Input
                                label="Triple OT minutes"
                                type="number"
                                min="0"
                                value={form.triple_overtime_minutes}
                                onChange={(event) =>
                                    change("triple_overtime_minutes", event.target.value)
                                }
                            />
                            <Input
                                label="Night-outs"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.night_out_count}
                                onChange={(event) =>
                                    change("night_out_count", event.target.value)
                                }
                            />
                            <Input
                                label="Customer / owner reference"
                                value={form.reference_number}
                                onChange={(event) =>
                                    change("reference_number", event.target.value)
                                }
                            />
                        </div>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Textarea
                                label="Variance reason"
                                required
                                value={form.variance_reason}
                                onChange={(event) =>
                                    change("variance_reason", event.target.value)
                                }
                            />
                            <Textarea
                                label="Remarks"
                                value={form.remarks}
                                onChange={(event) =>
                                    change("remarks", event.target.value)
                                }
                            />
                        </div>
                        <div className="mt-4 flex flex-wrap justify-end gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                disabled={busy}
                                onClick={() => {
                                    setForm(formFromFact(fact));
                                    setDirty(false);
                                    setEditingVariance(false);
                                }}
                            >
                                Cancel variance
                            </Button>
                            <Button type="submit" loading={busy} disabled={!dirty}>
                                Save variance
                            </Button>
                        </div>
                    </>
                )}

                {dirty && (
                    <p className="mt-3 text-sm font-medium text-amber-700">
                        Save or cancel the variance before changing this side&apos;s status.
                    </p>
                )}
                <div className="mt-4 grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                    <Input
                        label="Approval, rejection or reversal note"
                        value={transitionReason}
                        onChange={(event) => setTransitionReason(event.target.value)}
                    />
                    <div className="flex flex-wrap justify-end gap-2">
                        {canRecord && fact.status === "draft" && (
                            <Button
                                type="button"
                                variant="secondary"
                                loading={busy}
                                disabled={dirty}
                                onClick={() => void transition("submitted")}
                            >
                                Submit
                            </Button>
                        )}
                        {canApprove && fact.status === "submitted" && (
                            <>
                                <Button
                                    type="button"
                                    loading={busy}
                                    onClick={() => void transition("approved")}
                                >
                                    Approve
                                </Button>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    loading={busy}
                                    onClick={() => void transition("rejected")}
                                >
                                    Reject
                                </Button>
                            </>
                        )}
                        {canApprove && fact.status === "approved" && (
                            <Button
                                type="button"
                                variant="secondary"
                                loading={busy}
                                disabled={!transitionReason.trim()}
                                onClick={() => void transition("reversed")}
                            >
                                Reverse
                            </Button>
                        )}
                    </div>
                </div>
            </Panel>
        </form>
    );
}
