import { useEffect, useMemo, useState, type FormEvent } from "react";
import { useNavigate, useParams, useSearchParams } from "react-router-dom";
import { CustomerLookupSelect } from "@/modules/customer/components/CustomerLookupSelect";
import type { CustomerSummary } from "@/modules/customer/customerTypes";
import { SupplierLookupSelect } from "@/modules/supplier/components/SupplierLookupSelect";
import type { SupplierSummary } from "@/modules/supplier/supplierTypes";
import { fieldError, toApiError, type ApiError } from "@/shared/api/apiError";
import { Button } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { Textarea } from "@/shared/components/Textarea";
import { useMutationFormGuard } from "@/shared/hooks/useMutationFormGuard";
import {
    businessDateInputValue,
    businessDateTimeInputValue,
} from "@/shared/utils/businessDate";
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
import {
    createRentalAgreement,
    getRentalAgreement,
    getRentalReservation,
    updateRentalAgreement,
} from "../vehicleRentalApi";
import type { RentalAgreement, RentalReservation } from "../vehicleRentalTypes";
import { useRentalCurrencyDefault } from "../hooks/useRentalCurrencyDefault";
import { rentalOptions } from "../hooks/useRentalMetadata";

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
    id?: number;
    sequence?: number;
    title: string;
    content: string;
}

const emptyAgreementTerm = (sequence?: number): AgreementTermForm => ({
    sequence,
    title: "",
    content: "",
});

function nextTermSequence(terms: AgreementTermForm[]): number {
    return Math.max(0, ...terms.map((term) => term.sequence ?? 0)) + 1;
}

function toDateInputValue(value: string | null | undefined): string {
    if (!value) return "";
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value.slice(0, 10);

    return businessDateInputValue(date);
}

function toDateTimeLocal(value: string | null | undefined): string {
    if (!value) return "";
    const localMatch = value.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/);
    const hasExplicitOffset = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(value);
    if (localMatch && !hasExplicitOffset) {
        return `${localMatch[1]}T${localMatch[2]}`;
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value.slice(0, 16);

    return businessDateTimeInputValue(date);
}

interface RentalAgreementCreatePageProps {
    mode?: RentalAgreementPageMode;
}

export default function RentalAgreementCreatePage({
    mode = "standard",
}: RentalAgreementCreatePageProps) {
    const navigate = useNavigate();
    const routeAgreementId = parsePositiveInteger(useParams().id);
    const isEditing = routeAgreementId !== null;
    const [searchParams] = useSearchParams();
    const reservationId =
        mode === "lessor" || isEditing
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
    const {
        currency,
        error: currencyDefaultError,
        selectCurrency,
        setAuthoritativeCurrency,
        applyCurrencyDefault,
        metadata,
    } = useRentalCurrencyDefault({ initialTouched: isEditing });
    const [reservation, setReservation] = useState<RentalReservation | null>(null);
    const [loadedAgreement, setLoadedAgreement] =
        useState<RentalAgreement | null>(null);
    const [loadingAgreement, setLoadingAgreement] = useState(isEditing);
    const [form, setForm] = useState({
        agreement_kind: initialAgreementKind as string,
        agreement_date: isEditing ? "" : businessDateInputValue(),
        executed_at: "",
        starts_at: "",
        ends_at: "",
        legal_context: "",
        rental_mode: "",
        billing_cycle: "",
        billing_basis: "",
        proration_rule: "",
        payment_term_days: "",
        included_km: "0",
        excess_km_method: "",
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
    const { markDirty, markSaved, resetDirty } = useMutationFormGuard(saving);
    const updateForm: typeof setForm = (next) => { markDirty(); setForm(next); };
    const metadataDefaults = metadata?.defaults;
    const legalContextOptions = useMemo(
        () => rentalOptions(metadata?.legal_contexts),
        [metadata?.legal_contexts],
    );
    const rentalModeOptions = useMemo(
        () => rentalOptions(metadata?.rental_modes),
        [metadata?.rental_modes],
    );
    const billingCycleOptions = useMemo(
        () => rentalOptions(metadata?.billing_cycles),
        [metadata?.billing_cycles],
    );
    const billingBasisOptions = useMemo(
        () => rentalOptions(metadata?.billing_bases),
        [metadata?.billing_bases],
    );
    const excessKmMethodOptions = useMemo(
        () => rentalOptions(metadata?.excess_km_methods),
        [metadata?.excess_km_methods],
    );

    useEffect(() => {
        if (isEditing || !metadataDefaults) return;

        let cancelled = false;
        queueMicrotask(() => {
            if (cancelled) return;

            setForm((current) => ({
                ...current,
                legal_context:
                    current.legal_context || metadataDefaults.legal_context || "",
                rental_mode:
                    current.rental_mode || metadataDefaults.rental_mode || "",
                billing_cycle:
                    current.billing_cycle || metadataDefaults.billing_cycle || "",
                billing_basis:
                    current.billing_basis || metadataDefaults.billing_basis || "",
                proration_rule:
                    current.proration_rule || metadataDefaults.proration_rule || "",
                payment_term_days:
                    current.payment_term_days ||
                    String(metadataDefaults.payment_term_days ?? ""),
                excess_km_method:
                    current.excess_km_method ||
                    metadataDefaults.excess_km_method ||
                    "",
            }));
        });

        return () => {
            cancelled = true;
        };
    }, [isEditing, metadataDefaults]);

    useEffect(() => {
        if (!isEditing || routeAgreementId === null) return;

        const controller = new AbortController();
        queueMicrotask(() => {
            if (controller.signal.aborted) return;

            setLoadingAgreement(true);
            setError(null);
        });

        void getRentalAgreement(routeAgreementId, controller.signal)
            .then((resource) => {
                setLoadedAgreement(resource);
                setCustomer((resource.customer ?? null) as CustomerSummary | null);
                setSupplier((resource.supplier ?? null) as SupplierSummary | null);
                setAuthoritativeCurrency(resource.currency ?? null);
                setForm({
                    agreement_kind: resource.agreement_kind,
                    agreement_date: resource.agreement_date,
                    executed_at: toDateInputValue(resource.executed_at),
                    starts_at: toDateTimeLocal(resource.starts_at),
                    ends_at: toDateTimeLocal(resource.ends_at),
                    legal_context: resource.legal_context ?? "",
                    rental_mode: resource.rental_mode,
                    billing_cycle: resource.billing_cycle,
                    billing_basis: resource.billing_basis,
                    proration_rule: resource.proration_rule,
                    payment_term_days:
                        resource.payment_term_days === null ||
                        resource.payment_term_days === undefined
                            ? ""
                            : String(resource.payment_term_days),
                    included_km: resource.active_rate_version?.included_km ?? "0",
                    excess_km_method:
                        resource.active_rate_version?.excess_km_method ?? "",
                    deposit_amount:
                        resource.deposit_requirement?.required_amount ?? "0",
                    remarks: resource.remarks ?? "",
                });
                setTerms(
                    resource.terms?.length
                        ? resource.terms.map((term) => ({
                              id: term.id,
                              sequence: term.sequence,
                              title: term.title ?? "",
                              content: term.content,
                          }))
                        : [emptyAgreementTerm()],
                );
                resetDirty();
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoadingAgreement(false);
            });

        return () => controller.abort();
    }, [isEditing, resetDirty, routeAgreementId, setAuthoritativeCurrency]);

    useEffect(() => {
        if (!reservationId) return;

        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) setError(null);
        });

        void getRentalReservation(reservationId, controller.signal)
            .then((resource) => {
                setReservation(resource);
                if (resource.customer) {
                    const nextCustomer = resource.customer as CustomerSummary;
                    setCustomer(nextCustomer);
                    if (!resource.currency) {
                        applyCurrencyDefault(nextCustomer.default_currency ?? null);
                    }
                }
                if (resource.currency) {
                    setAuthoritativeCurrency(resource.currency);
                }
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
    }, [reservationId, setAuthoritativeCurrency, applyCurrencyDefault]);
    const applyPartyDefaultCurrency = (
        party: CustomerSummary | SupplierSummary | null,
    ) => {
        applyCurrencyDefault(party?.default_currency ?? null);
    };
    const structuralFieldsLocked = Boolean(
        isEditing &&
            loadedAgreement &&
            ((loadedAgreement.allocations?.length ?? 0) > 0 ||
                (loadedAgreement.rate_versions?.length ?? 0) > 0 ||
                loadedAgreement.active_rate_version != null ||
                loadedAgreement.deposit_requirement != null),
    );
    const errorFor = (field: string) => fieldError(error, field);
    const partyValid = useMemo(
        () =>
            form.agreement_kind === RENTAL_AGREEMENT_KIND.customerRental
                ? Boolean(customer)
                : Boolean(supplier),
        [form.agreement_kind, customer, supplier],
    );
    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (isEditing && (!loadedAgreement || routeAgreementId === null)) return;

        setSaving(true);
        setError(null);
        try {
            const normalizedTerms = terms
                .map((term, index) => ({
                    id: term.id,
                    sequence: term.sequence ?? index + 1,
                    title: term.title.trim() || null,
                    content: term.content.trim(),
                    is_printable: true,
                }))
                .filter((term) => term.content !== "");
            const commonPayload = {
                agreement_date: form.agreement_date,
                executed_at: form.executed_at || null,
                starts_at: form.starts_at,
                ends_at: form.ends_at,
                legal_context: form.legal_context || null,
                rental_mode: form.rental_mode,
                billing_cycle: form.billing_cycle,
                billing_basis: form.billing_basis,
                proration_rule: form.proration_rule,
                customer_id:
                    form.agreement_kind === RENTAL_AGREEMENT_KIND.customerRental
                        ? customer?.id
                        : null,
                supplier_id:
                    form.agreement_kind === RENTAL_AGREEMENT_KIND.ownerSupply
                        ? supplier?.id
                        : null,
                currency_id: Number(currency?.id),
                payment_term_days:
                    form.payment_term_days === ""
                        ? null
                        : Number(form.payment_term_days),
                remarks: form.remarks,
                terms: normalizedTerms,
            };
            const draftUpdatePayload = structuralFieldsLocked
                ? {
                      agreement_date: form.agreement_date,
                      executed_at: form.executed_at || null,
                      legal_context: form.legal_context || null,
                      remarks: form.remarks,
                      terms: normalizedTerms,
                  }
                : commonPayload;
            const row = isEditing
                ? await updateRentalAgreement(
                      routeAgreementId,
                      loadedAgreement?.row_version ?? 0,
                      draftUpdatePayload,
                  )
                : await createRentalAgreement({
                      ...commonPayload,
                      agreement_kind: form.agreement_kind,
                      reservation_id: reservation?.id ?? undefined,
                      expected_reservation_version: reservation?.row_version ?? undefined,
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
                          form.agreement_kind ===
                              RENTAL_AGREEMENT_KIND.customerRental &&
                          Number(form.deposit_amount) > 0
                              ? {
                                    required_amount: form.deposit_amount,
                                    currency_id: Number(currency?.id),
                                    is_refundable: true,
                                }
                              : undefined,
                  });
            markSaved();
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
    const pageTitle =
        mode === "lessee"
            ? isEditing
                ? "Edit lessee agreement"
                : "New lessee agreement"
            : mode === "lessor"
            ? isEditing
                ? "Edit lessor agreement"
                : "New lessor agreement"
            : isEditing
              ? "Edit rental agreement"
              : "New rental agreement";
    const submitLabel =
        mode === "lessee"
            ? isEditing
                ? "Update lessee agreement"
                : "Create lessee agreement"
            : mode === "lessor"
            ? isEditing
                ? "Update lessor agreement"
                : "Create lessor agreement"
            : isEditing
              ? "Update agreement"
              : "Create agreement";

    if (loadingAgreement) {
        return (
            <RentalPage>
                <LoadingState />
            </RentalPage>
        );
    }

    return (
        <RentalPage>
            <ContentHeader
                title={pageTitle}
                description={
                    isEditing
                        ? "Update draft agreement details before activation."
                        : mode === "lessee"
                        ? "Create the customer-side rental agreement and its first immutable rate version."
                        : mode === "lessor"
                        ? "Create the supplier-side payable agreement and its first immutable rate version."
                        : "Create the lessee or lessor agreement and its first immutable rate version."
                }
            />
            <ErrorAlert error={error ?? currencyDefaultError} />
            <form onSubmit={submit} className="space-y-5">
                <Panel title="Agreement">
                    {structuralFieldsLocked && (
                        <div
                            className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                            role="status"
                        >
                            Party, period, billing, payment term and currency are
                            locked because dependent rental records already exist.
                        </div>
                    )}
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {mode === "standard" && (
                            <Select
                                label="Agreement kind"
                                value={form.agreement_kind}
                                disabled={reservation !== null || isEditing}
                                onChange={(e) => {
                                    setCustomer(null);
                                    setSupplier(null);
                                    setTerms([emptyAgreementTerm()]);
                                    applyCurrencyDefault(null);
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
                                error={errorFor("customer_id")}
                                disabled={structuralFieldsLocked}
                                required
                                onChange={(next) => {
                                    markDirty();
                                    setCustomer(next);
                                    applyPartyDefaultCurrency(next);
                                }}
                            />
                        ) : (
                            <SupplierLookupSelect
                                value={supplier}
                                error={errorFor("supplier_id")}
                                disabled={structuralFieldsLocked}
                                required
                                onChange={(next) => {
                                    markDirty();
                                    setSupplier(next);
                                    applyPartyDefaultCurrency(next);
                                }}
                            />
                        )}
                        <Input
                            label="Agreement date"
                            type="date"
                            required
                            value={form.agreement_date}
                            error={errorFor("agreement_date")}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    agreement_date: e.target.value,
                                })
                            }
                        />
                        <Input
                            label="Executed date"
                            type="date"
                            min={
                                form.agreement_date
                                    ? form.agreement_date
                                    : undefined
                            }
                            max={businessDateInputValue()}
                            value={form.executed_at}
                            error={errorFor("executed_at")}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    executed_at: e.target.value,
                                })
                            }
                            hint="Required before activation."
                        />
                        <Select
                            label="Legal context"
                            value={form.legal_context}
                            error={errorFor("legal_context")}
                            required
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    legal_context: e.target.value,
                                })
                            }
                            options={legalContextOptions}
                        />
                        <Input
                            label="Start"
                            type="datetime-local"
                            required
                            value={form.starts_at}
                            error={errorFor("starts_at")}
                            disabled={structuralFieldsLocked}
                            onChange={(e) =>
                                updateForm({ ...form, starts_at: e.target.value })
                            }
                        />
                        <Input
                            label="End"
                            type="datetime-local"
                            required
                            value={form.ends_at}
                            error={errorFor("ends_at")}
                            disabled={structuralFieldsLocked}
                            onChange={(e) =>
                                updateForm({ ...form, ends_at: e.target.value })
                            }
                        />
                        <Select
                            label="Rental mode"
                            value={form.rental_mode}
                            error={errorFor("rental_mode")}
                            disabled={structuralFieldsLocked}
                            required
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    rental_mode: e.target.value,
                                })
                            }
                            options={rentalModeOptions}
                        />
                        <Select
                            label="Billing cycle"
                            value={form.billing_cycle}
                            error={errorFor("billing_cycle")}
                            disabled={structuralFieldsLocked}
                            required
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    billing_cycle: e.target.value,
                                })
                            }
                            options={billingCycleOptions}
                        />
                        <Select
                            label="Billing basis"
                            value={form.billing_basis}
                            error={errorFor("billing_basis")}
                            disabled={structuralFieldsLocked}
                            required
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    billing_basis: e.target.value,
                                })
                            }
                            options={billingBasisOptions}
                        />
                        <RentalCurrencyLookupSelect
                            value={currency}
                            error={errorFor("currency_id")}
                            disabled={structuralFieldsLocked}
                            onChange={(next) => {
                                markDirty();
                                selectCurrency(next);
                            }}
                            required
                        />
                        <Input
                            label="Payment term days"
                            type="number"
                            min="0"
                            value={form.payment_term_days}
                            error={errorFor("payment_term_days")}
                            disabled={structuralFieldsLocked}
                            onChange={(e) =>
                                updateForm({
                                    ...form,
                                    payment_term_days: e.target.value,
                                })
                            }
                        />
                        {!isEditing && (
                            <>
                                <Input
                                    label="Included KM"
                                    type="number"
                                    min="0"
                                    step="0.000001"
                                    value={form.included_km}
                                    error={errorFor("rate_version.included_km")}
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
                                    error={errorFor("rate_version.excess_km_method")}
                                    required
                                    onChange={(e) =>
                                        updateForm({
                                            ...form,
                                            excess_km_method: e.target.value,
                                        })
                                    }
                                    options={excessKmMethodOptions}
                                />
                            </>
                        )}
                        {!isEditing && form.agreement_kind ===
                            RENTAL_AGREEMENT_KIND.customerRental && (
                            <Input
                                label="Security deposit"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.deposit_amount}
                                error={errorFor("deposit.required_amount")}
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
                            error={errorFor("remarks")}
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
                        Clauses can be completed before activation.
                    </p>
                    {errorFor("terms") && (
                        <p className="mb-4 text-sm text-rose-600">
                            {errorFor("terms")}
                        </p>
                    )}
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
                                        error={errorFor(`terms.${index}.title`)}
                                        onChange={(event) => {
                                            markDirty();
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
                                                    markDirty();
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
                                        maxLength={20000}
                                        value={term.content}
                                        error={errorFor(`terms.${index}.content`)}
                                        onChange={(event) => {
                                            markDirty();
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
                                markDirty();
                                setTerms((current) => [
                                    ...current,
                                    emptyAgreementTerm(nextTermSequence(current)),
                                ]);
                            }}
                        >
                            Add clause
                        </Button>
                    </div>
                </Panel>
                {!isEditing && (
                    <>
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
                                            markDirty();
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
                                            markDirty();
                                            setRates({
                                                ...rates,
                                                [code]: e.target.value,
                                            });
                                        }}
                                    />
                                ))}
                            </div>
                        </Panel>
                    </>
                )}
                <div className="flex justify-end">
                    <Button
                        type="submit"
                        loading={saving}
                        disabled={!partyValid || !currency}
                    >
                        {submitLabel}
                    </Button>
                </div>
            </form>
        </RentalPage>
    );
}
