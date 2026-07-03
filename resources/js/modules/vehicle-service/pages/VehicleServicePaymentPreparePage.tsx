import { useMemo, useState } from 'react';
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
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { compareDecimalStrings } from '@/shared/utils/decimal';
import { PaymentMethodFields } from '@/modules/payment/components/PaymentMethodFields';
import {
    createVehicleServicePayment,
    getVehicleServiceJob,
    getVehicleServicePaymentOptions,
    prepareVehicleServicePayment,
} from '../vehicleServiceApi';
import type {
    PreparedVehicleServicePayment,
    VehicleServicePaymentMethod,
    VehicleServicePaymentPayload,
} from '../vehicleServiceTypes';

const today = businessDateInputValue;

function paymentMethodKind(method?: VehicleServicePaymentMethod): string {
    const type = method?.method_type ?? '';
    if (['bank_transfer', 'direct_debit'].includes(type)) return 'bank_transfer';
    if (['digital_wallet', 'mobile_wallet'].includes(type)) return 'wallet';
    return type || 'other';
}

function methodReference(
    reference: string,
    kind: string,
    details: Record<string, string>,
): string | undefined {
    const explicit = reference.trim();
    if (explicit) return explicit;

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

function methodPayload(
    kind: string,
    details: Record<string, string>,
): Pick<VehicleServicePaymentPayload,
    | 'external_bank_name'
    | 'external_bank_branch'
    | 'instrument_number'
    | 'instrument_date'
> {
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
    return Boolean(
        payload.instrument_number
        || payload.instrument_date
        || payload.external_bank_name
        || payload.external_bank_branch,
    );
}

export default function VehicleServicePaymentPreparePage() {
    const jobId = Number(useParams().id);
    const navigate = useNavigate();
    const job = useApi((signal) => getVehicleServiceJob(jobId, signal), [jobId]);
    const options = useApi((signal) => getVehicleServicePaymentOptions(jobId, signal), [jobId]);
    const [invoiceId, setInvoiceId] = useState('');
    const [paymentMethodId, setPaymentMethodId] = useState('');
    const [date, setDate] = useState(today());
    const [amount, setAmount] = useState('0.000000');
    const [reference, setReference] = useState('');
    const [methodDetails, setMethodDetails] = useState<Record<string, string>>({});
    const [prepared, setPrepared] = useState<PreparedVehicleServicePayment | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const paymentMethods = options.data?.methods ?? [];
    const selectedMethod = paymentMethods.find((method) => String(method.id) === paymentMethodId);
    const kind = paymentMethodKind(selectedMethod);
    const instrumentPayload = methodPayload(kind, methodDetails);
    const resolvedReference = methodReference(reference, kind, methodDetails);
    const eligibleInvoices = useMemo(
        () => (job.data?.invoice_links ?? []).filter((link) =>
            link.status === 'active'
            && Boolean(link.can_receive_payment)
            && compareDecimalStrings(link.balance_due ?? '0', '0') > 0,
        ),
        [job.data?.invoice_links],
    );
    const invoice = eligibleInvoices.find((link) => link.invoice_id === Number(invoiceId));
    const amountValid = compareDecimalStrings(amount || '0', '0') > 0
        && (!invoice?.balance_due || compareDecimalStrings(amount, invoice.balance_due) <= 0);
    const methodRequirementsSatisfied = Boolean(selectedMethod)
        && (!selectedMethod?.requires_reference || Boolean(resolvedReference))
        && (!selectedMethod?.requires_instrument_details || hasInstrumentDetails(instrumentPayload));
    const canSubmit = Boolean(
        options.data?.job_version
        && invoiceId
        && amountValid
        && methodRequirementsSatisfied
        && !busy,
    );

    const clearPrepared = () => setPrepared(null);
    const payload = (): VehicleServicePaymentPayload => ({
        expected_version: options.data?.job_version ?? 0,
        invoice_id: Number(invoiceId),
        payment_date: date,
        amount,
        payment_method_id: Number(paymentMethodId),
        reference_number: resolvedReference,
        ...instrumentPayload,
    });

    if (job.loading || options.loading) return <LoadingState />;
    if (!job.data) return <ErrorAlert error={job.error} />;

    return (
        <>
            <ContentHeader
                title={`Payment for ${job.data.job_number}`}
                description="Receive, post, and allocate a customer payment through the Payment module."
                actions={<LinkButton to="/payments/methods" variant="secondary">Manage payment methods</LinkButton>}
            />
            <ErrorAlert error={error ?? options.error} />
            <Panel>
                <form className="grid gap-4 md:grid-cols-2" onSubmit={async (event) => {
                    event.preventDefault();
                    if (!canSubmit) return;
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
                    <Select
                        label="Invoice"
                        value={invoiceId}
                        error={fieldError(error, 'invoice_id')}
                        options={eligibleInvoices.map((link) => ({
                            value: link.invoice_id,
                            label: `${link.invoice_number ?? 'Invoice'} / balance ${link.balance_due ?? link.invoice_total}`,
                        }))}
                        placeholder={eligibleInvoices.length > 0 ? 'Select posted invoice' : 'No payable posted invoices'}
                        onChange={(event) => {
                            const value = event.target.value;
                            setInvoiceId(value);
                            clearPrepared();
                            const link = eligibleInvoices.find((entry) => entry.invoice_id === Number(value));
                            setAmount(link?.balance_due ?? '0.000000');
                        }}
                    />
                    <Select
                        label="Payment method"
                        value={paymentMethodId}
                        error={fieldError(error, 'payment_method_id')}
                        options={paymentMethods.map((method) => ({
                            value: method.id ?? '',
                            label: `${method.name}${method.method_type ? ` / ${method.method_type.replaceAll('_', ' ')}` : ''}`,
                        }))}
                        placeholder={paymentMethods.length > 0 ? 'Select payment method' : 'No active inbound methods'}
                        onChange={(event) => {
                            setPaymentMethodId(event.target.value);
                            setReference('');
                            setMethodDetails({});
                            clearPrepared();
                        }}
                    />
                    <Input
                        label="Payment date"
                        type="date"
                        value={date}
                        error={fieldError(error, 'payment_date')}
                        onChange={(event) => { setDate(event.target.value); clearPrepared(); }}
                    />
                    <DecimalInput
                        label="Amount"
                        value={amount}
                        error={fieldError(error, 'amount')}
                        onChange={(event) => { setAmount(event.target.value); clearPrepared(); }}
                    />
                    <Input
                        label={selectedMethod?.requires_reference ? 'Reference *' : 'Reference'}
                        value={reference}
                        error={fieldError(error, 'reference_number')}
                        onChange={(event) => { setReference(event.target.value); clearPrepared(); }}
                    />

                    {selectedMethod && (
                        <div className="md:col-span-2">
                            <PaymentMethodFields
                                kind={kind}
                                metadata={methodDetails}
                                onChange={(field, value) => {
                                    setMethodDetails((current) => ({ ...current, [field]: value }));
                                    clearPrepared();
                                }}
                            />
                            {selectedMethod.requires_instrument_details && !hasInstrumentDetails(instrumentPayload) && (
                                <p className="mt-2 text-sm text-amber-700">This method requires transaction instrument details.</p>
                            )}
                        </div>
                    )}

                    {invoice && (
                        <div className="rounded-lg border border-sky-100 bg-sky-50 p-4 md:col-span-2">
                            <DetailGrid items={[
                                { label: 'Invoice', value: invoice.invoice_number ?? 'Invoice' },
                                { label: 'Invoice total', value: invoice.invoice_total },
                                { label: 'Outstanding balance', value: invoice.balance_due ?? '-' },
                                { label: 'Invoice status', value: invoice.invoice_status ?? '-' },
                                { label: 'Settlement amount', value: amount },
                                { label: 'Method', value: selectedMethod?.name ?? '-' },
                            ]} />
                        </div>
                    )}

                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit" variant="secondary" loading={busy} disabled={!canSubmit}>
                            Review payment
                        </Button>
                        <Button type="button" loading={busy} disabled={!canSubmit} onClick={async () => {
                            setBusy(true);
                            setError(null);
                            try {
                                const payment = await createVehicleServicePayment(jobId, payload());
                                navigate(`/payments/${payment.id}`);
                            } catch (requestError) {
                                setError(toApiError(requestError));
                            } finally {
                                setBusy(false);
                            }
                        }}>
                            Receive, post and allocate
                        </Button>
                    </div>
                </form>

                {prepared && (
                    <div className="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                        <p className="mb-4 text-sm font-semibold text-emerald-900">Payment validation completed successfully</p>
                        <DetailGrid items={[
                            { label: 'Type', value: prepared.paymentType },
                            { label: 'Direction', value: prepared.direction },
                            { label: 'Payment date', value: prepared.paymentDate },
                            { label: 'Method', value: selectedMethod?.name ?? '-' },
                            { label: 'Reference', value: prepared.referenceNumber ?? resolvedReference ?? '-' },
                            { label: 'Amount', value: prepared.lines[0]?.amount ?? amount },
                            { label: 'Invoice', value: invoice?.invoice_number ?? '-' },
                        ]} />
                    </div>
                )}
            </Panel>
        </>
    );
}
