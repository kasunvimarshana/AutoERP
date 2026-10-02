import { useEffect, useMemo, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
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
    reference: string;
    details: Record<string, string>;
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
    const jobId = Number(useParams().id);
    const job = useApi((signal) => getVehicleServiceJob(jobId, signal), [jobId]);
    const options = useApi((signal) => getVehicleServicePaymentOptions(jobId, signal), [jobId]);
    const nextRowKey = useRef(2);
    const [invoiceId, setInvoiceId] = useState('');
    const [date, setDate] = useState(businessDateInputValue());
    const [rows, setRows] = useState<PaymentRow[]>([
        { key: 1, paymentMethodId: '', amount: ZERO_AMOUNT, reference: '', details: {} },
    ]);
    const [prepared, setPrepared] = useState<PreparedVehicleServicePayment | null>(null);
    const [createdPayment, setCreatedPayment] = useState<VehicleServicePaymentCreated | null>(null);
    const [settledInvoice, setSettledInvoice] = useState<VehicleServiceInvoiceLink | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const paymentMethods = useMemo(() => options.data?.methods ?? [], [options.data?.methods]);
    const eligibleInvoices = useMemo(
        () => (job.data?.invoice_links ?? []).filter((link) =>
            link.status === 'active'
            && Boolean(link.can_receive_payment)
            && compareDecimalStrings(link.balance_due ?? ZERO_AMOUNT, ZERO_AMOUNT) > 0,
        ),
        [job.data?.invoice_links],
    );
    const firstPaymentMethodId = rows[0]?.paymentMethodId ?? '';
    useEffect(() => {
        const onlyInvoice = !invoiceId && eligibleInvoices.length === 1 ? eligibleInvoices[0] : null;
        const onlyMethod = firstPaymentMethodId === '' && paymentMethods.length === 1 ? paymentMethods[0] : null;
        if (!onlyInvoice && !onlyMethod) return;

        let cancelled = false;
        queueMicrotask(() => {
            if (cancelled) return;
            if (onlyInvoice) {
                setInvoiceId((current) => current || String(onlyInvoice.invoice_id));
            }
            setRows((current) => current.map((row, index) => {
                if (index !== 0) return row;
                return {
                    ...row,
                    ...(onlyInvoice ? { amount: onlyInvoice.balance_due ?? onlyInvoice.invoice_total } : {}),
                    ...(onlyMethod && row.paymentMethodId === '' ? { paymentMethodId: String(onlyMethod.id) } : {}),
                };
            }));
        });

        return () => { cancelled = true; };
    }, [eligibleInvoices, firstPaymentMethodId, invoiceId, paymentMethods]);
    const invoice = eligibleInvoices.find((link) => link.invoice_id === Number(invoiceId));
    const paymentTotal = sumDecimals(rows.map((row) => row.amount || ZERO_AMOUNT));
    const outstanding = invoice?.balance_due ?? ZERO_AMOUNT;
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

            return {
                amount: row.amount,
                payment_method_id: Number(row.paymentMethodId),
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

        return compareDecimalStrings(row.amount || ZERO_AMOUNT, ZERO_AMOUNT) > 0
            && (!method.requires_reference || Boolean(methodReference(row.reference, kind, row.details)))
            && (!method.requires_instrument_details || hasInstrumentDetails(instrument));
    });
    const canPrepare = Boolean(
        options.data?.job_version
        && invoice
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
                description="Add one or more payment methods, review the total, then finalize the receipt."
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
                                                    reference: '',
                                                    details: {},
                                                })}
                                            />
                                            <DecimalInput
                                                label="Amount"
                                                value={row.amount}
                                                error={fieldError(error, `lines.${index}.amount`)}
                                                disabled={Boolean(createdPayment)}
                                                onChange={(event) => updateRow(row.key, { amount: event.target.value })}
                                            />
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
                                    </section>
                                );
                            })}
                        </div>

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
                        <p className="mt-1 text-xl font-semibold tabular-nums text-slate-950">{formatMoney(paymentTotal)}</p>
                        <p className="mt-1 text-xs text-slate-600">Across {rows.length} {rows.length === 1 ? 'method' : 'methods'}</p>
                        <div className="my-5 border-t border-blue-200" />
                        <p className="text-sm font-medium text-slate-700">Balance after payment</p>
                        <p className={`mt-1 text-xl font-semibold tabular-nums ${compareDecimalStrings(paymentTotal, outstanding) > 0 ? 'text-red-700' : 'text-slate-950'}`}>
                            {formatMoney(compareDecimalStrings(paymentTotal, outstanding) > 0
                                ? subtractDecimal(paymentTotal, outstanding)
                                : remainingAfterPayment)}
                        </p>
                        {compareDecimalStrings(paymentTotal, outstanding) > 0 && (
                            <p className="mt-2 text-sm text-red-700">Payment total exceeds the outstanding balance.</p>
                        )}
                        <div className="mt-6 flex flex-col gap-2">
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
                        </div>
                    </aside>
                </div>
            </form>
        </>
    );
}
