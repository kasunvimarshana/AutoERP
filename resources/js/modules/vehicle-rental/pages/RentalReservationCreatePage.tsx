import { useState, type FormEvent } from "react";
import { useNavigate } from "react-router-dom";
import { CustomerLookupSelect } from "@/modules/customer/components/CustomerLookupSelect";
import type { CustomerSummary } from "@/modules/customer/customerTypes";
import { VehicleLookupSelect } from "@/modules/vehicle/components/VehicleLookupSelect";
import type { VehicleSummary } from "@/modules/vehicle/vehicleTypes";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { Textarea } from "@/shared/components/Textarea";
import { useMutationFormGuard } from "@/shared/hooks/useMutationFormGuard";
import { RentalPage } from "../components/RentalPage";
import { RentalCurrencyLookupSelect } from "../components/RentalLookups";
import { useRentalCurrencyDefault } from "../hooks/useRentalCurrencyDefault";
import { createRentalReservation } from "../vehicleRentalApi";

export default function RentalReservationCreatePage() {
    const navigate = useNavigate();
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const {
        currency,
        error: currencyDefaultError,
        selectCurrency,
        applyCurrencyDefault,
        resetCurrencyToDefault,
    } = useRentalCurrencyDefault();
    const [form, setForm] = useState({
        rental_mode: "with_driver",
        billing_cycle: "monthly",
        requested_start_at: "",
        requested_end_at: "",
        estimated_amount: "0",
        estimated_deposit_amount: "0",
        source: "walk_in",
        remarks: "",
    });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(saving);
    const updateForm: typeof setForm = (next) => { formGuard.markDirty(); setForm(next); };
    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!customer || !currency) return;

        setSaving(true);
        setError(null);
        try {
            const row = await createRentalReservation({
                ...form,
                customer_id: customer?.id,
                requested_vehicle_id: vehicle?.id || null,
                currency_id: Number(currency?.id),
                estimated_amount: form.estimated_amount,
                estimated_deposit_amount: form.estimated_deposit_amount,
            });
            formGuard.markSaved();
            resetCurrencyToDefault();
            navigate(`/vehicle-rental/reservations/${row.id}`);
        } catch (e) {
            setError(toApiError(e));
        } finally {
            setSaving(false);
        }
    };
    return (
        <RentalPage>
            <ContentHeader
                title="New rental reservation"
                description="Record the requested period, rental mode, vehicle preference and estimated deposit."
            />
            <ErrorAlert error={error ?? currencyDefaultError} />
            <form onSubmit={submit}>
                <Panel>
                    <div className="grid gap-4 md:grid-cols-2">
                        <CustomerLookupSelect
                            value={customer}
                            onChange={(next) => {
                                formGuard.markDirty();
                                setCustomer(next);
                                applyCurrencyDefault(next?.default_currency ?? null);
                            }}
                        />
                        <VehicleLookupSelect
                            value={vehicle}
                            onChange={(next) => { formGuard.markDirty(); setVehicle(next); }}
                        />
                        <Select
                            label="Rental mode"
                            value={form.rental_mode}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    rental_mode: e.target.value,
                                })
                            }
                            options={[
                                { value: "with_driver", label: "With driver" },
                                { value: "self_drive", label: "Self-drive" },
                                {
                                    value: "vehicle_only",
                                    label: "Vehicle only",
                                },
                            ]}
                        />
                        <Select
                            label="Billing cycle"
                            value={form.billing_cycle}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    billing_cycle: e.target.value,
                                })
                            }
                            options={[
                                "hourly",
                                "daily",
                                "weekly",
                                "monthly",
                                "per_hire",
                            ].map((value) => ({
                                value,
                                label: value.replaceAll("_", " "),
                            }))}
                        />
                        <Input
                            label="Start"
                            type="datetime-local"
                            required
                            value={form.requested_start_at}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    requested_start_at: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="Expected end"
                            type="datetime-local"
                            required
                            value={form.requested_end_at}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    requested_end_at: e.target.value,
                                })
                            }
                        />
                        <RentalCurrencyLookupSelect
                            value={currency}
                            onChange={(next) => {
                                formGuard.markDirty();
                                selectCurrency(next);
                            }}
                            required
                        />
                        <Input
                            label="Estimated amount"
                            type="number"
                            step="0.000001"
                            min="0"
                            value={form.estimated_amount}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    estimated_amount: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="Estimated deposit"
                            type="number"
                            step="0.000001"
                            min="0"
                            value={form.estimated_deposit_amount}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    estimated_deposit_amount: e.target.value,
                                })
                            }
                        />
                        <Textarea
                            label="Remarks"
                            value={form.remarks}
                            onChange={(e) =>
                                updateForm({ ...form, remarks: e.target.value })
                            }
                        />
                    </div>
                    <div className="mt-5 flex justify-end">
                        <Button
                            type="submit"
                            loading={saving}
                            disabled={!customer || !currency}
                        >
                            Create reservation
                        </Button>
                    </div>
                </Panel>
            </form>
        </RentalPage>
    );
}
