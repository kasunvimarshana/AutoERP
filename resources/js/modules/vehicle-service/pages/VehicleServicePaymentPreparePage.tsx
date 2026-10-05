import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { openSameOriginUrl } from '@/shared/utils/safeNavigation';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { compareDecimalStrings, nonNegativeDecimal, subtractDecimal, sumDecimals } from '@/shared/utils/decimal';
import { formatMoney } from '@/shared/utils/formatMoney';
import { getInvoiceSignedPrintLink } from '@/modules/invoice/invoiceApi';
import { PaymentMethodFields } from '@/modules/payment/components/PaymentMethodFields';
import {
    createVehicleServicePayment,
    checkVehicleServiceCredit,
    getVehicleServiceJob,
    getVehicleServicePaymentOptions,
    prepareVehicleServicePayment,
} from '../vehicleServiceApi';
import type {
    PreparedVehicleServicePayment,
    VehicleServiceInvoiceLink,
    VehicleServicePaymentCreated,
    VehicleServicePaymentMethod,
    VehicleServicePaymentPayload,
} from '../vehicleServiceTypes';

const ZERO_AMOUNT = '0.000000';

interface PaymentRow {
    key: number;
    paymentMethodId: string;
    amount: string;
    cashReceivedAmount: string;
    reference: string;
    details: Record<string, string>;
}

type PaymentMode = 'direct' | 'credit';

const CARD_BRANDS = [
    { value: 'visa', label: 'VISA' },
    { value: 'master', label: 'MASTER' },
    { value: 'amex', label: 'AMEX' },
] as const;

function isCashMethod(method?: VehicleServicePaymentMethod): boolean {
    return paymentMethodKind(method) === 'cash';
}

function calculateAppliedAmounts(
    rows: PaymentRow[],
    methods: VehicleServicePaymentMethod[],
    outstanding: string,
): string[] {
    const nonCashTotal = sumDecimals(rows.map((row) => {
        const method = methods.find((candidate) => String(candidate.id) === row.paymentMethodId);
        return isCashMethod(method) ? ZERO_AMOUNT : row.amount || ZERO_AMOUNT;
    }));
    let cashAvailable = nonNegativeDecimal(subtractDecimal(outstanding, nonCashTotal));

    return rows.map((row) => {
        const method = methods.find((candidate) => String(candidate.id) === row.paymentMethodId);
        if (!isCashMethod(method)) return row.amount || ZERO_AMOUNT;

        const applied = compareDecimalStrings(row.cashReceivedAmount || ZERO_AMOUNT, cashAvailable) < 0
            ? row.cashReceivedAmount || ZERO_AMOUNT
            : cashAvailable;
        cashAvailable = nonNegativeDecimal(subtractDecimal(cashAvailable, applied));

        return applied;
    });
}

function paymentMethodKind(method?: VehicleServicePaymentMethod): string {
    const type = method?.method_type ?? '';
    if (['bank_transfer', 'direct_debit'].includes(type)) return 'bank_transfer';
    if (['digital_wallet', 'mobile_wallet'].includes(type)) return 'wallet';
    return type || 'other';
}

function methodReference(reference: string, kind: string, details: Record<string, string>): string | undefined {
    if (reference.trim()) return reference.trim();

    const derived = kind === 'cheque'
        ? details.cheque_number
        : kind === 'bank_transfer'
            ? details.transfer_reference
            : kind === 'card'
                ? details.card_reference || details.authorization_code
                : kind === 'wallet'
                    ? details.wallet_reference
                    : undefined;

    return derived?.trim() || undefined;
}

function methodPayload(kind: string, details: Record<string, string>) {
    if (kind === 'cheque') {
        return {
            instrument_number: details.cheque_number?.trim() || undefined,
            instrument_date: details.cheque_date || undefined,
            external_bank_name: details.bank_account?.trim() || undefined,
        };
    }
    if (kind === 'bank_transfer') {
        return {
            instrument_number: details.transfer_reference?.trim() || undefined,
            instrument_date: details.transfer_date || undefined,
            external_bank_name: details.bank_account?.trim() || undefined,
        };
    }
    if (kind === 'card') {
        return {
            instrument_number: details.card_reference?.trim() || details.authorization_code?.trim() || undefined,
            external_bank_name: details.terminal?.trim() || undefined,
        };
    }
    if (kind === 'wallet') {
        return {
            instrument_number: details.wallet_reference?.trim() || undefined,
            external_bank_name: details.provider?.trim() || undefined,
        };
    }

    return {};
}

function hasInstrumentDetails(payload: ReturnType<typeof methodPayload>): boolean {
    return Boolean(payload.instrument_number || payload.instrument_date || payload.external_bank_name);
}

export default function VehicleServicePaymentPreparePage() {
    const navigate = useNavigate();
    const jobId = Number(useParams().id);
    const job = useApi((signal) => getVehicleServiceJob(jobId, signal), [jobId]);
    const options = useApi((signal) => getVehicleServicePaymentOptions(jobId, signal), [jobId]);
    const nextRowKey = useRef(2);
    const [invoiceId, setInvoiceId] = useState('');
    const [date, setDate] = useState(businessDateInputValue());
    const [paymentMode, setPaymentMode] = useState<PaymentMode>('direct');
    const [rows, setRows] = useState<PaymentRow[]>([
        { key: 1, paymentMethodId: '', amount: ZERO_AMOUNT, cashReceivedAmount: '', reference: '', details: {} },
    ]);
    const [prepared, setPrepared] = useState<PreparedVehicleServicePayment | null>(null);
    const [createdPayment, setCreatedPayment] = useState<VehicleServicePaymentCreated | null>(null);
    const [settledInvoice, setSettledInvoice] = useState<VehicleServiceInvoiceLink | null>(null);
    const [busy, setBusy] = useState(false);
    const [creditCheckFeedback, setCreditCheckFeedback] = useState<{ kind: 'blocked' | 'warning'; message: string } | null>(null);
    const [acknowledgedCreditWarning, setAcknowledgedCreditWarning] = useState<string | null>(null);
    const [error, setError] = useState<ApiError | null>(null);

    const paymentMethods = options.data?.methods ?? [];
    const eligibleInvoices = useMemo(
        () => (job.data?.invoice_links ?? []).filter((link) =>
            link.status === 'active'
            && Boolean(link.can_receive_payment)
            && compareDecimalStrings(link.balance_due ?? ZERO_AMOUNT, ZERO_AMOUNT) > 0,
        ),
        [job.data?.invoice_links],
    );
    useEffect(() => {
        if (!invoiceId && eligibleInvoices.length === 1) {
            const [onlyInvoice] = eligibleInvoices;
            setInvoiceId(String(onlyInvoice.invoice_id));
            setRows((current) => current.map((row, index) => ({
                ...row,
                amount: index === 0 && !isCashMethod(paymentMethods.find((method) => String(method.id) === row.paymentMethodId))
                    ? onlyInvoice.balance_due ?? onlyInvoice.invoice_total
                    : ZERO_AMOUNT,
            })));
        }

        if (paymentMethods.length === 1 && rows[0]?.paymentMethodId === '') {
            const [onlyMethod] = paymentMethods;
            setRows((current) => current.map((row, index) => index === 0
                ? {
                    ...row,
                    paymentMethodId: String(onlyMethod.id),
                    amount: isCashMethod(onlyMethod) ? ZERO_AMOUNT : eligibleInvoices[0]?.balance_due ?? ZERO_AMOUNT,
                }
                : row));
        }
    }, [eligibleInvoices, invoiceId, paymentMethods, rows]);
    const invoice = eligibleInvoices.find((link) => link.invoice_id === Number(invoiceId));
    const outstanding = invoice?.balance_due ?? ZERO_AMOUNT;
    const appliedAmounts = calculateAppliedAmounts(rows, paymentMethods, outstanding);
    const paymentTotal = sumDecimals(appliedAmounts);
    const remainingAfterPayment = nonNegativeDecimal(subtractDecimal(outstanding, paymentTotal));

    const clearPrepared = () => {
        setPrepared(null);
        setCreatedPayment(null);
        setSettledInvoice(null);
    };
    const updateRow = (key: number, update: Partial<PaymentRow>) => {
        setRows((current) => current.map((row) => row.key === key ? { ...row, ...update } : row));
        clearPrepared();
    };
    const payload = (): VehicleServicePaymentPayload => ({
        expected_version: options.data?.job_version ?? 0,
        invoice_id: Number(invoiceId),
        payment_date: date,
        lines: rows.map((row) => {
            const method = paymentMethods.find((candidate) => String(candidate.id) === row.paymentMethodId);
            const kind = paymentMethodKind(method);
            const index = rows.indexOf(row);

            return {
                amount: appliedAmounts[index] ?? ZERO_AMOUNT,
                payment_method_id: Number(row.paymentMethodId),
                ...(kind === 'card' && row.details.card_brand
                    ? { card_brand: row.details.card_brand as 'visa' | 'master' | 'amex' }
                    : {}),
                reference_number: methodReference(row.reference, kind, row.details),
                ...methodPayload(kind, row.details),
            };
        }),
    });
    const rowsValid = rows.length > 0 && rows.every((row) => {
        const method = paymentMethods.find((candidate) => String(candidate.id) === row.paymentMethodId);
        if (!method) return false;

        const kind = paymentMethodKind(method);
        const instrument = methodPayload(kind, row.details);
        const appliedAmount = appliedAmounts[rows.indexOf(row)] ?? ZERO_AMOUNT;

        return compareDecimalStrings(appliedAmount, ZERO_AMOUNT) > 0
            && (kind !== 'card' || CARD_BRANDS.some((brand) => brand.value === row.details.card_brand))
            && (!method.requires_reference || Boolean(methodReference(row.reference, kind, row.details)))
            && (!method.requires_instrument_details || hasInstrumentDetails(instrument));
    });
    const canPrepare = Boolean(
        options.data?.job_version
        && invoice
        && paymentMode === 'direct'
        && rowsValid
        && compareDecimalStrings(paymentTotal, ZERO_AMOUNT) > 0
        && compareDecimalStrings(paymentTotal, outstanding) <= 0
        && !busy
        && !createdPayment,
    );

    if (job.loading || options.loading) return <LoadingState />;
    if (!job.data) return <ErrorAlert error={job.error} />;

    return (
        <>
            <ContentHeader
                title={`Payment for ${job.data.job_number}`}
                description="Collect a direct payment or keep the outstanding invoice on an approved customer account."
                actions={<LinkButton to="/payments/methods" variant="secondary">Manage payment methods</LinkButton>}
            />
            <ErrorAlert error={error ?? options.error} />
            <form onSubmit={async (event) => {
                event.preventDefault();
                if (!canPrepare) return;
                setBusy(true);
                setError(null);
                try {
                    setPrepared(await prepareVehicleServicePayment(jobId, payload()));
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setBusy(false);
                }
            }}>
                <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
                    <Panel className="space-y-5">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Select
                                label="Invoice"
                                value={invoiceId}
                                error={fieldError(error, 'invoice_id')}
                                options={eligibleInvoices.map((link) => ({
                                    value: link.invoice_id,
                                    label: `${link.invoice_number ?? 'Invoice'} · balance ${formatMoney(link.balance_due ?? link.invoice_total)}`,
                                }))}
                                placeholder={eligibleInvoices.length > 0 ? 'Select posted invoice' : 'No payable posted invoices'}
                                disabled={Boolean(createdPayment)}
                                onChange={(event) => {
                                    const selectedInvoice = eligibleInvoices.find((link) => link.invoice_id === Number(event.target.value));
                                    setInvoiceId(event.target.value);
                                    setRows((current) => current.map((row, index) => ({
                                        ...row,
                                        amount: index === 0 ? selectedInvoice?.balance_due ?? ZERO_AMOUNT : ZERO_AMOUNT,
                                    })));
                                    setCreditCheckFeedback(null);
                                    setAcknowledgedCreditWarning(null);
                                    clearPrepared();
                                }}
                            />
                            <Input
                                label="Payment date"
                                type="date"
                                value={date}
                                error={fieldError(error, 'payment_date')}
                                disabled={Boolean(createdPayment)}
                                onChange={(event) => { setDate(event.target.value); clearPrepared(); }}
                            />
                        </div>

                        <fieldset className="space-y-2">
                            <legend className="text-sm font-medium text-slate-800">Payment type</legend>
                            <div className="flex flex-wrap gap-3">
                                <label className="flex cursor-pointer items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm">
                                    <input
                                        type="radio"
                                        name="payment-mode"
                                        value="direct"
                                        checked={paymentMode === 'direct'}
                                        disabled={Boolean(createdPayment)}
                                        onChange={() => { setPaymentMode('direct'); setCreditCheckFeedback(null); setAcknowledgedCreditWarning(null); clearPrepared(); }}
                                    />
                                    Direct Payment
                                </label>
                                {options.data?.credit_allowed && (
                                    <label className="flex cursor-pointer items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm">
                                        <input
                                            type="radio"
                                            name="payment-mode"
                                            value="credit"
                                            checked={paymentMode === 'credit'}
                                            disabled={Boolean(createdPayment)}
                                            onChange={() => { setPaymentMode('credit'); setCreditCheckFeedback(null); setAcknowledgedCreditWarning(null); clearPrepared(); }}
                                        />
                                        Credit Payment
                                    </label>
                                )}
                            </div>
                        </fieldset>

                        {paymentMode === 'direct' ? <>
                        <div className="flex items-end justify-between gap-3">
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">Payment methods</h2>
                                <p className="mt-1 text-sm text-slate-600">Split the amount across the methods received.</p>
                            </div>
                            <Button type="button" variant="secondary" disabled={Boolean(createdPayment)} onClick={() => {
                                setRows((current) => [...current, {
                                    key: nextRowKey.current++,
                                    paymentMethodId: '',
                                    amount: ZERO_AMOUNT,
                                    cashReceivedAmount: '',
                                    reference: '',
                                    details: {},
                                }]);
                                clearPrepared();
                            }}>
                                Add method
                            </Button>
                        </div>

                        <div className="space-y-4">
                            {rows.map((row, index) => {
                                const selectedMethod = paymentMethods.find((method) => String(method.id) === row.paymentMethodId);
                                const kind = paymentMethodKind(selectedMethod);
                                const instrument = methodPayload(kind, row.details);
                                const usedMethodIds = rows.filter((item) => item.key !== row.key).map((item) => item.paymentMethodId);

                                return (
                                    <section key={row.key} className="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                                        <div className="mb-4 flex items-center justify-between">
                                            <h3 className="text-sm font-semibold text-slate-800">Method {index + 1}</h3>
                                            {rows.length > 1 && !createdPayment && (
                                                <Button type="button" variant="ghost" onClick={() => {
                                                    setRows((current) => current.filter((item) => item.key !== row.key));
                                                    clearPrepared();
                                                }}>
                                                    Remove
                                                </Button>
                                            )}
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <Select
                                                label="Payment method"
                                                value={row.paymentMethodId}
                                                error={fieldError(error, `lines.${index}.payment_method_id`)}
                                                disabled={Boolean(createdPayment)}
                                                options={paymentMethods.filter((method) =>
                                                    !usedMethodIds.includes(String(method.id)) || String(method.id) === row.paymentMethodId,
                                                ).map((method) => ({
                                                    value: method.id ?? '',
                                                    label: `${method.name}${method.method_type ? ` · ${method.method_type.replaceAll('_', ' ')}` : ''}`,
                                                }))}
                                                placeholder={paymentMethods.length > 0 ? 'Select payment method' : 'No active inbound methods'}
                                                onChange={(event) => updateRow(row.key, {
                                                    paymentMethodId: event.target.value,
                                                    amount: isCashMethod(paymentMethods.find((method) => String(method.id) === event.target.value))
                                                        ? ZERO_AMOUNT
                                                        : outstanding,
                                                    reference: '',
                                                    details: {},
                                                })}
                                            />
                                            {isCashMethod(selectedMethod) ? (
                                                <DecimalInput
                                                    label="Cash received"
                                                    value={row.cashReceivedAmount}
                                                    error={fieldError(error, `lines.${index}.amount`)}
                                                    disabled={Boolean(createdPayment)}
                                                    onChange={(event) => updateRow(row.key, { cashReceivedAmount: event.target.value })}
                                                />
                                            ) : (
                                                <DecimalInput
                                                    label="Amount"
                                                    value={row.amount}
                                                    error={fieldError(error, `lines.${index}.amount`)}
                                                    disabled={Boolean(createdPayment)}
                                                    onChange={(event) => updateRow(row.key, { amount: event.target.value })}
                                                />
                                            )}
                                            {kind === 'card' && (
                                                <fieldset className="space-y-2 md:col-span-2">
                                                    <legend className="text-sm font-medium text-slate-700">Card type</legend>
                                                    <div className="flex flex-wrap gap-2">
                                                        {CARD_BRANDS.map((brand) => (
                                                            <label key={brand.value} className="flex cursor-pointer items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm">
                                                                <input
                                                                    type="radio"
                                                                    name={`card-brand-${row.key}`}
                                                                    value={brand.value}
                                                                    checked={row.details.card_brand === brand.value}
                                                                    disabled={Boolean(createdPayment)}
                                                                    onChange={() => updateRow(row.key, {
                                                                        details: { ...row.details, card_brand: brand.value },
                                                                    })}
                                                                />
                                                                {brand.label}
                                                            </label>
                                                        ))}
                                                    </div>
                                                </fieldset>
                                            )}
                                            {selectedMethod?.requires_reference && (
                                                <Input
                                                    label="Reference"
                                                    value={row.reference}
                                                    error={fieldError(error, `lines.${index}.reference_number`)}
                                                    disabled={Boolean(createdPayment)}
                                                    onChange={(event) => updateRow(row.key, { reference: event.target.value })}
                                                />
                                            )}
                                        </div>
                                        {selectedMethod && (
                                            <>
                                                <PaymentMethodFields
                                                    kind={kind}
                                                    metadata={row.details}
                                                    disabled={Boolean(createdPayment)}
                                                    onChange={(field, value) => updateRow(row.key, {
                                                        details: { ...row.details, [field]: value },
                                                    })}
                                                />
                                                {selectedMethod.requires_instrument_details && !hasInstrumentDetails(instrument) && (
                                                    <p className="mt-3 text-sm text-amber-700">Enter the transaction details required for this method.</p>
                                                )}
                                            </>
                                        )}
                                        {isCashMethod(selectedMethod) && (
                                            <div className="mt-4 grid gap-3 rounded-md bg-white p-3 sm:grid-cols-2">
                                                <div>
                                                    <p className="text-xs text-slate-500">Cash amount</p>
                                                    <p className="mt-1 font-semibold tabular-nums text-slate-900">{formatMoney(appliedAmounts[index] ?? ZERO_AMOUNT)}</p>
                                                </div>
                                                <div>
                                                    <p className="text-xs text-slate-500">Change to return</p>
                                                    <p className="mt-1 font-semibold tabular-nums text-emerald-700">{formatMoney(nonNegativeDecimal(subtractDecimal(row.cashReceivedAmount || ZERO_AMOUNT, appliedAmounts[index] ?? ZERO_AMOUNT)))}</p>
                                                </div>
                                            </div>
                                        )}
                                    </section>
                                );
                            })}
                        </div>
                        </> : (
                            <div className="space-y-3 rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                                <p>No payment will be collected now. The outstanding invoice balance will remain on this customer account.</p>
                                {options.data?.credit_assessment?.available && (
                                    <DetailGrid items={[
                                        { label: 'Open invoice exposure', value: formatMoney(options.data.credit_assessment.open_exposure, options.data.credit_assessment.currency_code ?? 'LKR') },
                                        { label: 'Credit limit', value: formatMoney(options.data.credit_assessment.credit_limit, options.data.credit_assessment.currency_code ?? 'LKR') },
                                        { label: 'Remaining credit', value: formatMoney(options.data.credit_assessment.remaining_credit, options.data.credit_assessment.currency_code ?? 'LKR') },
                                    ]} />
                                )}
                                {options.data?.credit_assessment?.warning && (
                                    <p role="alert" className="font-medium text-amber-800">
                                        {options.data.credit_assessment.warning}
                                    </p>
                                )}
                            </div>
                        )}

                        {invoice && (
                            <div className="rounded-lg border border-sky-100 bg-sky-50 p-4">
                                <DetailGrid items={[
                                    { label: 'Invoice', value: invoice.invoice_number ?? 'Invoice' },
                                    { label: 'Invoice total', value: formatMoney(invoice.invoice_total) },
                                    { label: 'Outstanding balance', value: formatMoney(outstanding) },
                                ]} />
                            </div>
                        )}

                        {prepared && (
                            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                                <p className="text-sm font-semibold text-emerald-900">Payment is ready to finalize</p>
                                <p className="mt-1 text-sm text-emerald-800">Review the method amounts and remaining invoice balance before finalizing.</p>
                            </div>
                        )}

                        {createdPayment && settledInvoice && (
                            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                                <p className="text-sm font-semibold text-emerald-900">Payment created and allocated successfully</p>
                                <p className="mt-1 text-sm text-emerald-800">
                                    {createdPayment.payment_number ?? 'Payment'} was posted for invoice {settledInvoice.invoice_number ?? 'the selected invoice'}.
                                </p>
                                <div className="mt-4">
                                    <DetailGrid items={[
                                        { label: 'Payment', value: createdPayment.payment_number ?? '-' },
                                        { label: 'Posting status', value: createdPayment.posting_status ?? '-' },
                                        { label: 'Allocated amount', value: formatMoney(createdPayment.allocated_amount ?? paymentTotal) },
                                    ]} />
                                </div>
                            </div>
                        )}
                    </Panel>

                    <aside className="rounded-lg border border-blue-100 bg-blue-50 p-5 xl:sticky xl:top-5">
                        <p className="text-sm font-medium text-slate-700">Outstanding balance</p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums text-slate-950">{formatMoney(outstanding)}</p>
                        <div className="my-5 border-t border-blue-200" />
                        <p className="text-sm font-medium text-slate-700">Payment total</p>
                        <p className="mt-1 text-xl font-semibold tabular-nums text-slate-950">{formatMoney(paymentMode === 'direct' ? paymentTotal : ZERO_AMOUNT)}</p>
                        {paymentMode === 'direct' && (
                            <p className="mt-1 text-xs text-slate-600">Across {rows.length} {rows.length === 1 ? 'method' : 'methods'}</p>
                        )}
                        <div className="my-5 border-t border-blue-200" />
                        <p className="text-sm font-medium text-slate-700">{paymentMode === 'credit' ? 'Credit amount' : 'Balance after payment'}</p>
                        <p className="mt-1 text-xl font-semibold tabular-nums text-slate-950">
                            {formatMoney(paymentMode === 'credit'
                                ? outstanding
                                : compareDecimalStrings(paymentTotal, outstanding) > 0
                                    ? subtractDecimal(paymentTotal, outstanding)
                                    : remainingAfterPayment)}
                        </p>
                        {paymentMode === 'direct' && compareDecimalStrings(paymentTotal, outstanding) > 0 && (
                            <p className="mt-2 text-sm text-red-700">Payment total exceeds the outstanding balance.</p>
                        )}
                        {paymentMode === 'credit' && creditCheckFeedback && (
                            <p
                                role="alert"
                                aria-live="assertive"
                                className={`mt-4 rounded-md border p-3 text-sm font-medium ${creditCheckFeedback.kind === 'blocked'
                                    ? 'border-red-200 bg-red-50 text-red-800'
                                    : 'border-amber-200 bg-amber-50 text-amber-800'}`}
                            >
                                {creditCheckFeedback.message}
                            </p>
                        )}
                        <div className="mt-6 flex flex-col gap-2">
                            {paymentMode === 'direct' ? <>
                                <Button type="submit" variant="secondary" loading={busy} disabled={!canPrepare || Boolean(prepared)}>
                                    Review payment
                                </Button>
                                {createdPayment && settledInvoice ? (
                                    <Button type="button" onClick={async () => {
                                        try {
                                            const json = await getInvoiceSignedPrintLink(settledInvoice.invoice_id);
                                            if (json.print_url) {
                                                openSameOriginUrl(json.print_url);
                                                return;
                                            }
                                        } catch {
                                            // Use the invoice print page when a signed link is unavailable.
                                        }
                                        openSameOriginUrl(`/invoices/${settledInvoice.invoice_id}/print`);
                                    }}>
                                        Print bill
                                    </Button>
                                ) : (
                                    <Button type="button" loading={busy} disabled={!prepared || busy} onClick={async () => {
                                        if (!prepared || !invoice) return;
                                        setBusy(true);
                                        setError(null);
                                        try {
                                            const payment = await createVehicleServicePayment(jobId, payload());
                                            setSettledInvoice(invoice);
                                            setCreatedPayment(payment);
                                            options.reload();
                                            job.reload();
                                        } catch (requestError) {
                                            setError(toApiError(requestError));
                                        } finally {
                                            setBusy(false);
                                        }
                                    }}>
                                        Finalize payment
                                    </Button>
                                )}
                            </> : (
                                <Button type="button" loading={busy} disabled={!invoice || busy} onClick={async () => {
                                    setBusy(true);
                                    setCreditCheckFeedback(null);
                                    try {
                                        const assessment = await checkVehicleServiceCredit(jobId);
                                        if (!assessment.can_keep_on_credit) {
                                            setCreditCheckFeedback({
                                                kind: 'blocked',
                                                message: assessment.warning ?? 'Credit cannot be used for this customer. Choose Direct Payment to continue.',
                                            });
                                            setAcknowledgedCreditWarning(null);
                                            return;
                                        }
                                        if (assessment.warning && acknowledgedCreditWarning !== assessment.warning) {
                                            setCreditCheckFeedback({ kind: 'warning', message: `${assessment.warning} Click confirm to continue.` });
                                            setAcknowledgedCreditWarning(assessment.warning);
                                            return;
                                        }
                                        navigate(`/vehicle-service/jobs/${jobId}`);
                                    } catch (requestError) {
                                        setCreditCheckFeedback({
                                            kind: 'blocked',
                                            message: toApiError(requestError).message,
                                        });
                                    } finally {
                                        setBusy(false);
                                    }
                                }}>
                                    {acknowledgedCreditWarning ? 'Confirm credit payment' : 'Keep invoice on credit'}
                                </Button>
                            )}
                        </div>
                    </aside>
                </div>
            </form>
        </>
    );
}
