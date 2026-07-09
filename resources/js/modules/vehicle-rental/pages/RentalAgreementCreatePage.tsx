import { useEffect, useMemo, useState, type FormEvent } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
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
import type { NamedResource } from "@/shared/types/common";
import { useMutationFormGuard } from "@/shared/hooks/useMutationFormGuard";
import { businessDateInputValue } from "@/shared/utils/businessDate";
import { parsePositiveInteger } from "@/shared/utils/routeParams";
import { RentalPage } from "../components/RentalPage";
import { RentalCurrencyLookupSelect } from "../components/RentalLookups";
import {
    RENTAL_AGREEMENT_KIND,
    RENTAL_AGREEMENT_KIND_OPTIONS,
    agreementDetailPath,
    isRentalAgreementKind,
    type RentalAgreementPageMode,
} from "../rentalAgreementPresentation";
import { createRentalAgreement, getRentalReservation } from "../vehicleRentalApi";
import type { RentalReservation } from "../vehicleRentalTypes";

const coreComponentDefaults = [
    ["base_rental", "month"],
    ["excess_km", "km"],
    ["driver_salary", "month"],
    ["normal_overtime", "hour"],
    ["double_overtime", "hour"],
    ["triple_overtime", "hour"],
    ["night_out", "count"],
] as const;

const eventComponentDefaults = [
    ["parking", "count"],
    ["toll", "count"],
    ["waiting", "hour"],
    ["outstation", "trip"],
    ["pass", "count"],
    ["fuel", "litre"],
    ["damage", "fixed"],
    ["repair", "fixed"],
    ["other_recovery", "fixed"],
] as const;

const componentDefaults = [
    ...coreComponentDefaults,
    ...eventComponentDefaults,
] as const;

interface AgreementTermForm {
    title: string;
    content: string;
}

const emptyAgreementTerm = (): AgreementTermForm => ({
    title: "",
    content: "",
});

function toDateTimeLocal(value: string | null | undefined): string {
    if (!value) return "";

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value.slice(0, 16);

    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, "0"),
        String(date.getDate()).padStart(2, "0"),
    ].join("-") + `T${String(date.getHours()).padStart(2, "0")}:${String(date.getMinutes()).padStart(2, "0")}`;
}

interface RentalAgreementCreatePageProps {
    mode?: RentalAgreementPageMode;
}

export default function RentalAgreementCreatePage({
    mode = "standard",
}: RentalAgreementCreatePageProps) {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const reservationId =
        mode === "lessor"
            ? null
            : parsePositiveInteger(searchParams.get("reservation_id"));
    const requestedKind = searchParams.get("kind");
    const initialAgreementKind =
        mode === "lessee"
            ? RENTAL_AGREEMENT_KIND.customerRental
            : mode === "lessor"
            ? RENTAL_AGREEMENT_KIND.ownerSupply
            : isRentalAgreementKind(requestedKind)
                ? requestedKind
                : RENTAL_AGREEMENT_KIND.customerRental;
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [supplier, setSupplier] = useState<SupplierSummary | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [reservation, setReservation] = useState<RentalReservation | null>(null);
    const [form, setForm] = useState({
        agreement_kind: initialAgreementKind as string,
        agreement_date: "",
        executed_at: "",
        starts_at: "",
        ends_at: "",
        legal_context: "company",
        rental_mode: "with_driver",
        billing_cycle: "monthly",
        billing_basis: "calendar_month",
        proration_rule: "exact_day_count",
        payment_term_days: "30",
        included_km: "0",
        excess_km_method: "period",
        deposit_amount: "0",
        remarks: "",
    });
    const [rates, setRates] = useState<Record<string, string>>(() =>
        Object.fromEntries(componentDefaults.map(([code]) => [code, "0"])),
    );
    const [terms, setTerms] = useState<AgreementTermForm[]>([
        emptyAgreementTerm(),
    ]);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(saving);
    const updateForm: typeof setForm = (next) => { formGuard.markDirty(); setForm(next); };
    useEffect(() => {
        if (!reservationId) return;

        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) setError(null);
        });

        void getRentalReservation(reservationId, controller.signal)
            .then((resource) => {
                setReservation(resource);
                if (resource.customer) setCustomer(resource.customer as CustomerSummary);
                if (resource.currency) setCurrency(resource.currency);
                setForm((current) => ({
                    ...current,
                    agreement_kind: RENTAL_AGREEMENT_KIND.customerRental,
                    agreement_date: current.agreement_date || businessDateInputValue(),
                    starts_at: toDateTimeLocal(resource.requested_start_at),
                    ends_at: toDateTimeLocal(resource.requested_end_at),
                    rental_mode: resource.rental_mode,
                    billing_cycle: resource.billing_cycle,
                }));
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            });

        return () => controller.abort();
    }, [reservationId]);
    const partyValid = useMemo(
        () =>
            form.agreement_kind === RENTAL_AGREEMENT_KIND.customerRental
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
                reservation_id: reservation?.id ?? undefined,
                expected_reservation_version: reservation?.row_version ?? undefined,
                customer_id:
                    form.agreement_kind === RENTAL_AGREEMENT_KIND.customerRental
                        ? customer?.id
                        : null,
                supplier_id:
                    form.agreement_kind === RENTAL_AGREEMENT_KIND.ownerSupply
                        ? supplier?.id
                        : null,
                currency_id: Number(currency?.id),
                payment_term_days: Number(form.payment_term_days),
                terms: terms.map((term, index) => ({
                    sequence: index + 1,
                    title: term.title.trim() || null,
                    content: term.content.trim(),
                    is_printable: true,
                })),
                rate_version: {
                    effective_from: form.starts_at,
                    driver_mode: form.rental_mode,
                    billing_cycle: form.billing_cycle,
                    billing_basis: form.billing_basis,
                    proration_rule: form.proration_rule,
                    excess_km_method: form.excess_km_method,
                    included_km: form.included_km,
                    currency_id: Number(currency?.id),
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
                    form.agreement_kind === RENTAL_AGREEMENT_KIND.customerRental &&
                    Number(form.deposit_amount) > 0
                        ? {
                              required_amount: form.deposit_amount,
                              currency_id: Number(currency?.id),
                              is_refundable: true,
                          }
                        : undefined,
            });
            formGuard.markSaved();
            navigate(agreementDetailPath(mode, row.id));
        } catch (e) {
            setError(toApiError(e));
        } finally {
            setSaving(false);
        }
    };
    const isLesseeAgreement =
        form.agreement_kind === RENTAL_AGREEMENT_KIND.customerRental;
    const commercialSideLabel = isLesseeAgreement
        ? "Lessee billable"
        : "Lessor payable";
    const termsComplete = terms.every((term) => term.content.trim() !== "");

    return (
        <RentalPage>
            <ContentHeader
                title={
                    mode === "lessee"
                        ? "New lessee agreement"
                        : mode === "lessor"
                        ? "New lessor agreement"
                        : "New rental agreement"
                }
                description={
                    mode === "lessee"
                        ? "Create the customer-side rental agreement and its first immutable rate version."
                        : mode === "lessor"
                        ? "Create the supplier-side payable agreement and its first immutable rate version."
                        : "Create the lessee or lessor agreement and its first immutable rate version."
                }
            />
            <ErrorAlert error={error} />
            <form onSubmit={submit} className="space-y-5">
                <Panel title="Agreement">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {mode === "standard" && (
                            <Select
                                label="Agreement kind"
                                value={form.agreement_kind}
                                disabled={reservation !== null}
                                onChange={(e) => {
                                    setCustomer(null);
                                    setSupplier(null);
                                    setTerms([emptyAgreementTerm()]);
                                    updateForm({
                                        ...form,
                                        agreement_kind: e.target.value,
                                    });
                                }}
                                options={[...RENTAL_AGREEMENT_KIND_OPTIONS]}
                            />
                        )}
                        {form.agreement_kind ===
                        RENTAL_AGREEMENT_KIND.customerRental ? (
                            <CustomerLookupSelect
                                value={customer}
                                onChange={(next) => { formGuard.markDirty(); setCustomer(next); }}
                            />
                        ) : (
                            <SupplierLookupSelect
                                value={supplier}
                                onChange={(next) => { formGuard.markDirty(); setSupplier(next); }}
                            />
                        )}
                        <Input
                            label="Agreement date"
                            type="date"
                            required
                            value={form.agreement_date}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    agreement_date: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="Executed at"
                            type="datetime-local"
                            required
                            min={
                                form.agreement_date
                                    ? `${form.agreement_date}T00:00`
                                    : undefined
                            }
                            value={form.executed_at}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    executed_at: e.target.value,
                                })
                            }
                            hint="Date and time the parties signed this agreement."
                        />
                        <Select
                            label="Legal context"
                            value={form.legal_context}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    legal_context: e.target.value,
                                })
                            }
                            options={[
                                { value: "company", label: "Company" },
                                { value: "personal", label: "Personal" },
                            ]}
                        />
                        <Input
                            label="Start"
                            type="datetime-local"
                            required
                            value={form.starts_at}
                            onChange={(e) =>
                                updateForm({ ...form, starts_at: e.target.value })
                            }
                        />
                        <Input
                            label="End"
                            type="datetime-local"
                            required
                            value={form.ends_at}
                            onChange={(e) =>
                                updateForm({ ...form, ends_at: e.target.value })
                            }
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
                        <Select
                            label="Billing basis"
                            value={form.billing_basis}
                            onChange={(e) =>
                                updateForm({
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
                        <RentalCurrencyLookupSelect
                            value={currency}
                            onChange={(next) => { formGuard.markDirty(); setCurrency(next); }}
                            required
                        />
                        <Input
                            label="Payment term days"
                            type="number"
                            min="0"
                            value={form.payment_term_days}
                            onChange={(e) =>
                                updateForm({
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
                                updateForm({
                                    ...form,
                                    included_km: e.target.value,
                                })
                            }
                        />
                        <Select
                            label="Excess KM method"
                            value={form.excess_km_method}
                            onChange={(e) =>
                                updateForm({
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
                        {form.agreement_kind ===
                            RENTAL_AGREEMENT_KIND.customerRental && (
                            <Input
                                label="Security deposit"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.deposit_amount}
                                onChange={(e) =>
                                    updateForm({
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
                                updateForm({ ...form, remarks: e.target.value })
                            }
                        />
                    </div>
                </Panel>
                <Panel
                    title={
                        isLesseeAgreement
                            ? "Lessee agreement terms"
                            : "Lessor agreement terms"
                    }
                >
                    <p className="mb-4 text-sm text-slate-600">
                        Record the clauses accepted by both parties. These terms
                        become immutable when the agreement is activated.
                    </p>
                    <div className="space-y-4">
                        {terms.map((term, index) => (
                            <div
                                key={index}
                                className="rounded-lg border border-slate-200 p-4"
                            >
                                <div className="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]">
                                    <Input
                                        label={`Clause ${index + 1} title`}
                                        value={term.title}
                                        onChange={(event) => {
                                            formGuard.markDirty();
                                            setTerms((current) =>
                                                current.map((row, rowIndex) =>
                                                    rowIndex === index
                                                        ? {
                                                              ...row,
                                                              title: event.target.value,
                                                          }
                                                        : row,
                                                ),
                                            );
                                        }}
                                    />
                                    {terms.length > 1 && (
                                        <div className="flex items-end">
                                            <Button
                                                variant="ghost"
                                                onClick={() => {
                                                    formGuard.markDirty();
                                                    setTerms((current) =>
                                                        current.filter(
                                                            (_, rowIndex) =>
                                                                rowIndex !== index,
                                                        ),
                                                    );
                                                }}
                                            >
                                                Remove clause
                                            </Button>
                                        </div>
                                    )}
                                </div>
                                <div className="mt-4">
                                    <Textarea
                                        label={`Clause ${index + 1} content`}
                                        required
                                        maxLength={20000}
                                        value={term.content}
                                        onChange={(event) => {
                                            formGuard.markDirty();
                                            setTerms((current) =>
                                                current.map((row, rowIndex) =>
                                                    rowIndex === index
                                                        ? {
                                                              ...row,
                                                              content:
                                                                  event.target.value,
                                                          }
                                                        : row,
                                                ),
                                            );
                                        }}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="mt-4">
                        <Button
                            variant="secondary"
                            onClick={() => {
                                formGuard.markDirty();
                                setTerms((current) => [
                                    ...current,
                                    emptyAgreementTerm(),
                                ]);
                            }}
                        >
                            Add clause
                        </Button>
                    </div>
                </Panel>
                <Panel title={`${commercialSideLabel} core rates`}>
                    <p className="mb-4 text-sm text-slate-600">
                        {isLesseeAgreement
                            ? "These rates calculate amounts billed to the lessee customer."
                            : "These rates calculate amounts payable to the lessor vehicle owner."}
                    </p>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {coreComponentDefaults.map(([code, unit]) => (
                            <Input
                                key={code}
                                label={`${code.replaceAll("_", " ")} (${unit})`}
                                type="number"
                                min="0"
                                step="0.000001"
                                value={rates[code]}
                                onChange={(e) => {
                                    formGuard.markDirty();
                                    setRates({
                                        ...rates,
                                        [code]: e.target.value,
                                    });
                                }}
                            />
                        ))}
                    </div>
                </Panel>
                <Panel title={`${commercialSideLabel} event rates`}>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {eventComponentDefaults.map(([code, unit]) => (
                            <Input
                                key={code}
                                label={`${code.replaceAll("_", " ")} (${unit})`}
                                type="number"
                                min="0"
                                step="0.000001"
                                value={rates[code]}
                                onChange={(e) => {
                                    formGuard.markDirty();
                                    setRates({
                                        ...rates,
                                        [code]: e.target.value,
                                    });
                                }}
                            />
                        ))}
                    </div>
                </Panel>
                <div className="flex justify-end">
                    <Button
                        type="submit"
                        loading={saving}
                        disabled={
                            !partyValid ||
                            !currency ||
                            !form.executed_at ||
                            !termsComplete
                        }
                    >
                        {mode === "lessee"
                            ? "Create lessee agreement"
                            : mode === "lessor"
                            ? "Create lessor agreement"
                            : "Create agreement"}
                    </Button>
                </div>
            </form>
        </RentalPage>
    );
}
