import { useMemo, useState, type FormEvent } from "react";
import { useNavigate } from "react-router-dom";
import { CustomerLookupSelect } from "@/modules/customer/components/CustomerLookupSelect";
import type { CustomerSummary } from "@/modules/customer/customerTypes";
import { SupplierLookupSelect } from "@/modules/supplier/components/SupplierLookupSelect";
import type { SupplierSummary } from "@/modules/supplier/supplierTypes";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { Textarea } from "@/shared/components/Textarea";
import { RentalPage } from "../components/RentalPage";
import { createRentalAgreement } from "../vehicleRentalApi";

const componentDefaults = [
    ["base_rental", "month"],
    ["excess_km", "km"],
    ["driver_salary", "month"],
    ["normal_overtime", "hour"],
    ["double_overtime", "hour"],
    ["triple_overtime", "hour"],
    ["night_out", "count"],
] as const;

export default function RentalAgreementCreatePage() {
    const navigate = useNavigate();
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [supplier, setSupplier] = useState<SupplierSummary | null>(null);
    const [form, setForm] = useState({
        agreement_kind: "customer_rental",
        agreement_date: "",
        starts_at: "",
        ends_at: "",
        legal_context: "company",
        rental_mode: "with_driver",
        billing_cycle: "monthly",
        billing_basis: "calendar_month",
        proration_rule: "exact_day_count",
        billing_timezone: "Asia/Colombo",
        payment_term_days: "30",
        currency_id: "",
        included_km: "0",
        excess_km_method: "period",
        deposit_amount: "0",
        remarks: "",
    });
    const [rates, setRates] = useState<Record<string, string>>(() =>
        Object.fromEntries(componentDefaults.map(([code]) => [code, "0"])),
    );
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const partyValid = useMemo(
        () =>
            form.agreement_kind === "customer_rental"
                ? Boolean(customer)
                : Boolean(supplier),
        [form.agreement_kind, customer, supplier],
    );
    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setError(null);
        try {
            const row = await createRentalAgreement({
                ...form,
                customer_id:
                    form.agreement_kind === "customer_rental"
                        ? customer?.id
                        : null,
                supplier_id:
                    form.agreement_kind === "owner_supply"
                        ? supplier?.id
                        : null,
                currency_id: Number(form.currency_id),
                payment_term_days: Number(form.payment_term_days),
                rate_version: {
                    effective_from: form.starts_at,
                    driver_mode: form.rental_mode,
                    billing_cycle: form.billing_cycle,
                    billing_basis: form.billing_basis,
                    proration_rule: form.proration_rule,
                    excess_km_method: form.excess_km_method,
                    included_km: form.included_km,
                    currency_id: Number(form.currency_id),
                    components: componentDefaults
                        .filter(
                            ([code]) =>
                                Number(rates[code]) > 0 ||
                                code === "base_rental",
                        )
                        .map(([code, unit], index) => ({
                            component_code: code,
                            unit,
                            rate: rates[code],
                            multiplier: "1",
                            calculation_order: index + 1,
                            is_taxable: true,
                        })),
                },
                activate_rate_version: true,
                deposit:
                    form.agreement_kind === "customer_rental" &&
                    Number(form.deposit_amount) > 0
                        ? {
                              required_amount: form.deposit_amount,
                              currency_id: Number(form.currency_id),
                              is_refundable: true,
                          }
                        : undefined,
            });
            navigate(`/vehicle-rental/agreements/${row.id}`);
        } catch (e) {
            setError(toApiError(e));
        } finally {
            setSaving(false);
        }
    };
    return (
        <RentalPage>
            <ContentHeader
                title="New rental agreement"
                description="Create the customer or owner agreement and its first immutable rate version."
            />
            <ErrorAlert error={error} />
            <form onSubmit={submit} className="space-y-5">
                <Panel title="Agreement">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Select
                            label="Agreement kind"
                            value={form.agreement_kind}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    agreement_kind: e.target.value,
                                })
                            }
                            options={[
                                {
                                    value: "customer_rental",
                                    label: "Customer rental",
                                },
                                {
                                    value: "owner_supply",
                                    label: "Vehicle owner supply",
                                },
                            ]}
                        />
                        {form.agreement_kind === "customer_rental" ? (
                            <CustomerLookupSelect
                                value={customer}
                                onChange={setCustomer}
                            />
                        ) : (
                            <SupplierLookupSelect
                                value={supplier}
                                onChange={setSupplier}
                            />
                        )}
                        <Input
                            label="Agreement date"
                            type="date"
                            required
                            value={form.agreement_date}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    agreement_date: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="Start"
                            type="datetime-local"
                            required
                            value={form.starts_at}
                            onChange={(e) =>
                                setForm({ ...form, starts_at: e.target.value })
                            }
                        />
                        <Input
                            label="End"
                            type="datetime-local"
                            required
                            value={form.ends_at}
                            onChange={(e) =>
                                setForm({ ...form, ends_at: e.target.value })
                            }
                        />
                        <Select
                            label="Rental mode"
                            value={form.rental_mode}
                            onChange={(e) =>
                                setForm({
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
                                setForm({
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
                        <Select
                            label="Billing basis"
                            value={form.billing_basis}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    billing_basis: e.target.value,
                                })
                            }
                            options={[
                                "calendar_month",
                                "anniversary",
                                "fixed_period",
                                "per_hire",
                                "per_usage_log",
                            ].map((value) => ({
                                value,
                                label: value.replaceAll("_", " "),
                            }))}
                        />
                        <Input
                            label="Currency ID"
                            type="number"
                            min="1"
                            required
                            value={form.currency_id}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    currency_id: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="Payment term days"
                            type="number"
                            min="0"
                            value={form.payment_term_days}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    payment_term_days: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="Included KM"
                            type="number"
                            min="0"
                            step="0.000001"
                            value={form.included_km}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    included_km: e.target.value,
                                })
                            }
                        />
                        <Select
                            label="Excess KM method"
                            value={form.excess_km_method}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    excess_km_method: e.target.value,
                                })
                            }
                            options={[
                                "period",
                                "per_hire",
                                "per_usage_log",
                            ].map((value) => ({
                                value,
                                label: value.replaceAll("_", " "),
                            }))}
                        />
                        {form.agreement_kind === "customer_rental" && (
                            <Input
                                label="Security deposit"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.deposit_amount}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        deposit_amount: e.target.value,
                                    })
                                }
                            />
                        )}
                    </div>
                    <div className="mt-4">
                        <Textarea
                            label="Remarks"
                            value={form.remarks}
                            onChange={(e) =>
                                setForm({ ...form, remarks: e.target.value })
                            }
                        />
                    </div>
                </Panel>
                <Panel title="Rate components">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {componentDefaults.map(([code, unit]) => (
                            <Input
                                key={code}
                                label={`${code.replaceAll("_", " ")} (${unit})`}
                                type="number"
                                min="0"
                                step="0.000001"
                                value={rates[code]}
                                onChange={(e) =>
                                    setRates({
                                        ...rates,
                                        [code]: e.target.value,
                                    })
                                }
                            />
                        ))}
                    </div>
                </Panel>
                <div className="flex justify-end">
                    <Button
                        type="submit"
                        loading={saving}
                        disabled={!partyValid}
                    >
                        Create agreement
                    </Button>
                </div>
            </form>
        </RentalPage>
    );
}
