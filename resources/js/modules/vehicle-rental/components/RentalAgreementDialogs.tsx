import { useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { notifySuccess } from '@/shared/notifications/appToast';
import { createRentalAgreement, createRentalRateVersion, getRentalAgreementFormLookups, updateRentalAgreement } from '../vehicleRentalApi';
import type {
    RentalAgreement,
    RentalAgreementKind,
    RentalAgreementPayload,
    RentalBillingBasis,
    RentalRateLine,
    RentalRateVersion,
    RentalReference,
} from '../vehicleRentalTypes';
import { defaultRentalRate, normalizeRatesForAgreement, RentalRateEditor } from './RentalRateEditor';
import {
    RentalCurrencyLookup,
    RentalCustomerLookup,
    RentalSupplierLookup,
    type RentalLookupOption,
} from './VehicleRentalLookups';

interface AgreementFormState {
    kind: RentalAgreementKind;
    customer: RentalReference | null;
    supplier: RentalReference | null;
    agreementNumber: string;
    executedAt: string;
    startsOn: string;
    endsOn: string;
    billingBasis: RentalBillingBasis;
    currency: RentalReference | null;
    taxGroupId: string;
    includedKm: string;
    depositRequired: boolean;
    depositAmount: string;
    paymentTermsDays: string;
    terms: string;
    notes: string;
    rates: RentalRateLine[];
}

interface RentalAgreementDialogProps {
    open: boolean;
    agreement: RentalAgreement | null;
    kind?: RentalAgreementKind;
    onClose: () => void;
    onSaved: (agreement: RentalAgreement) => void;
}

export function RentalAgreementDialog(props: RentalAgreementDialogProps) {
    const identity = `${props.open ? 'open' : 'closed'}:${props.agreement?.id ?? 'new'}:${props.agreement?.row_version ?? 0}:${props.kind ?? 'selectable'}`;
    return <RentalAgreementDialogForm key={identity} {...props} />;
}

function RentalAgreementDialogForm({
    open,
    agreement,
    kind,
    onClose,
    onSaved,
}: RentalAgreementDialogProps) {
    const [state, setState] = useState<AgreementFormState>(() => initialAgreementState(agreement, kind));
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const formLookups = useApi((signal) => getRentalAgreementFormLookups(signal), [], open);
    const fixedKind = agreement?.kind ?? kind;
    const taxGroupOptions = (formLookups.data?.tax_groups ?? [])
        .map((group) => ({ value: group.id, label: [group.code, group.name].filter(Boolean).join(' - ') }));

    const setKind = (nextKind: RentalAgreementKind) => {
        setState((current) => ({
            ...current,
            kind: nextKind,
            customer: nextKind === 'customer' ? current.customer : null,
            supplier: nextKind === 'owner' ? current.supplier : null,
            depositRequired: nextKind === 'customer' ? current.depositRequired : false,
            depositAmount: nextKind === 'customer' && current.depositRequired ? current.depositAmount : '0',
            rates: normalizeRatesForAgreement(current.rates, nextKind, current.billingBasis),
        }));
    };

    const setBillingBasis = (billingBasis: RentalBillingBasis) => {
        setState((current) => ({
            ...current,
            billingBasis,
            rates: normalizeRatesForAgreement(current.rates, current.kind, billingBasis),
        }));
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        try {
            const payload = agreementPayload(state, agreement?.row_version);
            const saved = agreement
                ? await updateRentalAgreement(agreement.id, payload)
                : await createRentalAgreement(payload);
            notifySuccess(agreement ? 'Rental agreement updated successfully.' : 'Rental agreement created successfully.');
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    const sideLabel = state.kind === 'owner' ? 'Owner / supplier agreement' : 'Customer agreement';

    return (
        <Modal open={open} title={agreement ? `Edit ${agreement.agreement_number}` : `New ${sideLabel.toLowerCase()}`} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-5" onSubmit={(event) => void submit(event)}>
                <ErrorAlert error={error ?? formLookups.error} inline />
                <div className="grid gap-4 md:grid-cols-2">
                    {!fixedKind && (
                        <Select
                            label="Agreement side"
                            value={state.kind}
                            required
                            options={[{ value: 'customer', label: 'Customer agreement' }, { value: 'owner', label: 'Owner / supplier agreement' }]}
                            error={fieldError(error, 'kind')}
                            onChange={(event) => setKind(event.target.value as RentalAgreementKind)}
                        />
                    )}
                    <Input
                        label="Agreement number"
                        hint="Leave blank to use the configured Vehicle Rental number sequence."
                        value={state.agreementNumber}
                        disabled={Boolean(agreement)}
                        error={fieldError(error, 'agreement_number')}
                        onChange={(event) => setState((current) => ({ ...current, agreementNumber: event.target.value }))}
                    />
                    {state.kind === 'customer' ? (
                        <RentalCustomerLookup
                            value={state.customer}
                            required
                            onChange={(value: RentalLookupOption | null) => setState((current) => ({ ...current, customer: value }))}
                            error={fieldError(error, 'customer_id')}
                        />
                    ) : (
                        <RentalSupplierLookup
                            value={state.supplier}
                            required
                            onChange={(value: RentalLookupOption | null) => setState((current) => ({ ...current, supplier: value }))}
                            error={fieldError(error, 'supplier_id')}
                        />
                    )}
                    <RentalCurrencyLookup
                        value={state.currency}
                        required
                        onChange={(value: RentalLookupOption | null) => setState((current) => ({ ...current, currency: value }))}
                        error={fieldError(error, 'currency_id')}
                    />
                    <Input label="Executed on" type="date" value={state.executedAt} error={fieldError(error, 'executed_at')} onChange={(event) => setState((current) => ({ ...current, executedAt: event.target.value }))} />
                    <Select label="Billing basis" value={state.billingBasis} required options={[{ value: 'daily', label: 'Daily' }, { value: 'monthly', label: 'Monthly' }]} error={fieldError(error, 'billing_basis')} onChange={(event) => setBillingBasis(event.target.value as RentalBillingBasis)} />
                    <Input label="Starts on" type="date" required value={state.startsOn} error={fieldError(error, 'starts_on')} onChange={(event) => setState((current) => ({ ...current, startsOn: event.target.value }))} />
                    <Input label="Ends on" type="date" min={state.startsOn || undefined} value={state.endsOn} error={fieldError(error, 'ends_on')} onChange={(event) => setState((current) => ({ ...current, endsOn: event.target.value }))} />
                    <Select label="Tax group" value={state.taxGroupId} placeholder="No tax group" options={taxGroupOptions} error={fieldError(error, 'tax_group_id')} onChange={(event) => setState((current) => ({ ...current, taxGroupId: event.target.value }))} />
                    <Input label="Included KM" type="number" min="0" step="0.000001" value={state.includedKm} error={fieldError(error, 'included_km')} onChange={(event) => setState((current) => ({ ...current, includedKm: event.target.value }))} />
                    <Input label="Payment terms (days)" type="number" min="0" max="3650" value={state.paymentTermsDays} error={fieldError(error, 'payment_terms_days')} onChange={(event) => setState((current) => ({ ...current, paymentTermsDays: event.target.value }))} />
                    {state.kind === 'customer' && (
                        <div className="space-y-3">
                            <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={state.depositRequired}
                                    onChange={(event) => setState((current) => ({
                                        ...current,
                                        depositRequired: event.target.checked,
                                        depositAmount: event.target.checked ? current.depositAmount : '0',
                                    }))}
                                />
                                Security deposit required
                            </label>
                            <Input label="Deposit amount" type="number" min="0" step="0.000001" disabled={!state.depositRequired} required={state.depositRequired} value={state.depositAmount} error={fieldError(error, 'deposit_amount')} onChange={(event) => setState((current) => ({ ...current, depositAmount: event.target.value }))} />
                        </div>
                    )}
                </div>
                <RentalRateEditor rates={state.rates} onChange={(rates) => setState((current) => ({ ...current, rates }))} kind={state.kind} billingBasis={state.billingBasis} error={error} disabled={submitting} />
                <div className="grid gap-4 md:grid-cols-2">
                    <Textarea label="Terms" value={state.terms} error={fieldError(error, 'terms')} onChange={(event) => setState((current) => ({ ...current, terms: event.target.value }))} />
                    <Textarea label="Notes" value={state.notes} error={fieldError(error, 'notes')} onChange={(event) => setState((current) => ({ ...current, notes: event.target.value }))} />
                </div>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{agreement ? 'Save agreement' : 'Create agreement'}</Button>
                </div>
            </form>
        </Modal>
    );
}

interface RentalRateVersionDialogProps {
    open: boolean;
    agreement: RentalAgreement | null;
    onClose: () => void;
    onSaved: (agreement: RentalAgreement) => void;
}

export function RentalRateVersionDialog(props: RentalRateVersionDialogProps) {
    const identity = `${props.open ? 'open' : 'closed'}:${props.agreement?.id ?? 'none'}:${props.agreement?.row_version ?? 0}`;
    return <RentalRateVersionDialogForm key={identity} {...props} />;
}

function RentalRateVersionDialogForm({
    open,
    agreement,
    onClose,
    onSaved,
}: RentalRateVersionDialogProps) {
    const [effectiveFrom, setEffectiveFrom] = useState('');
    const [rates, setRates] = useState<RentalRateLine[]>(() =>
        (latestRateVersion(agreement)?.rates ?? []).map((rate) => ({ ...rate, id: undefined })),
    );
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!agreement) return;
        setSubmitting(true);
        setError(null);
        try {
            const saved = await createRentalRateVersion(agreement.id, {
                effective_from: effectiveFrom,
                rates,
                expected_version: agreement.row_version,
            });
            notifySuccess('Successor rental rate version created successfully.');
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open={open} title={`New rates for ${agreement?.agreement_number ?? 'agreement'}`} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-5" onSubmit={(event) => void submit(event)}>
                <ErrorAlert error={error} inline />
                <Input label="Effective from" type="date" required min={successorMinimumDate(agreement)} max={agreement?.ends_on ?? undefined} value={effectiveFrom} error={fieldError(error, 'effective_from')} onChange={(event) => setEffectiveFrom(event.target.value)} />
                {agreement && <RentalRateEditor rates={rates} onChange={setRates} kind={agreement.kind} billingBasis={agreement.billing_basis} error={error} disabled={submitting} />}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>Create rate version</Button>
                </div>
            </form>
        </Modal>
    );
}

function initialAgreementState(agreement: RentalAgreement | null, requestedKind?: RentalAgreementKind): AgreementFormState {
    const billingBasis = agreement?.billing_basis ?? 'daily';
    const kind = agreement?.kind ?? requestedKind ?? 'customer';
    const existingRates = latestRateVersion(agreement)?.rates ?? [defaultRentalRate(billingBasis)];
    return {
        kind,
        customer: agreement?.customer ?? null,
        supplier: agreement?.supplier ?? null,
        agreementNumber: agreement?.agreement_number ?? '',
        executedAt: agreement?.executed_at ?? '',
        startsOn: agreement?.starts_on ?? '',
        endsOn: agreement?.ends_on ?? '',
        billingBasis,
        currency: agreement?.currency ?? null,
        taxGroupId: agreement?.tax_group?.id ? String(agreement.tax_group.id) : '',
        includedKm: agreement?.included_km ?? '0',
        depositRequired: agreement?.deposit_required ?? false,
        depositAmount: agreement?.deposit_amount ?? '0',
        paymentTermsDays: String(agreement?.payment_terms_days ?? 0),
        terms: agreement?.terms ?? '',
        notes: agreement?.notes ?? '',
        rates: normalizeRatesForAgreement(existingRates.map((rate) => ({ ...rate })), kind, billingBasis),
    };
}

function latestRateVersion(agreement: RentalAgreement | null): RentalRateVersion | undefined {
    return agreement?.rate_versions?.reduce<RentalRateVersion | undefined>(
        (latest, candidate) => !latest || candidate.version_number > latest.version_number ? candidate : latest,
        undefined,
    );
}

function successorMinimumDate(agreement: RentalAgreement | null): string | undefined {
    const effectiveFrom = latestRateVersion(agreement)?.effective_from ?? agreement?.starts_on;
    if (!effectiveFrom) return undefined;

    const [year, month, day] = effectiveFrom.split('-').map(Number);
    if (!year || !month || !day) return effectiveFrom;
    const nextDay = new Date(Date.UTC(year, month - 1, day + 1));
    return nextDay.toISOString().slice(0, 10);
}

function agreementPayload(state: AgreementFormState, expectedVersion?: number): RentalAgreementPayload {
    return {
        kind: state.kind,
        customer_id: state.kind === 'customer' ? state.customer?.id ?? null : null,
        supplier_id: state.kind === 'owner' ? state.supplier?.id ?? null : null,
        agreement_number: nullable(state.agreementNumber),
        executed_at: nullable(state.executedAt),
        starts_on: state.startsOn,
        ends_on: nullable(state.endsOn),
        billing_basis: state.billingBasis,
        currency_id: state.currency?.id ?? 0,
        tax_group_id: positiveInteger(state.taxGroupId),
        included_km: state.includedKm || '0',
        deposit_required: state.kind === 'customer' && state.depositRequired,
        deposit_amount: state.kind === 'customer' && state.depositRequired ? state.depositAmount || '0' : '0',
        payment_terms_days: Number.parseInt(state.paymentTermsDays || '0', 10),
        terms: nullable(state.terms),
        notes: nullable(state.notes),
        rates: state.rates.map((rate) => ({
            code: rate.code,
            unit: rate.unit,
            rate: rate.rate,
            is_taxable: rate.is_taxable,
            description: rate.description ?? null,
        })),
        expected_version: expectedVersion,
    };
}

function nullable(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function positiveInteger(value: string): number | null {
    const parsed = Number.parseInt(value, 10);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}
