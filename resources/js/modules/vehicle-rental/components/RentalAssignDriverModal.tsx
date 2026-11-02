import { useEffect, useState, type FormEvent } from "react";
import { DriverLookupSelect } from "@/modules/hr/components/DriverLookupSelect";
import type { DriverSummary } from "@/modules/hr/driverTypes";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button } from "@/shared/components/Button";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { Modal } from "@/shared/components/Modal";
import { Select } from "@/shared/components/Select";
import { Textarea } from "@/shared/components/Textarea";
import { assignRentalDriver } from "../vehicleRentalApi";

interface RentalAssignDriverModalProps {
    open: boolean;
    allocationId: number | null;
    allocationNumber?: string;
    allocatedFrom?: string | null;
    allocatedTo?: string | null;
    initialRole?: "primary" | "relief";
    onClose: () => void;
    onSuccess: () => void;
}

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

export function RentalAssignDriverModal({
    open,
    allocationId,
    allocationNumber,
    allocatedFrom,
    allocatedTo,
    initialRole = "primary",
    onClose,
    onSuccess,
}: RentalAssignDriverModalProps) {
    const [selectedDriver, setSelectedDriver] = useState<DriverSummary | null>(null);
    const [assignmentRole, setAssignmentRole] = useState<"primary" | "relief">(initialRole);
    const [assignedFrom, setAssignedFrom] = useState(() => toDateTimeLocal(allocatedFrom));
    const [assignedTo, setAssignedTo] = useState(() => toDateTimeLocal(allocatedTo));
    const [driverRemarks, setDriverRemarks] = useState("");
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        if (open) {
            setSelectedDriver(null);
            setAssignmentRole(initialRole);
            setAssignedFrom(toDateTimeLocal(allocatedFrom));
            setAssignedTo(toDateTimeLocal(allocatedTo));
            setDriverRemarks("");
            setError(null);
        }
    }, [open, allocatedFrom, allocatedTo, initialRole]);

    const submit = async (e: FormEvent) => {
        e.preventDefault();
        if (!allocationId || !selectedDriver) return;

        setSubmitting(true);
        setError(null);
        try {
            await assignRentalDriver(allocationId, {
                employee_id: selectedDriver.id,
                assignment_role: assignmentRole,
                assigned_from: toIsoDateTime(assignedFrom),
                assigned_to: assignedTo ? toIsoDateTime(assignedTo) : null,
                is_primary: assignmentRole === "primary",
                remarks: driverRemarks || undefined,
            });
            onSuccess();
            onClose();
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open={open}
            title={allocationNumber ? `Assign Driver to ${allocationNumber}` : "Assign Driver to Allocation"}
            onClose={onClose}
            closeDisabled={submitting}
        >
            <form onSubmit={submit} className="space-y-4">
                <ErrorAlert error={error} />

                <DriverLookupSelect
                    label="Driver"
                    value={selectedDriver}
                    onChange={setSelectedDriver}
                    required
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <Select
                        label="Assignment role"
                        value={assignmentRole}
                        options={[
                            { value: "primary", label: "Primary driver" },
                            { value: "relief", label: "Relief driver" },
                        ]}
                        onChange={(e) => setAssignmentRole(e.target.value as "primary" | "relief")}
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Input
                        label="Assigned from"
                        type="datetime-local"
                        value={assignedFrom}
                        onChange={(e) => setAssignedFrom(e.target.value)}
                        required
                    />
                    <Input
                        label="Assigned to"
                        type="datetime-local"
                        value={assignedTo}
                        onChange={(e) => setAssignedTo(e.target.value)}
                        hint="Optional if open-ended"
                    />
                </div>

                <Textarea
                    label="Remarks"
                    rows={2}
                    value={driverRemarks}
                    onChange={(e) => setDriverRemarks(e.target.value)}
                    placeholder="e.g. Assigned for NSB corporate run, fuel allowance standard..."
                />

                <div className="flex justify-end gap-2 pt-2">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={onClose}
                        disabled={submitting}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        loading={submitting}
                        disabled={!selectedDriver || !assignedFrom}
                    >
                        Assign driver
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
