import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { getInvoice, getInvoiceSignedPrintLink, getInvoiceWhatsAppShare } from '@/modules/invoice/invoiceApi';
import type { Invoice } from '@/modules/invoice/invoiceTypes';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { compareDecimalStrings, isNonNegativeDecimal, isPositiveDecimal, nonNegativeDecimal, subtractDecimal, sumDecimals } from '@/shared/utils/decimal';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { openSameOriginUrl } from '@/shared/utils/safeNavigation';
import { closePendingWhatsAppWindow, confirmPendingWhatsAppShare, navigateToWhatsApp, openPendingWhatsAppWindow } from '@/shared/utils/whatsAppNavigation';
import { readableRelation } from '@/shared/utils/object';
import { parsePositiveInteger } from '@/shared/utils/routeParams';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import { SupplierLookupSelect } from '@/modules/supplier/components/SupplierLookupSelect';
import { SellingPaymentSuccess } from '@/modules/selling/components/SellingPaymentSuccess';
import { createPayment, finalizeSellingPayment, listPaymentMethods, type Payment, type PaymentLinePayload, type PaymentMethod } from '../paymentApi';
import { PAYMENT_AMOUNT_FRACTION_DIGITS, PaymentLineTable, type PaymentLineDraft } from '../components/PaymentLineTable';
import { PaymentMethodFields } from '../components/PaymentMethodFields';

const PAYMENT_TYPE_CUSTOMER_RECEIPT = 'customer_receipt';
const PAYMENT_TYPE_SUPPLIER_PAYMENT = 'supplier_payment';
const PAYMENT_DIRECTION_INBOUND = 'inbound';
const PAYMENT_DIRECTION_OUTBOUND = 'outbound';
const ALLOCATION_METHOD_SPECIFIC_INVOICE = 'specific_invoice';
const SETTLEABLE_INVOICE_STATUSES = ['posted', 'partially_paid'] as const;
const SELLING_INVOICE_TYPE = 'sales';
const ZERO_AMOUNT = '0.000000';
const SELLING_PAYMENT_METHODS = [
    { kind: 'cash', label: 'Cash', section: 'border-emerald-500 bg-emerald-50/60', badge: 'bg-emerald-100 text-emerald-900' },
    { kind: 'card', label: 'Card', section: 'border-blue-500 bg-blue-50/60', badge: 'bg-blue-100 text-blue-900' },
    { kind: 'bank_transfer', label: 'Bank transfer', section: 'border-violet-500 bg-violet-50/60', badge: 'bg-violet-100 text-violet-900' },
] as const;
type SellingPaymentKind = typeof SELLING_PAYMENT_METHODS[number]['kind'];
type SellingPaymentRow = PaymentLineDraft & { kind: SellingPaymentKind; cashReceivedAmount: string };
const CARD_BRANDS = [
    { value: 'visa', label: 'VISA' },
    { value: 'master', label: 'MASTER' },
    { value: 'amex', label: 'AMEX' },
] as const;

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

function emptySellingRows(): SellingPaymentRow[] {
    return SELLING_PAYMENT_METHODS.map(({ kind }, index) => ({
        ...emptyLine(index + 1),
        kind,
        cashReceivedAmount: ZERO_AMOUNT,
    }));
}

function methodKind(method?: PaymentMethod): string {
    const type = method?.method_type ?? '';
    if (['bank_transfer', 'direct_debit'].includes(type)) return 'bank_transfer';
    if (['digital_wallet', 'mobile_wallet'].includes(type)) return 'wallet';
    return type || 'other';
}

function linePayload(line: PaymentLineDraft & { cashReceivedAmount?: string }, method: PaymentMethod, direction: string): PaymentLinePayload {
    const kind = methodKind(method);
    const payload: PaymentLinePayload = {
        payment_method_id: method.id,
        amount: line.amount,
        cash_received_amount: line.cashReceivedAmount && kind === 'cash' ? line.cashReceivedAmount : undefined,
        card_brand: kind === 'card' && CARD_BRANDS.some((brand) => brand.value === line.metadata.card_brand)
            ? line.metadata.card_brand as PaymentLinePayload['card_brand']
            : undefined,
        reference_number: line.reference.trim() || undefined,
        instrument_direction: direction === PAYMENT_DIRECTION_OUTBOUND ? 'issued' : 'received',
    };

    if (kind === 'cheque') {
        payload.instrument_number = line.metadata.cheque_number?.trim() || undefined;
        payload.instrument_date = line.metadata.cheque_date || undefined;
        payload.external_bank_name = line.metadata.bank_account?.trim() || undefined;
    }
    if (kind === 'bank_transfer') {
        payload.instrument_number = line.metadata.transfer_reference?.trim() || line.reference.trim() || undefined;
        payload.instrument_date = line.metadata.transfer_date || undefined;
        payload.external_bank_name = line.metadata.bank_account?.trim() || undefined;
    }
    if (kind === 'card') {
        payload.instrument_number = line.metadata.card_reference?.trim() || line.metadata.authorization_code?.trim() || line.reference.trim() || undefined;
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
    const sellingViewRequested = searchParams.get('view') === 'selling';
    const initializedInvoiceId = useRef<number | null>(null);
    const [paymentType, setPaymentType] = useState(PAYMENT_TYPE_CUSTOMER_RECEIPT);
    const [direction, setDirection] = useState(PAYMENT_DIRECTION_INBOUND);
    const [paymentDate, setPaymentDate] = useState(today());
    const [reference, setReference] = useState('');
    const [party, setParty] = useState<NamedResource | null>(null);
    const [lines, setLines] = useState<PaymentLineDraft[]>([emptyLine(1)]);
    const [sellingRows, setSellingRows] = useState<SellingPaymentRow[]>(emptySellingRows);
    const [nextKey, setNextKey] = useState(2);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [sellingMode, setSellingMode] = useState<'payment' | 'credit'>('payment');
    const [completedSellingPayment, setCompletedSellingPayment] = useState<Payment | null>(null);
    const [creditConfirmed, setCreditConfirmed] = useState(false);
    const [sharingWhatsApp, setSharingWhatsApp] = useState(false);
    const [shareError, setShareError] = useState<ApiError | null>(null);
    const [printing, setPrinting] = useState(false);
    const settlementInvoice = useApi(
        (signal) => getInvoice(invoiceId ?? 0, signal),
        [invoiceId],
        invoiceId !== null,
    );
    const invoiceIssue = settlementInvoice.data ? invoiceSettlementIssue(settlementInvoice.data) : null;
    const sellingInvoice = sellingViewRequested && settlementInvoice.data?.invoice_type === SELLING_INVOICE_TYPE
        && settlementInvoice.data.direction === PAYMENT_DIRECTION_OUTBOUND
        ? settlementInvoice.data : null;

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
        setSellingRows(emptySellingRows());
        setNextKey(2);
    }, [invoiceIssue, settlementInvoice.data]);

    const methods = useApi((signal) => listPaymentMethods({ direction, per_page: 100 }, signal), [direction]);
    const methodRows = methods.data?.data ?? [];
    const total = useMemo(() => sumDecimals(lines.map((line) => line.amount || '0.000000')), [lines]);
    const sellingBalance = sellingInvoice?.balance_due ?? ZERO_AMOUNT;
    const nonCashTotal = sumDecimals(sellingRows.filter((row) => row.kind !== 'cash').map((row) => row.amount || ZERO_AMOUNT));
    const cashRow = sellingRows.find((row) => row.kind === 'cash');
    const cashReceived = cashRow?.cashReceivedAmount || ZERO_AMOUNT;
    const cashAvailable = nonNegativeDecimal(subtractDecimal(sellingBalance, nonCashTotal));
    const cashApplied = compareDecimalStrings(cashReceived, cashAvailable) < 0 ? cashReceived : cashAvailable;
    const cashChange = nonNegativeDecimal(subtractDecimal(cashReceived, cashApplied));
    const sellingPaymentTotal = sumDecimals([nonCashTotal, cashApplied]);
    const sellingBalanceAfterPayment = nonNegativeDecimal(subtractDecimal(sellingBalance, sellingPaymentTotal));
    const activeSellingRows = sellingRows.flatMap((row) => {
        const amount = row.kind === 'cash' ? cashApplied : row.amount || ZERO_AMOUNT;
        if (compareDecimalStrings(amount, ZERO_AMOUNT) <= 0) return [];
        const available = methodRows.filter((method) => method.method_type === row.kind);
        const method = available.find((candidate) => String(candidate.id) === row.paymentMethodId)
            ?? (available.length === 1 ? available[0] : undefined);
        return [{ ...row, paymentMethodId: method ? String(method.id) : '', amount }];
    });
    const partyType = settlementInvoice.data?.party_type
        ?? (paymentType === PAYMENT_TYPE_SUPPLIER_PAYMENT ? 'supplier' : 'customer');
    const invoiceAmountValid = !settlementInvoice.data
        || compareDecimalStrings(total, settlementInvoice.data.balance_due ?? '0') <= 0;
    const sellingAmountsValid = isNonNegativeDecimal(cashReceived)
        && sellingRows.every((row) => row.kind === 'cash' || isNonNegativeDecimal(row.amount))
        && compareDecimalStrings(nonCashTotal, sellingBalance) <= 0
        && (compareDecimalStrings(cashReceived, ZERO_AMOUNT) <= 0 || compareDecimalStrings(cashApplied, ZERO_AMOUNT) > 0);
    const sellingLinesValid = activeSellingRows.length > 0 && activeSellingRows.every((row) => {
        const method = methodRows.find((candidate) => String(candidate.id) === row.paymentMethodId);
        return lineIsValid(row, method, reference, direction)
            && (!method?.requires_reference || row.reference.trim() !== '')
            && (row.kind !== 'card' || CARD_BRANDS.some((brand) => brand.value === row.metadata.card_brand));
    });
    const canSubmit = !busy
        && completedSellingPayment === null
        && invoiceIssue === null
        && (sellingInvoice ? sellingAmountsValid && sellingLinesValid : invoiceAmountValid)
        && (invoiceId === null || party !== null)
        && (sellingInvoice || lines.every((line) => lineIsValid(
            line,
            methodRows.find((method) => String(method.id) === line.paymentMethodId),
            reference,
            direction,
        )));

    async function printSellingInvoice() {
        if (!sellingInvoice || printing) return;
        setPrinting(true);
        setShareError(null);
        try {
            const link = await getInvoiceSignedPrintLink(sellingInvoice.id);
            if (!link.print_url || !openSameOriginUrl(link.print_url)) {
                throw new Error('Could not open the invoice print link.');
            }
        } catch (requestError) {
            setShareError(toApiError(requestError));
        } finally {
            setPrinting(false);
        }
    }

    async function shareSellingInvoice() {
        if (!sellingInvoice || sharingWhatsApp) return;
        const pendingWindow = openPendingWhatsAppWindow();
        setSharingWhatsApp(true);
        setShareError(null);
        try {
            const share = await getInvoiceWhatsAppShare(sellingInvoice.id);
            if (share.recipient.verification_status !== 'verified'
                && !confirmPendingWhatsAppShare(pendingWindow, 'This customer WhatsApp number has not been manually verified. Continue sharing anyway?')) {
                closePendingWhatsAppWindow(pendingWindow);
                return;
            }
            if (!navigateToWhatsApp(share.whatsapp_url, pendingWindow)) {
                throw new Error('The server returned an invalid WhatsApp link.');
            }
        } catch (requestError) {
            closePendingWhatsAppWindow(pendingWindow);
            setShareError(toApiError(requestError));
        } finally {
            setSharingWhatsApp(false);
        }
    }

    const updateLine = (key: number, patch: Partial<PaymentLineDraft>) => {
        setLines((current) => current.map((line) => line.key === key ? { ...line, ...patch } : line));
    };
    const updateMetadata = (key: number, field: string, value: string) => {
        setLines((current) => current.map((line) => line.key === key
            ? { ...line, metadata: { ...line.metadata, [field]: value } }
            : line));
    };
    const updateSellingRow = (kind: SellingPaymentKind, patch: Partial<SellingPaymentRow>) => {
        setSellingRows((current) => current.map((row) => row.kind === kind ? { ...row, ...patch } : row));
    };
    const updateSellingMetadata = (kind: SellingPaymentKind, field: string, value: string) => {
        setSellingRows((current) => current.map((row) => row.kind === kind
            ? { ...row, metadata: { ...row.metadata, [field]: value } }
            : row));
    };

    async function submit() {
        if (!canSubmit) return;
        setBusy(true);
        setError(null);
        try {
            const payloadLines = (sellingInvoice ? activeSellingRows : lines).map((line) => {
                const method = methodRows.find((candidate) => String(candidate.id) === line.paymentMethodId);
                if (!method) throw new Error('A valid payment method is required for every line.');
                return linePayload(line, method, direction);
            });
            const payment = await (sellingInvoice ? finalizeSellingPayment : createPayment)({
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
                    allocated_amount: sellingInvoice ? sellingPaymentTotal : total,
                    allocation_date: paymentDate,
                    allocation_method: ALLOCATION_METHOD_SPECIFIC_INVOICE,
                }] : undefined,
            });
            if (sellingInvoice) {
                setCompletedSellingPayment(payment);
            } else {
                navigate(`/payments/${payment.id}`);
            }
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    if (invoiceId !== null && settlementInvoice.loading) return <LoadingState />;

    if (sellingInvoice && completedSellingPayment) {
        return <SellingPaymentSuccess
            invoice={sellingInvoice}
            payment={completedSellingPayment}
            printing={printing}
            sharingWhatsApp={sharingWhatsApp}
            actionError={shareError}
            onPrint={() => void printSellingInvoice()}
            onShare={() => void shareSellingInvoice()}
        />;
    }

    if (sellingInvoice) {
        const currency = sellingInvoice.currency?.code ?? undefined;
        return <>
            <ContentHeader
                title="Record payment"
                description={`Settle sales invoice ${sellingInvoice.invoice_number ?? ''}`}
            />
            <ErrorAlert error={error ?? shareError ?? methods.error ?? settlementInvoice.error} />
            <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_306px] xl:items-start">
                <form className="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" onSubmit={(event) => { event.preventDefault(); if (sellingMode === 'payment') void submit(); }}>
                    <div className="grid gap-3 md:grid-cols-[1.5fr_1fr_1fr]">
                        <div className="rounded-lg border border-blue-100 bg-blue-50 px-5 py-4">
                            <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Invoice number</p>
                            <p className="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">{sellingInvoice.invoice_number ?? 'Sales invoice'}</p>
                            <p className="mt-1 text-sm text-blue-700">{readableRelation(sellingInvoice.party)}</p>
                        </div>
                        <div className="rounded-lg bg-blue-600 px-5 py-4 text-white">
                            <p className="text-xs font-semibold uppercase tracking-wide text-blue-100">Invoice total</p>
                            <p className="mt-3 text-2xl font-bold tabular-nums sm:text-3xl">{formatMoney(sellingInvoice.grand_total, currency)}</p>
                        </div>
                        <div className="rounded-lg bg-amber-50 px-5 py-4 text-amber-900">
                            <p className="text-xs font-semibold uppercase tracking-wide text-amber-700">Outstanding balance</p>
                            <p className="mt-3 text-2xl font-bold tabular-nums sm:text-3xl">{formatMoney(sellingBalance, currency)}</p>
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="Payment date" type="date" value={paymentDate} disabled={sellingMode === 'credit'} onChange={(event) => setPaymentDate(event.target.value)} />
                        <fieldset>
                            <legend className="mb-1.5 text-sm font-medium text-slate-700">Payment type</legend>
                            <div className="flex flex-wrap gap-2">
                                <label className="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm text-slate-900"><input type="radio" name="selling-payment-mode" checked={sellingMode === 'payment'} onChange={() => setSellingMode('payment')} />Direct payment</label>
                                {sellingInvoice.due_date && <label className="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm text-slate-900"><input type="radio" name="selling-payment-mode" checked={sellingMode === 'credit'} onChange={() => setSellingMode('credit')} />Keep on credit</label>}
                            </div>
                        </fieldset>
                    </div>

                    {sellingMode === 'payment' ? <div>
                        <h2 className="text-lg font-semibold text-slate-950">Payment amounts</h2>
                        <p className="mt-1 text-sm text-slate-600">Enter amounts for the methods you use.</p>
                        <div className="mt-3 space-y-3">
                            {SELLING_PAYMENT_METHODS.map((configuration) => {
                                const row = sellingRows.find((candidate) => candidate.kind === configuration.kind);
                                if (!row) return null;
                                const available = methodRows.filter((method) => method.method_type === row.kind);
                                const selectedMethod = available.find((method) => String(method.id) === row.paymentMethodId)
                                    ?? (available.length === 1 ? available[0] : undefined);
                                const rowAmount = row.kind === 'cash' ? row.cashReceivedAmount : row.amount;
                                const amountEntered = compareDecimalStrings(rowAmount || ZERO_AMOUNT, ZERO_AMOUNT) > 0;
                                return <section key={row.kind} className={`rounded-lg border-l-4 p-4 ${configuration.section}`}>
                                    <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(12rem,1.3fr)] sm:items-end">
                                        <div>
                                            <h3 className={`inline-flex rounded-md px-3 py-1 text-sm font-semibold ${configuration.badge}`}>{configuration.label}</h3>
                                            {available.length > 1 ? <div className="mt-2"><Select label={`${configuration.label} method`} value={row.paymentMethodId} options={available.map((method) => ({ value: String(method.id), label: method.name }))} placeholder="Select method" onChange={(event) => updateSellingRow(row.kind, { paymentMethodId: event.target.value, reference: '', metadata: {} })} /></div>
                                                : <p className="mt-1 text-sm text-slate-600">{selectedMethod?.name ?? `No active ${configuration.label.toLowerCase()} method`}</p>}
                                        </div>
                                        <DecimalInput label={row.kind === 'cash' ? 'Cash received' : `${configuration.label} amount`} maxFractionDigits={PAYMENT_AMOUNT_FRACTION_DIGITS} value={rowAmount} className="text-right text-lg font-semibold tabular-nums" disabled={available.length === 0} onFocus={(event) => event.currentTarget.select()} onChange={(event) => updateSellingRow(row.kind, row.kind === 'cash' ? { cashReceivedAmount: event.target.value } : { amount: event.target.value })} />
                                    </div>
                                    {row.kind === 'cash' && <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div className="rounded-md bg-white/70 px-4 py-2"><p className="text-xs text-slate-600">Cash amount applied</p><p className="mt-1 font-semibold tabular-nums text-slate-950">{formatMoney(cashApplied, currency)}</p></div>
                                        <div className="rounded-md bg-white/70 px-4 py-2"><p className="text-xs text-slate-600">Change to return</p><p className="mt-1 font-semibold tabular-nums text-emerald-700">{formatMoney(cashChange, currency)}</p></div>
                                    </div>}
                                    {row.kind === 'card' && <fieldset className="mt-4">
                                        <legend className="mb-2 text-sm font-medium text-slate-800">Card type</legend>
                                        <div className="flex flex-wrap gap-2">{CARD_BRANDS.map((brand) => <label key={brand.value} className="flex cursor-pointer items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"><input type="radio" name="selling-card-brand" checked={row.metadata.card_brand === brand.value} disabled={available.length === 0} onChange={() => updateSellingMetadata('card', 'card_brand', brand.value)} />{brand.label}</label>)}</div>
                                    </fieldset>}
                                    {(row.kind === 'card' || row.kind === 'bank_transfer' || selectedMethod?.requires_reference) && <div className="mt-3"><Input label="Reference details" maxLength={150} value={row.reference} disabled={available.length === 0} onChange={(event) => updateSellingRow(row.kind, { reference: event.target.value })} /></div>}
                                    {(row.kind === 'card' || row.kind === 'bank_transfer') && selectedMethod && <details className="mt-3 rounded-md bg-white/70 px-3 py-2">
                                        <summary className="cursor-pointer text-sm font-medium text-slate-700">Additional transaction details</summary>
                                        <PaymentMethodFields kind={row.kind} metadata={row.metadata} hideReferenceFields onChange={(field, value) => updateSellingMetadata(row.kind, field, value)} />
                                    </details>}
                                    {amountEntered && available.length > 1 && !selectedMethod && <p className="mt-3 text-sm font-medium text-amber-800">Select the {configuration.label.toLowerCase()} method.</p>}
                                    {amountEntered && row.kind === 'card' && !CARD_BRANDS.some((brand) => brand.value === row.metadata.card_brand) && <p className="mt-3 text-sm font-medium text-amber-800">Select the card type.</p>}
                                    {amountEntered && selectedMethod?.requires_reference && !row.reference.trim() && <p className="mt-3 text-sm font-medium text-amber-800">Enter a reference for this method.</p>}
                                </section>;
                            })}
                        </div>
                        {methodRows.some((method) => !SELLING_PAYMENT_METHODS.some(({ kind }) => kind === method.method_type)) && <LinkButton className="mt-4" variant="secondary" to={`/payments/create?invoice_id=${sellingInvoice.id}`}>Use another payment method</LinkButton>}
                    </div> : <div className="rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                        <p>This sale was already recorded on credit, due {sellingInvoice.due_date}. No payment is collected now; the invoice stays outstanding.</p>
                        {creditConfirmed && <p role="status" className="mt-3 font-semibold">No payment was created. You can print or share the invoice.</p>}
                    </div>}
                    <div className="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                        <Button variant="secondary" loading={printing} onClick={() => void printSellingInvoice()}>Print bill</Button>
                        <Button variant="secondary" loading={sharingWhatsApp} onClick={() => void shareSellingInvoice()}>Share via WhatsApp</Button>
                    </div>
                </form>
                <aside className="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm xl:sticky xl:top-5">
                    <h2 className="text-lg font-semibold text-slate-950">Payment summary</h2>
                    <div className="mt-4 border-t border-blue-200 pt-4"><p className="text-sm text-slate-700">Outstanding balance</p><p className="mt-1 text-2xl font-bold tabular-nums text-slate-950">{formatMoney(sellingBalance, currency)}</p></div>
                    <div className="mt-5 border-t border-blue-200 pt-4"><p className="text-sm text-slate-700">Payment total</p><p className="mt-1 text-2xl font-bold tabular-nums text-slate-950">{formatMoney(sellingMode === 'payment' ? sellingPaymentTotal : ZERO_AMOUNT, currency)}</p><p className="mt-1 text-xs text-slate-600">Across {sellingMode === 'payment' ? activeSellingRows.length : 0} {activeSellingRows.length === 1 ? 'method' : 'methods'}</p></div>
                    {sellingMode === 'payment' && compareDecimalStrings(cashReceived, ZERO_AMOUNT) > 0 && <div className="mt-5 border-t border-blue-200 pt-4"><p className="text-sm text-slate-700">Cash received</p><p className="mt-1 text-xl font-semibold tabular-nums text-slate-950">{formatMoney(cashReceived, currency)}</p><p className="mt-3 text-sm text-slate-700">Change to return</p><p className="mt-1 text-xl font-semibold tabular-nums text-emerald-700">{formatMoney(cashChange, currency)}</p></div>}
                    <div className="mt-5 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-4"><p className="text-sm font-medium text-emerald-900">Balance after payment</p><p className="mt-1 text-2xl font-bold tabular-nums text-emerald-900">{formatMoney(sellingMode === 'payment' ? sellingBalanceAfterPayment : sellingBalance, currency)}</p><p className="mt-1 text-xs text-emerald-800">{sellingMode === 'payment' && compareDecimalStrings(sellingBalanceAfterPayment, ZERO_AMOUNT) === 0 && sellingLinesValid ? 'Fully settled after finalizing' : 'Remaining balance after finalizing'}</p></div>
                    {sellingMode === 'payment' && !sellingAmountsValid && <p role="alert" className="mt-3 text-sm font-medium text-rose-700">Check the entered amounts. Card and bank transfer cannot exceed the balance.</p>}
                    {invoiceIssue && <p role="alert" className="mt-3 text-sm font-medium text-rose-700">{invoiceIssue}</p>}
                    {sellingMode === 'payment' ? <Button className="mt-5 w-full" loading={busy} disabled={!canSubmit} onClick={() => void submit()}>Finalize payment</Button>
                        : <Button className="mt-5 w-full" disabled={Boolean(invoiceIssue) || creditConfirmed} onClick={() => setCreditConfirmed(true)}>{creditConfirmed ? 'Credit invoice ready' : 'Keep invoice on credit'}</Button>}
                    <LinkButton className="mt-2 w-full" variant="secondary" to="/selling/sales">Cancel</LinkButton>
                    {sellingMode === 'payment' && <p className="mt-4 text-xs leading-5 text-slate-600">The receipt is posted and applied to this invoice when finalized.</p>}
                </aside>
            </div>
        </>;
    }

    return (
        <>
            <ContentHeader
                title={settlementInvoice.data ? 'Invoice settlement' : 'Payment entry'}
                description={settlementInvoice.data
                    ? 'Create the matching customer receipt or supplier payment and allocate it to this invoice atomically.'
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
