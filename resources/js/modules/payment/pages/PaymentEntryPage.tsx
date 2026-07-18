import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { getInvoice } from '@/modules/invoice/invoiceApi';
import type { Invoice } from '@/modules/invoice/invoiceTypes';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { compareDecimalStrings, isPositiveDecimal, sumDecimals } from '@/shared/utils/decimal';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { readableRelation } from '@/shared/utils/object';
import { parsePositiveInteger } from '@/shared/utils/routeParams';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import { SupplierLookupSelect } from '@/modules/supplier/components/SupplierLookupSelect';
import { createPayment, listPaymentMethods, type PaymentLinePayload, type PaymentMethod } from '../paymentApi';
import { PaymentLineTable, type PaymentLineDraft } from '../components/PaymentLineTable';

const PAYMENT_TYPE_CUSTOMER_RECEIPT = 'customer_receipt';
const PAYMENT_TYPE_SUPPLIER_PAYMENT = 'supplier_payment';
const PAYMENT_DIRECTION_INBOUND = 'inbound';
const PAYMENT_DIRECTION_OUTBOUND = 'outbound';
const ALLOCATION_METHOD_SPECIFIC_INVOICE = 'specific_invoice';
const SETTLEABLE_INVOICE_STATUSES = ['posted', 'partially_paid'] as const;

const typeOptions = [
    { value: PAYMENT_TYPE_CUSTOMER_RECEIPT, label: 'Customer receipt' },
    { value: PAYMENT_TYPE_SUPPLIER_PAYMENT, label: 'Supplier payment' },
    { value: 'advance', label: 'Advance / deposit' },
    { value: 'refund', label: 'Refund' },
    { value: 'manual', label: 'Manual payment' },
];

const directionOptions = [
    { value: PAYMENT_DIRECTION_INBOUND, label: 'Inbound' },
    { value: PAYMENT_DIRECTION_OUTBOUND, label: 'Outbound' },
];

function today(): string {
    return businessDateInputValue();
}

function emptyLine(key: number): PaymentLineDraft {
    return { key, paymentMethodId: '', amount: '0.000000', reference: '', metadata: {} };
}

function methodKind(method?: PaymentMethod): string {
    const type = method?.method_type ?? '';
    if (['bank_transfer', 'direct_debit'].includes(type)) return 'bank_transfer';
    if (['digital_wallet', 'mobile_wallet'].includes(type)) return 'wallet';
    return type || 'other';
}

function linePayload(line: PaymentLineDraft, method: PaymentMethod, direction: string): PaymentLinePayload {
    const kind = methodKind(method);
    const payload: PaymentLinePayload = {
        payment_method_id: method.id,
        amount: line.amount,
        reference_number: line.reference.trim() || undefined,
        instrument_direction: direction === PAYMENT_DIRECTION_OUTBOUND ? 'issued' : 'received',
    };

    if (kind === 'cheque') {
        payload.instrument_number = line.metadata.cheque_number?.trim() || undefined;
        payload.instrument_date = line.metadata.cheque_date || undefined;
        payload.external_bank_name = line.metadata.bank_account?.trim() || undefined;
    }
    if (kind === 'bank_transfer') {
        payload.instrument_number = line.metadata.transfer_reference?.trim() || undefined;
        payload.instrument_date = line.metadata.transfer_date || undefined;
        payload.external_bank_name = line.metadata.bank_account?.trim() || undefined;
    }
    if (kind === 'card') {
        payload.instrument_number = line.metadata.card_reference?.trim() || line.metadata.authorization_code?.trim() || undefined;
        payload.external_bank_name = line.metadata.terminal?.trim() || undefined;
    }
    if (kind === 'wallet') {
        payload.instrument_number = line.metadata.wallet_reference?.trim() || undefined;
        payload.external_bank_name = line.metadata.provider?.trim() || undefined;
    }

    return payload;
}

function lineIsValid(line: PaymentLineDraft, method: PaymentMethod | undefined, headerReference: string, direction: string): boolean {
    if (!method || !isPositiveDecimal(line.amount)) return false;

    const payload = linePayload(line, method, direction);
    const hasReference = Boolean(payload.reference_number || headerReference.trim());
    const hasInstrumentDetails = Boolean(
        payload.instrument_number
        || payload.instrument_date
        || payload.external_bank_name
        || payload.external_bank_branch,
    );

    return (!method.requires_reference || hasReference)
        && (!method.requires_instrument_details || hasInstrumentDetails);
}

function invoiceSettlementIssue(invoice: Invoice): string | null {
    if (!SETTLEABLE_INVOICE_STATUSES.includes(invoice.status as typeof SETTLEABLE_INVOICE_STATUSES[number])) {
        return 'Only a posted invoice with an open balance can be settled.';
    }
    if (![PAYMENT_DIRECTION_INBOUND, PAYMENT_DIRECTION_OUTBOUND].includes(invoice.direction ?? '')) {
        return 'The invoice direction is not supported for payment settlement.';
    }
    if (!invoice.party_type || !invoice.party?.id) {
        return 'The invoice must have an authoritative settlement party.';
    }
    if (!invoice.currency?.id) {
        return 'The invoice must have an authoritative settlement currency.';
    }
    if (!isPositiveDecimal(invoice.balance_due ?? '0')) {
        return 'This invoice has no remaining balance to settle.';
    }

    return null;
}

function invoiceParty(invoice: Invoice): NamedResource | null {
    const party = invoice.party;
    if (!party?.id) return null;

    return {
        id: party.id,
        code: party.code ?? party.number ?? undefined,
        name: party.name ?? party.legal_name ?? 'Invoice party',
    };
}

export default function PaymentEntryPage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const invoiceId = parsePositiveInteger(searchParams.get('invoice_id'));
    const initializedInvoiceId = useRef<number | null>(null);
    const [paymentType, setPaymentType] = useState(PAYMENT_TYPE_CUSTOMER_RECEIPT);
    const [direction, setDirection] = useState(PAYMENT_DIRECTION_INBOUND);
    const [paymentDate, setPaymentDate] = useState(today());
    const [reference, setReference] = useState('');
    const [party, setParty] = useState<NamedResource | null>(null);
    const [lines, setLines] = useState<PaymentLineDraft[]>([emptyLine(1)]);
    const [nextKey, setNextKey] = useState(2);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const settlementInvoice = useApi(
        (signal) => getInvoice(invoiceId ?? 0, signal),
        [invoiceId],
        invoiceId !== null,
    );
    const invoiceIssue = settlementInvoice.data ? invoiceSettlementIssue(settlementInvoice.data) : null;

    useEffect(() => {
        const invoice = settlementInvoice.data;
        if (!invoice || invoiceIssue || initializedInvoiceId.current === invoice.id) return;

        const nextPaymentType = invoice.direction === PAYMENT_DIRECTION_OUTBOUND
            ? PAYMENT_TYPE_CUSTOMER_RECEIPT
            : PAYMENT_TYPE_SUPPLIER_PAYMENT;
        const nextDirection = invoice.direction === PAYMENT_DIRECTION_OUTBOUND
            ? PAYMENT_DIRECTION_INBOUND
            : PAYMENT_DIRECTION_OUTBOUND;
        const balance = invoice.balance_due ?? '0.000000';

        initializedInvoiceId.current = invoice.id;
        setPaymentType(nextPaymentType);
        setDirection(nextDirection);
        setParty(invoiceParty(invoice));
        setReference(`Settlement for ${invoice.invoice_number ?? 'invoice'}`);
        setLines([{ ...emptyLine(1), amount: balance }]);
        setNextKey(2);
    }, [invoiceIssue, settlementInvoice.data]);

    const methods = useApi((signal) => listPaymentMethods({ direction, per_page: 100 }, signal), [direction]);
    const methodRows = methods.data?.data ?? [];
    const total = useMemo(() => sumDecimals(lines.map((line) => line.amount || '0.000000')), [lines]);
    const partyType = settlementInvoice.data?.party_type
        ?? (paymentType === PAYMENT_TYPE_SUPPLIER_PAYMENT ? 'supplier' : 'customer');
    const invoiceAmountValid = !settlementInvoice.data
        || compareDecimalStrings(total, settlementInvoice.data.balance_due ?? '0') <= 0;
    const canSubmit = !busy
        && invoiceIssue === null
        && invoiceAmountValid
        && (invoiceId === null || party !== null)
        && lines.every((line) => lineIsValid(
            line,
            methodRows.find((method) => String(method.id) === line.paymentMethodId),
            reference,
            direction,
        ));

    const updateLine = (key: number, patch: Partial<PaymentLineDraft>) => {
        setLines((current) => current.map((line) => line.key === key ? { ...line, ...patch } : line));
    };
    const updateMetadata = (key: number, field: string, value: string) => {
        setLines((current) => current.map((line) => line.key === key
            ? { ...line, metadata: { ...line.metadata, [field]: value } }
            : line));
    };

    async function submit() {
        if (!canSubmit) return;
        setBusy(true);
        setError(null);
        try {
            const payloadLines = lines.map((line) => {
                const method = methodRows.find((candidate) => String(candidate.id) === line.paymentMethodId);
                if (!method) throw new Error('A valid payment method is required for every line.');
                return linePayload(line, method, direction);
            });
            const payment = await createPayment({
                payment_type: paymentType,
                direction,
                payment_date: paymentDate,
                party_type: party ? partyType : undefined,
                party_id: party?.id,
                currency_id: settlementInvoice.data?.currency?.id ?? undefined,
                reference_number: reference.trim() || undefined,
                notes: settlementInvoice.data
                    ? `Settlement initiated from invoice ${settlementInvoice.data.invoice_number ?? settlementInvoice.data.id}.`
                    : undefined,
                lines: payloadLines,
                allocations: settlementInvoice.data ? [{
                    invoice_id: settlementInvoice.data.id,
                    allocated_amount: total,
                    allocation_date: paymentDate,
                    allocation_method: ALLOCATION_METHOD_SPECIFIC_INVOICE,
                }] : undefined,
            });
            navigate(`/payments/${payment.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    if (invoiceId !== null && settlementInvoice.loading) return <LoadingState />;

    return (
        <>
            <ContentHeader
                title={settlementInvoice.data ? 'Invoice settlement' : 'Payment entry'}
                description={settlementInvoice.data
                    ? 'Create the matching receipt or supplier payment and allocate it to this invoice atomically.'
                    : 'Create a payment with one or more configured settlement methods.'}
            />
            <ErrorAlert error={error ?? methods.error ?? settlementInvoice.error} />
            {settlementInvoice.data && (
                <Panel title="Invoice to settle">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Invoice</div>
                            <div className="mt-1 font-medium text-slate-900">
                                {settlementInvoice.data.invoice_number ?? 'Invoice'}
                            </div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Party</div>
                            <div className="mt-1 text-slate-900">{readableRelation(settlementInvoice.data.party)}</div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Settlement</div>
                            <div className="mt-1 text-slate-900">
                                {settlementInvoice.data.direction === PAYMENT_DIRECTION_OUTBOUND
                                    ? 'Customer receipt'
                                    : 'Supplier payment'}
                            </div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Remaining</div>
                            <div className="mt-1 text-slate-900">
                                <MoneyDisplay
                                    value={settlementInvoice.data.balance_due}
                                    currency={settlementInvoice.data.currency?.code ?? undefined}
                                />
                            </div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</div>
                            <div className="mt-1"><StatusBadge status={settlementInvoice.data.status} /></div>
                        </div>
                    </div>
                    {invoiceIssue && <p className="mt-4 text-sm font-medium text-rose-700">{invoiceIssue}</p>}
                    {!invoiceAmountValid && (
                        <p className="mt-4 text-sm font-medium text-rose-700">
                            Payment total cannot exceed the invoice remaining balance.
                        </p>
                    )}
                </Panel>
            )}
            <form className="mt-5 space-y-5" onSubmit={(event) => { event.preventDefault(); void submit(); }}>
                <Panel title="Header">
                    <div className="grid gap-4 md:grid-cols-4">
                        <Select label="Payment type" value={paymentType} options={typeOptions} disabled={invoiceId !== null} onChange={(event) => {
                            const value = event.target.value;
                            setPaymentType(value);
                            if (value === PAYMENT_TYPE_SUPPLIER_PAYMENT) setDirection(PAYMENT_DIRECTION_OUTBOUND);
                            if (value === PAYMENT_TYPE_CUSTOMER_RECEIPT || value === 'advance') setDirection(PAYMENT_DIRECTION_INBOUND);
                            setParty(null);
                        }} />
                        <Select label="Direction" value={direction} options={directionOptions} disabled={invoiceId !== null} onChange={(event) => setDirection(event.target.value)} />
                        <Input label="Payment date" type="date" value={paymentDate} onChange={(event) => setPaymentDate(event.target.value)} />
                        <Input label="Reference" value={reference} onChange={(event) => setReference(event.target.value)} />
                        {invoiceId !== null ? (
                            <div>
                                <div className="mb-1.5 block text-sm font-medium text-slate-700">Settlement party</div>
                                <div className="min-h-10 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900">
                                    {readableRelation(party)}
                                </div>
                            </div>
                        ) : partyType === 'customer'
                            ? <CustomerLookupSelect value={party as never} onChange={(next) => setParty(next as NamedResource | null)} />
                            : <SupplierLookupSelect value={party as never} onChange={(next) => setParty(next as NamedResource | null)} />}
                    </div>
                </Panel>

                <Panel title="Payment methods">
                    <PaymentLineTable
                        lines={lines}
                        methods={methodRows}
                        methodsLoading={methods.loading}
                        total={total}
                        onLineChange={updateLine}
                        onMetadataChange={updateMetadata}
                        onAddLine={() => {
                            setLines((current) => [...current, emptyLine(nextKey)]);
                            setNextKey((value) => value + 1);
                        }}
                        onRemoveLine={(key) => setLines((current) => current.filter((item) => item.key !== key))}
                    />
                </Panel>

                <div className="flex justify-end">
                    <Button type="submit" loading={busy} disabled={!canSubmit}>
                        {settlementInvoice.data?.direction === PAYMENT_DIRECTION_OUTBOUND
                            ? 'Create customer receipt'
                            : settlementInvoice.data
                                ? 'Create supplier payment'
                                : 'Create payment'}
                    </Button>
                </div>
            </form>
        </>
    );
}