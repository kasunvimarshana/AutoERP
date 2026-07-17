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
import { humanize, readableRelation } from "@/shared/utils/object";
import { parsePositiveInteger } from "@/shared/utils/routeParams";
import {
    RentalAgreementRateBuilder,
    selectedRateUnit,
    type RentalRateComponentDefinition,
} from "../components/RentalAgreementRateBuilder";
import { RentalPage } from "../components/RentalPage";
import { RentalCurrencyLookupSelect } from "../components/RentalLookups";
import { useRentalCurrencyDefault } from "../hooks/useRentalCurrencyDefault";
import { rentalOptions } from "../hooks/useRentalMetadata";
import {
    RENTAL_AGREEMENT_KIND,
    RENTAL_AGREEMENT_KIND_OPTIONS,
    agreementDetailPath,
    isRentalAgreementKind,
    type RentalAgreementPageMode,
} from "../rentalAgreementPresentation";
import {
    isLesseeAgreementKind,
    paymentTermsLabel,
    rentalAgreementPartyInputLabel,
    rentalAgreementRatePanelTitle,
    rentalPeriodEndHint,
    rentalPeriodStartHint,
} from "../rentalAgreementUi";
import {
    createRentalAgreement,
    getRentalAgreement,
    getRentalReservation,
    updateRentalAgreement,
} from "../vehicleRentalApi";
import type {
    RentalAgreement,
    RentalRateVersion,
    RentalReservation,
} from "../vehicleRentalTypes";

interface AgreementTermForm {
    id?: number;
    sequence?: number;
    title: string;
    content: string;
}

interface AgreementFormState {
    agreement_kind: string;
    agreement_date: string;
    executed_at: string;
    starts_at: string;
    ends_at: string;
    legal_context: string;
    rental_mode: string;
    billing_cycle: string;
    billing_basis: string;
    proration_rule: string;
    payment_term_days: string;
    included_km: string;
    excess_km_method: string;
    deposit_amount: string;
    remarks: string;
}

const TAX_TREATMENT = {
    taxable: "taxable",
    nonTaxable: "non_taxable",
} as const;

const RATE_VALUE_ZERO = "0";
const RATE_MULTIPLIER_ONE = "1";

const emptyAgreementTerm = (sequence?: number): AgreementTermForm => ({
    sequence,
    title: "",
    content: "",
});

function initialForm(agreementKind: string, isEditing: boolean): AgreementFormState {
    return {
        agreement_kind: agreementKind,
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
        included_km: RATE_VALUE_ZERO,
        excess_km_method: "",
        deposit_amount: RATE_VALUE_ZERO,
        remarks: "",
    };
}

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

function editableDraftRate(
    agreement: RentalAgreement,
): RentalRateVersion | null {
    return (
        agreement.rate_versions?.find((version) => version.status === "draft") ??
        null
    );
}

function resolveRateDefinitions(metadata: unknown): RentalRateComponentDefinition[] {
    const source = metadata as
        | {
              rate_component_definitions?: RentalRateComponentDefinition[];
              rate_component_codes?: string[];
              rate_units?: string[];
          }
        | undefined;
    if (source?.rate_component_definitions?.length) {
        return source.rate_component_definitions.map((definition) => ({
            ...definition,
            supported_units:
                definition.supported_units?.length
                    ? definition.supported_units
                    : [definition.unit],
        }));
    }

    if (
        source?.rate_component_codes?.length === 1 &&
        source.rate_units?.length === 1
    ) {
        return [
            {
                code: source.rate_component_codes[0],
                unit: source.rate_units[0],
                supported_units: [source.rate_units[0]],
                group: "core",
                required: true,
            },
        ];
    }

    return [];
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
    const [reservation, setReservation] =
        useState<RentalReservation | null>(null);
    const [loadedAgreement, setLoadedAgreement] =
        useState<RentalAgreement | null>(null);
    const [draftRate, setDraftRate] = useState<RentalRateVersion | null>(null);
    const [loadingAgreement, setLoadingAgreement] = useState(isEditing);
    const [form, setForm] = useState<AgreementFormState>(() =>
        initialForm(initialAgreementKind, isEditing),
    );
    const [rates, setRates] = useState<Record<string, string>>({});
    const [rateUnits, setRateUnits] = useState<Record<string, string>>({});
    const [taxTreatments, setTaxTreatments] = useState<Record<string, string>>({});
    const [enabledOptionalCodes, setEnabledOptionalCodes] = useState<Set<string>>(
        () => new Set(),
    );
    const [terms, setTerms] = useState<AgreementTermForm[]>([
        emptyAgreementTerm(),
    ]);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const { markDirty, markSaved, resetDirty } = useMutationFormGuard(saving);

    const updateForm: typeof setForm = (next) => {
        markDirty();
        setForm(next);
    };

    const componentDefinitions = useMemo(
        () => resolveRateDefinitions(metadata),
        [metadata],
    );
    const activeRateDefinitions = useMemo(
        () =>
            componentDefinitions.filter(
                (definition) =>
                    definition.required ||
                    Number(rates[definition.code] ?? RATE_VALUE_ZERO) > 0,
            ),
        [componentDefinitions, rates],
    );
    const rateSelectionsValid = useMemo(
        () =>
            activeRateDefinitions.every((definition) => {
                const unit = selectedRateUnit(definition, rateUnits);
                const taxTreatment = taxTreatments[definition.code] ?? "";

                return (
                    unit !== "" &&
                    (taxTreatment === TAX_TREATMENT.taxable ||
                        taxTreatment === TAX_TREATMENT.nonTaxable)
                );
            }),
        [activeRateDefinitions, rateUnits, taxTreatments],
    );
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
    const prorationRuleOptions = useMemo(
        () => rentalOptions(metadata?.proration_rules),
        [metadata?.proration_rules],
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
                    current.legal_context ||
                    metadataDefaults.legal_context ||
                    "",
                rental_mode:
                    current.rental_mode || metadataDefaults.rental_mode || "",
                billing_cycle:
                    current.billing_cycle ||
                    metadataDefaults.billing_cycle ||
                    "",
                billing_basis:
                    current.billing_basis ||
                    metadataDefaults.billing_basis ||
                    "",
                proration_rule:
                    current.proration_rule ||
                    metadataDefaults.proration_rule ||
                    "",
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
                const rate = editableDraftRate(resource);
                setLoadedAgreement(resource);
                setDraftRate(rate);
                setCustomer(
                    (resource.customer ?? null) as CustomerSummary | null,
                );
                setSupplier(
                    (resource.supplier ?? null) as SupplierSummary | null,
                );
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
                    included_km: rate?.included_km ?? RATE_VALUE_ZERO,
                    excess_km_method: rate?.excess_km_method ?? "",
                    deposit_amount:
                        resource.deposit_requirement?.required_amount ??
                        RATE_VALUE_ZERO,
                    remarks: resource.remarks ?? "",
                });
                setRates(
                    Object.fromEntries(
                        (rate?.components ?? []).map((component) => [
                            component.component_code,
                            component.rate,
                        ]),
                    ),
                );
                setRateUnits(
                    Object.fromEntries(
                        (rate?.components ?? []).map((component) => [
                            component.component_code,
                            component.unit,
                        ]),
                    ),
                );
                setTaxTreatments(
                    Object.fromEntries(
                        (rate?.components ?? []).map((component) => [
                            component.component_code,
                            component.is_taxable
                                ? TAX_TREATMENT.taxable
                                : TAX_TREATMENT.nonTaxable,
                        ]),
                    ),
                );
                setEnabledOptionalCodes(
                    new Set(
                        (rate?.components ?? [])
                            .filter(
                                (component) => Number(component.rate) > 0,
                            )
                            .map((component) => component.component_code),
                    ),
                );
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
                if (!controller.signal.aborted) {
                    setError(toApiError(requestError));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoadingAgreement(false);
            });

        return () => controller.abort();
    }, [
        isEditing,
        resetDirty,
        routeAgreementId,
        setAuthoritativeCurrency,
    ]);

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
                        applyCurrencyDefault(
                            nextCustomer.default_currency ?? null,
                        );
                    }
                }
                if (resource.currency) {
                    setAuthoritativeCurrency(resource.currency);
                }
                setForm((current) => ({
                    ...current,
                    agreement_kind: RENTAL_AGREEMENT_KIND.customerRental,
                    agreement_date:
                        current.agreement_date || businessDateInputValue(),
                    starts_at: toDateTimeLocal(resource.requested_start_at),
                    ends_at: toDateTimeLocal(resource.requested_end_at),
                    rental_mode: resource.rental_mode,
                    billing_cycle: resource.billing_cycle,
                }));
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) {
                    setError(toApiError(requestError));
                }
            });

        return () => controller.abort();
    }, [reservationId, setAuthoritativeCurrency, applyCurrencyDefault]);

    const hasCommittedRate = Boolean(
        loadedAgreement?.rate_versions?.some(
            (version) => version.status !== "draft",
        ),
    );
    const hasAllocation = (loadedAgreement?.allocations?.length ?? 0) > 0;
    const structuralFieldsLocked = Boolean(
        isEditing && (hasCommittedRate || hasAllocation),
    );
    const depositIdentityLocked = Boolean(
        loadedAgreement?.deposit_requirement &&
            (loadedAgreement.deposit_requirement.status !== "pending" ||
                (loadedAgreement.deposit_requirement.links?.length ?? 0) > 0),
    );
    const rateEditable =
        !isEditing || Boolean(draftRate && !structuralFieldsLocked);
    const showRatePanel =
        !isEditing || draftRate !== null || hasCommittedRate;
    const errorFor = (field: string) => fieldError(error, field);
    const isLesseeAgreement = isLesseeAgreementKind(form.agreement_kind);
    const partyValid = isLesseeAgreement ? Boolean(customer) : Boolean(supplier);
    const blockingIssues = agreementBlockingIssues({
        form,
        partyValid,
        hasCurrency: Boolean(currency),
        hasRateDefinitions: componentDefinitions.length > 0,
        requireRateDefinitions: !isEditing,
        requireExcessKmMethod: !isEditing,
        rateSelectionsValid: !rateEditable || rateSelectionsValid,
    });

    const rateVersionPayload = (version: RentalRateVersion | null) => ({
        ...(version
            ? { id: version.id, expected_version: version.row_version }
            : {}),
        effective_from: form.starts_at,
        effective_to: form.ends_at,
        driver_mode: form.rental_mode,
        billing_cycle: form.billing_cycle,
        billing_basis: form.billing_basis,
        proration_rule: form.proration_rule,
        excess_km_method: form.excess_km_method,
        included_km: form.included_km,
        currency_id: Number(currency?.id),
        components: activeRateDefinitions.map((definition, index) => ({
            component_code: definition.code,
            unit: selectedRateUnit(definition, rateUnits),
            rate: rates[definition.code] ?? RATE_VALUE_ZERO,
            multiplier: RATE_MULTIPLIER_ONE,
            calculation_order: index + 1,
            is_taxable:
                taxTreatments[definition.code] === TAX_TREATMENT.taxable,
        })),
    });

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (isEditing && (!loadedAgreement || routeAgreementId === null)) return;
        if (blockingIssues.length > 0) return;

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
                legal_context: form.legal_context,
                rental_mode: form.rental_mode,
                billing_cycle: form.billing_cycle,
                billing_basis: form.billing_basis,
                proration_rule: form.proration_rule,
                customer_id: isLesseeAgreement ? customer?.id : null,
                supplier_id: isLesseeAgreement ? null : supplier?.id,
                currency_id: Number(currency?.id),
                payment_term_days:
                    form.payment_term_days === ""
                        ? null
                        : Number(form.payment_term_days),
                remarks: form.remarks,
                terms: normalizedTerms,
            };
            const unlockedUpdatePayload = {
                ...commonPayload,
                ...(draftRate && rateEditable
                    ? { rate_version: rateVersionPayload(draftRate) }
                    : {}),
            };
            const lockedUpdatePayload = {
                agreement_date: form.agreement_date,
                executed_at: form.executed_at || null,
                legal_context: form.legal_context,
                remarks: form.remarks,
                terms: normalizedTerms,
            };
            const row = isEditing
                ? await updateRentalAgreement(
                      routeAgreementId,
                      loadedAgreement?.row_version ?? 0,
                      structuralFieldsLocked
                          ? lockedUpdatePayload
                          : unlockedUpdatePayload,
                  )
                : await createRentalAgreement({
                      ...commonPayload,
                      agreement_kind: form.agreement_kind,
                      reservation_id: reservation?.id ?? undefined,
                      expected_reservation_version:
                          reservation?.row_version ?? undefined,
                      rate_version: rateVersionPayload(null),
                      activate_rate_version: false,
                      deposit:
                          isLesseeAgreement &&
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
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

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
                description="Complete the contract, pricing policy and review details. The saved draft is activated only after a separate final review."
            />
            <ErrorAlert
                error={error ?? currencyDefaultError}
                title="Agreement could not be saved"
                inline
            />
            <form onSubmit={submit} className="space-y-5">
                <Panel title="1. Contract">
                    {structuralFieldsLocked && (
                        <div
                            className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                            role="status"
                        >
                            Party, period and commercial structure are locked because a
                            committed rate or vehicle allocation already exists.
                        </div>
                    )}
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {mode === "standard" && (
                            <Select
                                label="Agreement kind"
                                value={form.agreement_kind}
                                disabled={reservation !== null || isEditing}
                                onChange={(event) => {
                                    setCustomer(null);
                                    setSupplier(null);
                                    setTerms([emptyAgreementTerm()]);
                                    setRates({});
                                    setRateUnits({});
                                    setTaxTreatments({});
                                    setEnabledOptionalCodes(new Set());
                                    applyCurrencyDefault(null);
                                    updateForm({
                                        ...form,
                                        agreement_kind: event.target.value,
                                    });
                                }}
                                options={[...RENTAL_AGREEMENT_KIND_OPTIONS]}
                            />
                        )}
                        {isLesseeAgreement ? (
                            <CustomerLookupSelect
                                label={rentalAgreementPartyInputLabel(
                                    form.agreement_kind,
                                )}
                                value={customer}
                                error={errorFor("customer_id")}
                                disabled={
                                    structuralFieldsLocked || depositIdentityLocked
                                }
                                required
                                onChange={(next) => {
                                    markDirty();
                                    setCustomer(next);
                                    applyCurrencyDefault(
                                        next?.default_currency ?? null,
                                    );
                                }}
                            />
                        ) : (
                            <SupplierLookupSelect
                                label={rentalAgreementPartyInputLabel(
                                    form.agreement_kind,
                                )}
                                value={supplier}
                                error={errorFor("supplier_id")}
                                disabled={structuralFieldsLocked}
                                required
                                onChange={(next) => {
                                    markDirty();
                                    setSupplier(next);
                                    applyCurrencyDefault(
                                        next?.default_currency ?? null,
                                    );
                                }}
                            />
                        )}
                        <Input
                            label="Agreement date"
                            type="date"
                            required
                            value={form.agreement_date}
                            error={errorFor("agreement_date")}
                            hint="Date the contract is prepared."
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    agreement_date: event.target.value,
                                })
                            }
                        />
                        <Input
                            label="Executed date"
                            type="date"
                            min={form.agreement_date || undefined}
                            max={businessDateInputValue()}
                            value={form.executed_at}
                            error={errorFor("executed_at")}
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    executed_at: event.target.value,
                                })
                            }
                            hint="Date the contract is signed. Required before activation."
                        />
                        <Select
                            label="Legal context"
                            value={form.legal_context}
                            error={errorFor("legal_context")}
                            required
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    legal_context: event.target.value,
                                })
                            }
                            options={legalContextOptions}
                            hint="Select the approved legal context for the contract."
                        />
                        <Input
                            label="Start"
                            type="datetime-local"
                            required
                            value={form.starts_at}
                            error={errorFor("starts_at")}
                            disabled={structuralFieldsLocked}
                            hint={rentalPeriodStartHint(form.agreement_kind)}
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    starts_at: event.target.value,
                                })
                            }
                        />
                        <Input
                            label="End"
                            type="datetime-local"
                            required
                            value={form.ends_at}
                            error={errorFor("ends_at")}
                            disabled={structuralFieldsLocked}
                            hint={rentalPeriodEndHint(form.agreement_kind)}
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    ends_at: event.target.value,
                                })
                            }
                        />
                        <Select
                            label="Rental mode"
                            value={form.rental_mode}
                            error={errorFor("rental_mode")}
                            disabled={structuralFieldsLocked}
                            required
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    rental_mode: event.target.value,
                                })
                            }
                            options={rentalModeOptions}
                            hint="Driver-related rates appear only for with-driver contracts."
                        />
                        <RentalCurrencyLookupSelect
                            value={currency}
                            error={errorFor("currency_id")}
                            disabled={
                                structuralFieldsLocked || depositIdentityLocked
                            }
                            onChange={(next) => {
                                markDirty();
                                selectCurrency(next);
                            }}
                            required
                        />
                        <Input
                            label={paymentTermsLabel(form.agreement_kind)}
                            type="number"
                            min="0"
                            value={form.payment_term_days}
                            error={errorFor("payment_term_days")}
                            disabled={structuralFieldsLocked}
                            hint="Leave blank only when payment terms are not specified."
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    payment_term_days: event.target.value,
                                })
                            }
                        />
                        {!isEditing && isLesseeAgreement && (
                            <Input
                                label="Security deposit"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.deposit_amount}
                                error={errorFor("deposit.required_amount")}
                                hint="Required deposit amount only. Receipt and refund are handled in Security Deposits and Payments."
                                onChange={(event) =>
                                    updateForm({
                                        ...form,
                                        deposit_amount: event.target.value,
                                    })
                                }
                            />
                        )}
                    </div>
                </Panel>

                <Panel title="2. Billing policy">
                    <p className="mb-4 text-sm text-slate-600">
                        These settings define how partial periods and included distance
                        are calculated. Use only the approved contract policy.
                    </p>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Select
                            label="Billing cycle"
                            value={form.billing_cycle}
                            error={errorFor("billing_cycle")}
                            disabled={structuralFieldsLocked}
                            required
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    billing_cycle: event.target.value,
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
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    billing_basis: event.target.value,
                                })
                            }
                            options={billingBasisOptions}
                        />
                        <Select
                            label="Proration rule"
                            value={form.proration_rule}
                            error={errorFor("proration_rule")}
                            disabled={structuralFieldsLocked}
                            required
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    proration_rule: event.target.value,
                                })
                            }
                            options={prorationRuleOptions}
                            hint="Contract-approved partial-period rule."
                        />
                        <Input
                            label="Included KM"
                            type="number"
                            min="0"
                            step="0.000001"
                            value={form.included_km}
                            error={errorFor("rate_version.included_km")}
                            disabled={!rateEditable}
                            hint="Distance included in the selected billing policy."
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    included_km: event.target.value,
                                })
                            }
                        />
                        <Select
                            label="Excess KM method"
                            value={form.excess_km_method}
                            error={errorFor(
                                "rate_version.excess_km_method",
                            )}
                            disabled={!rateEditable}
                            required
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    excess_km_method: event.target.value,
                                })
                            }
                            options={excessKmMethodOptions}
                        />
                    </div>
                </Panel>

                {showRatePanel && (
                    <Panel
                        title={rentalAgreementRatePanelTitle(
                            form.agreement_kind,
                        )}
                    >
                        {!rateEditable ? (
                            <p className="text-sm text-slate-600">
                                Committed rate history is immutable. Create a governed
                                successor rate version from the agreement details when a
                                future rate change is required.
                            </p>
                        ) : (
                            <RentalAgreementRateBuilder
                                agreementKind={form.agreement_kind}
                                rentalMode={form.rental_mode}
                                definitions={componentDefinitions}
                                rates={rates}
                                units={rateUnits}
                                taxTreatments={taxTreatments}
                                enabledOptionalCodes={enabledOptionalCodes}
                                currencyLabel={
                                    currency?.code ?? currency?.name ?? ""
                                }
                                editable={rateEditable}
                                onEnableOptional={(code) => {
                                    markDirty();
                                    setEnabledOptionalCodes((current) => {
                                        const next = new Set(current);
                                        next.add(code);
                                        return next;
                                    });
                                }}
                                onDisableOptional={(code) => {
                                    markDirty();
                                    setEnabledOptionalCodes((current) => {
                                        const next = new Set(current);
                                        next.delete(code);
                                        return next;
                                    });
                                    setRates((current) => ({
                                        ...current,
                                        [code]: RATE_VALUE_ZERO,
                                    }));
                                    setRateUnits((current) => {
                                        const next = { ...current };
                                        delete next[code];
                                        return next;
                                    });
                                    setTaxTreatments((current) => {
                                        const next = { ...current };
                                        delete next[code];
                                        return next;
                                    });
                                }}
                                onRateChange={(code, value) => {
                                    markDirty();
                                    setRates((current) => ({
                                        ...current,
                                        [code]: value,
                                    }));
                                }}
                                onUnitChange={(code, value) => {
                                    markDirty();
                                    setRateUnits((current) => ({
                                        ...current,
                                        [code]: value,
                                    }));
                                }}
                                onTaxTreatmentChange={(code, value) => {
                                    markDirty();
                                    setTaxTreatments((current) => ({
                                        ...current,
                                        [code]: value,
                                    }));
                                }}
                            />
                        )}
                        {rateEditable && !rateSelectionsValid && (
                            <p className="mt-4 text-sm text-amber-700" role="status">
                                Select a unit and Tax treatment for every required or
                                non-zero rate component before saving.
                            </p>
                        )}
                    </Panel>
                )}

                <Panel title="3. Review and agreement terms">
                    <AgreementReviewSummary
                        agreementKind={form.agreement_kind}
                        party={customer ?? supplier}
                        startsAt={form.starts_at}
                        endsAt={form.ends_at}
                        rentalMode={form.rental_mode}
                        billingCycle={form.billing_cycle}
                        billingBasis={form.billing_basis}
                        prorationRule={form.proration_rule}
                        includedKm={form.included_km}
                        currency={currency?.code ?? currency?.name}
                        activeRateCount={activeRateDefinitions.length}
                        depositAmount={
                            isLesseeAgreement ? form.deposit_amount : undefined
                        }
                    />

                    <div className="mt-5">
                        <Textarea
                            label="Remarks"
                            value={form.remarks}
                            error={errorFor("remarks")}
                            hint="Agreement remarks are included in the printed activation snapshot. Do not enter private internal notes here."
                            onChange={(event) =>
                                updateForm({
                                    ...form,
                                    remarks: event.target.value,
                                })
                            }
                        />
                    </div>

                    <div className="mt-5 border-t border-slate-200 pt-5">
                        <h3 className="font-semibold text-slate-900">
                            Optional agreement-specific clauses
                        </h3>
                        <p className="mt-1 text-sm text-slate-600">
                            Clauses are optional and remain editable until activation.
                            Add only terms that must be printed in this agreement.
                        </p>
                        {errorFor("terms") && (
                            <p className="mt-3 text-sm text-rose-600">
                                {errorFor("terms")}
                            </p>
                        )}
                        <div className="mt-4 space-y-4">
                            {terms.map((term, index) => (
                                <div
                                    key={term.id ?? index}
                                    className="rounded-lg border border-slate-200 p-4"
                                >
                                    <div className="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]">
                                        <Input
                                            label={`Clause ${index + 1} title`}
                                            value={term.title}
                                            error={errorFor(
                                                `terms.${index}.title`,
                                            )}
                                            onChange={(event) => {
                                                markDirty();
                                                setTerms((current) =>
                                                    current.map(
                                                        (row, rowIndex) =>
                                                            rowIndex === index
                                                                ? {
                                                                      ...row,
                                                                      title: event
                                                                          .target.value,
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
                                                    aria-label={`Remove clause ${index + 1}`}
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
                                            error={errorFor(
                                                `terms.${index}.content`,
                                            )}
                                            onChange={(event) => {
                                                markDirty();
                                                setTerms((current) =>
                                                    current.map(
                                                        (row, rowIndex) =>
                                                            rowIndex === index
                                                                ? {
                                                                      ...row,
                                                                      content:
                                                                          event
                                                                              .target
                                                                              .value,
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
                                        emptyAgreementTerm(
                                            nextTermSequence(current),
                                        ),
                                    ]);
                                }}
                            >
                                Add clause
                            </Button>
                        </div>
                    </div>
                </Panel>

                <div className="sticky bottom-0 z-20 rounded-xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            {blockingIssues.length > 0 ? (
                                <div role="status" aria-live="polite">
                                    <p className="text-sm font-semibold text-amber-800">
                                        Complete before saving:
                                    </p>
                                    <ul className="mt-1 list-disc pl-5 text-sm text-amber-700">
                                        {blockingIssues.map((issue) => (
                                            <li key={issue}>{issue}</li>
                                        ))}
                                    </ul>
                                </div>
                            ) : (
                                <p className="text-sm text-emerald-700">
                                    Draft requirements are complete. Activation remains a
                                    separate final review.
                                </p>
                            )}
                        </div>
                        <Button
                            type="submit"
                            loading={saving}
                            disabled={blockingIssues.length > 0}
                        >
                            {submitLabel}
                        </Button>
                    </div>
                </div>
            </form>
        </RentalPage>
    );
}

function agreementBlockingIssues({
    form,
    partyValid,
    hasCurrency,
    hasRateDefinitions,
    requireRateDefinitions,
    requireExcessKmMethod,
    rateSelectionsValid,
}: {
    form: AgreementFormState;
    partyValid: boolean;
    hasCurrency: boolean;
    hasRateDefinitions: boolean;
    requireRateDefinitions: boolean;
    requireExcessKmMethod: boolean;
    rateSelectionsValid: boolean;
}): string[] {
    const issues: string[] = [];
    if (!partyValid) issues.push("Select the agreement party.");
    if (!form.agreement_date) issues.push("Enter the agreement date.");
    if (!form.starts_at) issues.push("Enter the contract start.");
    if (!form.ends_at) issues.push("Enter the contract end.");
    if (!form.legal_context) issues.push("Select the legal context.");
    if (!form.rental_mode) issues.push("Select the rental mode.");
    if (!form.billing_cycle) issues.push("Select the billing cycle.");
    if (!form.billing_basis) issues.push("Select the billing basis.");
    if (!form.proration_rule) issues.push("Select the proration rule.");
    if (requireExcessKmMethod && !form.excess_km_method) {
        issues.push("Select the excess KM method.");
    }
    if (!hasCurrency) issues.push("Select the contract currency.");
    if (requireRateDefinitions && !hasRateDefinitions) {
        issues.push("Reload the unavailable rate definitions.");
    }
    if (!rateSelectionsValid) {
        issues.push("Complete unit and Tax treatment for active rates.");
    }

    return issues;
}

function AgreementReviewSummary({
    agreementKind,
    party,
    startsAt,
    endsAt,
    rentalMode,
    billingCycle,
    billingBasis,
    prorationRule,
    includedKm,
    currency,
    activeRateCount,
    depositAmount,
}: {
    agreementKind: string;
    party: CustomerSummary | SupplierSummary | null;
    startsAt: string;
    endsAt: string;
    rentalMode: string;
    billingCycle: string;
    billingBasis: string;
    prorationRule: string;
    includedKm: string;
    currency?: string;
    activeRateCount: number;
    depositAmount?: string;
}) {
    const items = [
        {
            label: rentalAgreementPartyInputLabel(agreementKind),
            value: party ? readableRelation(party) : "Not selected",
        },
        {
            label: "Contract period",
            value:
                startsAt && endsAt
                    ? `${startsAt.replace("T", " ")} – ${endsAt.replace("T", " ")}`
                    : "Incomplete",
        },
        {
            label: "Rental mode",
            value: rentalMode ? humanize(rentalMode) : "Not selected",
        },
        {
            label: "Billing policy",
            value:
                billingCycle && billingBasis && prorationRule
                    ? `${humanize(billingCycle)} / ${humanize(billingBasis)} / ${humanize(prorationRule)}`
                    : "Incomplete",
        },
        {
            label: "Included distance",
            value: `${includedKm || RATE_VALUE_ZERO} KM`,
        },
        {
            label: "Active rates",
            value: `${activeRateCount} in ${currency ?? "contract currency"}`,
        },
    ];

    if (depositAmount !== undefined) {
        items.push({
            label: "Security deposit requirement",
            value:
                Number(depositAmount) > 0
                    ? `${currency ?? ""} ${depositAmount}`.trim()
                    : "Not required",
        });
    }

    return (
        <dl className="grid gap-4 rounded-lg bg-slate-50 p-4 md:grid-cols-2 xl:grid-cols-3">
            {items.map((item) => (
                <div key={item.label}>
                    <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        {item.label}
                    </dt>
                    <dd className="mt-1 text-sm text-slate-900">
                        {item.value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
