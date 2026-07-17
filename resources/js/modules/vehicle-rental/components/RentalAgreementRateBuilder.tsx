import { Button } from "@/shared/components/Button";
import { Input } from "@/shared/components/Input";
import { Select } from "@/shared/components/Select";
import { rentalOptions } from "../hooks/useRentalMetadata";
import {
    isDefaultVisibleRateComponent,
    isDriverRateComponent,
    rateComponentAppliesToMode,
    rentalAgreementRateLabel,
    RENTAL_RATE_COMPONENT,
} from "../rentalAgreementUi";

export interface RentalRateComponentDefinition {
    code: string;
    unit: string;
    supported_units?: string[];
    group: "core" | "event";
    required: boolean;
}

interface RentalAgreementRateBuilderProps {
    agreementKind: string;
    rentalMode: string;
    definitions: RentalRateComponentDefinition[];
    rates: Record<string, string>;
    units: Record<string, string>;
    taxTreatments: Record<string, string>;
    enabledOptionalCodes: ReadonlySet<string>;
    currencyLabel: string;
    editable: boolean;
    onEnableOptional: (code: string) => void;
    onDisableOptional: (code: string) => void;
    onRateChange: (code: string, value: string) => void;
    onUnitChange: (code: string, value: string) => void;
    onTaxTreatmentChange: (code: string, value: string) => void;
}

const TAX_TREATMENT_OPTIONS = [
    { value: "taxable", label: "Taxable" },
    { value: "non_taxable", label: "Non-taxable" },
] as const;

export function RentalAgreementRateBuilder({
    agreementKind,
    rentalMode,
    definitions,
    rates,
    units,
    taxTreatments,
    enabledOptionalCodes,
    currencyLabel,
    editable,
    onEnableOptional,
    onDisableOptional,
    onRateChange,
    onUnitChange,
    onTaxTreatmentChange,
}: RentalAgreementRateBuilderProps) {
    const visibleDefinitions = definitions.filter((definition) =>
        isVisibleDefinition(
            definition,
            rentalMode,
            rates,
            enabledOptionalCodes,
        ),
    );
    const optionalDefinitions = definitions.filter(
        (definition) =>
            !visibleDefinitions.some((visible) => visible.code === definition.code)
            && rateComponentAppliesToMode(definition.code, rentalMode),
    );
    const coreDefinitions = visibleDefinitions.filter(
        (definition) => definition.group === "core",
    );
    const additionalDefinitions = visibleDefinitions.filter(
        (definition) => definition.group === "event",
    );

    if (definitions.length === 0) {
        return (
            <p className="text-sm text-rose-600" role="alert">
                Rate definitions are unavailable. Reload before saving this draft.
            </p>
        );
    }

    return (
        <div className="space-y-5">
            <p className="text-sm text-slate-600">
                Only contract-relevant rates are shown. A zero optional rate is not
                saved. Every active rate must have an approved unit and Tax
                treatment.
            </p>

            <RateGroup
                title="Contract rates"
                agreementKind={agreementKind}
                definitions={coreDefinitions}
                rates={rates}
                units={units}
                taxTreatments={taxTreatments}
                currencyLabel={currencyLabel}
                editable={editable}
                enabledOptionalCodes={enabledOptionalCodes}
                onDisableOptional={onDisableOptional}
                onRateChange={onRateChange}
                onUnitChange={onUnitChange}
                onTaxTreatmentChange={onTaxTreatmentChange}
            />

            {additionalDefinitions.length > 0 && (
                <RateGroup
                    title="Additional charges"
                    agreementKind={agreementKind}
                    definitions={additionalDefinitions}
                    rates={rates}
                    units={units}
                    taxTreatments={taxTreatments}
                    currencyLabel={currencyLabel}
                    editable={editable}
                    enabledOptionalCodes={enabledOptionalCodes}
                    onDisableOptional={onDisableOptional}
                    onRateChange={onRateChange}
                    onUnitChange={onUnitChange}
                    onTaxTreatmentChange={onTaxTreatmentChange}
                />
            )}

            {editable && optionalDefinitions.length > 0 && (
                <div className="max-w-md rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                    <Select
                        label="Add optional charge"
                        value=""
                        onChange={(event) => {
                            if (event.target.value) {
                                onEnableOptional(event.target.value);
                            }
                        }}
                        options={optionalDefinitions.map((definition) => ({
                            value: definition.code,
                            label: rentalAgreementRateLabel(
                                agreementKind,
                                definition.code,
                            ),
                        }))}
                        hint="Add only charges that are part of this contract."
                    />
                </div>
            )}
        </div>
    );
}

function RateGroup({
    title,
    agreementKind,
    definitions,
    rates,
    units,
    taxTreatments,
    currencyLabel,
    editable,
    enabledOptionalCodes,
    onDisableOptional,
    onRateChange,
    onUnitChange,
    onTaxTreatmentChange,
}: {
    title: string;
    agreementKind: string;
    definitions: RentalRateComponentDefinition[];
    rates: Record<string, string>;
    units: Record<string, string>;
    taxTreatments: Record<string, string>;
    currencyLabel: string;
    editable: boolean;
    enabledOptionalCodes: ReadonlySet<string>;
    onDisableOptional: (code: string) => void;
    onRateChange: (code: string, value: string) => void;
    onUnitChange: (code: string, value: string) => void;
    onTaxTreatmentChange: (code: string, value: string) => void;
}) {
    if (definitions.length === 0) return null;

    return (
        <section aria-labelledby={`rate-group-${title.replaceAll(" ", "-")}`}>
            <h3
                id={`rate-group-${title.replaceAll(" ", "-")}`}
                className="mb-3 text-sm font-semibold text-slate-800"
            >
                {title}
            </h3>
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {definitions.map((definition) => {
                    const label = rentalAgreementRateLabel(
                        agreementKind,
                        definition.code,
                    );
                    const supportedUnits =
                        definition.supported_units ?? [definition.unit];
                    const rate = rates[definition.code] ?? "0";
                    const active = definition.required || Number(rate) > 0;
                    const unit = selectedRateUnit(definition, units);
                    const removable =
                        enabledOptionalCodes.has(definition.code)
                        && !definition.required
                        && !isDefaultVisibleRateComponent(definition.code)
                        && !isDriverRateComponent(definition.code);
                    const isBaseRate =
                        definition.code === RENTAL_RATE_COMPONENT.baseRental;

                    return (
                        <fieldset
                            key={definition.code}
                            className="rounded-lg border border-slate-200 p-4"
                        >
                            <legend className="px-1 text-sm font-semibold text-slate-800">
                                {label}
                            </legend>
                            <p className="mb-3 text-xs text-slate-500">
                                {currencyLabel || "Contract currency"}
                            </p>
                            <Input
                                label="Rate"
                                type="number"
                                min="0"
                                step="0.000001"
                                required={definition.required}
                                value={rate}
                                disabled={!editable}
                                onChange={(event) =>
                                    onRateChange(
                                        definition.code,
                                        event.target.value,
                                    )
                                }
                            />
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                {supportedUnits.length === 1 ? (
                                    <Input
                                        label={isBaseRate ? "Unit" : `${label} unit`}
                                        value={supportedUnits[0].replaceAll("_", " ")}
                                        readOnly
                                    />
                                ) : (
                                    <Select
                                        label={isBaseRate ? "Unit" : `${label} unit`}
                                        value={unit}
                                        required={active}
                                        disabled={!editable}
                                        onChange={(event) =>
                                            onUnitChange(
                                                definition.code,
                                                event.target.value,
                                            )
                                        }
                                        options={rentalOptions(supportedUnits)}
                                    />
                                )}
                                <Select
                                    label={
                                        isBaseRate
                                            ? "Tax treatment"
                                            : `${label} Tax treatment`
                                    }
                                    value={taxTreatments[definition.code] ?? ""}
                                    required={active}
                                    disabled={!editable}
                                    onChange={(event) =>
                                        onTaxTreatmentChange(
                                            definition.code,
                                            event.target.value,
                                        )
                                    }
                                    options={[...TAX_TREATMENT_OPTIONS]}
                                />
                            </div>
                            {editable && removable && (
                                <div className="mt-3 flex justify-end">
                                    <Button
                                        variant="ghost"
                                        onClick={() =>
                                            onDisableOptional(definition.code)
                                        }
                                    >
                                        Remove {label}
                                    </Button>
                                </div>
                            )}
                        </fieldset>
                    );
                })}
            </div>
        </section>
    );
}

function isVisibleDefinition(
    definition: RentalRateComponentDefinition,
    rentalMode: string,
    rates: Record<string, string>,
    enabledOptionalCodes: ReadonlySet<string>,
): boolean {
    const hasValue = Number(rates[definition.code] ?? "0") > 0;
    if (definition.required || hasValue || enabledOptionalCodes.has(definition.code)) {
        return true;
    }
    if (isDefaultVisibleRateComponent(definition.code)) return true;

    return isDriverRateComponent(definition.code)
        && rateComponentAppliesToMode(definition.code, rentalMode);
}

export function selectedRateUnit(
    definition: RentalRateComponentDefinition,
    selectedUnits: Record<string, string>,
): string {
    const explicit = selectedUnits[definition.code];
    if (explicit) return explicit;

    const supportedUnits = definition.supported_units ?? [definition.unit];
    return supportedUnits.length === 1 ? supportedUnits[0] : "";
}
