import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { compareDecimalStrings, isPositiveDecimal, sumDecimals } from '@/shared/utils/decimal';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import { SupplierLookupSelect } from '@/modules/supplier/components/SupplierLookupSelect';
import { getInvoice } from '@/modules/invoice/invoiceApi';
import { createPayment, listPaymentMethods, type PaymentLinePayload, type PaymentMethod } from '../paymentApi';
import { PaymentLineTable, type PaymentLineDraft } from '../components/PaymentLineTable';
import { InvoiceSettlementSelector, type InvoiceAllocationDraft } from '../components/InvoiceSettlementSelector';

const typeOptions = [
    { value: 'customer_receipt', label: 'Customer receipt' },
    { value: 'supplier_payment', label: 'Supplier payment' },
    { value: 'advance', label: 'Advance / deposit' },
    { value: 'refund', label: 'Refund' },
    { value: 'manual', label: 'Manual payment' },
];

const directionOptions = [
    { value: 'inbound', label: 'Inbound' },
    { value: 'outbound', label: 'Outbound' },
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
        instrument_direction: direction === 'outbound' ? 'issued' : 'received',
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

export default function PaymentEntryPage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const urlInvoiceId = searchParams.get('invoice_id') ? Number(searchParams.get('invoice_id')) : null;
    const [paymentType, setPaymentType] = useState('customer_receipt');
    const [direction, setDirection] = useState('inbound');
    const [paymentDate, setPaymentDate] = useState(today());
    const [reference, setReference] = useState('');
    const [party, setParty] = useState<NamedResource | null>(null);
    const [lines, setLines] = useState<PaymentLineDraft[]>([emptyLine(1)]);
    const [invoiceAllocations, setInvoiceAllocations] = useState<InvoiceAllocationDraft[]>([]);
    const [nextKey, setNextKey] = useState(2);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        if (!urlInvoiceId) return;
        let cancelled = false;
        void getInvoice(urlInvoiceId).then((inv) => {
            if (cancelled || !inv) return;
            if (inv.party) {
                setParty({
                    id: inv.party.id ?? null,
                    name: inv.party.name ?? inv.party.legal_name ?? '',
                    code: inv.party.code ?? inv.party.number ?? '',
                } as NamedResource);
            }
            if (inv.direction === 'outbound') {
                setPaymentType('customer_receipt');
                setDirection('inbound');
            } else if (inv.direction === 'inbound') {
                setPaymentType('supplier_payment');
                setDirection('outbound');
            }
            if (inv.invoice_number) {
                setReference(`Payment for ${inv.invoice_number}`);
            }
            const bal = inv.balance_due ?? (inv.balance?.remaining_amount as string | undefined) ?? inv.grand_total;
            if (bal && isPositiveDecimal(bal)) {
                setLines([{
                    key: 1,
                    paymentMethodId: '',
                    amount: bal,
                    reference: inv.invoice_number ?? '',
                    metadata: {},
                }]);
            }
        }).catch(() => {
            // Ignore error
        });
        return () => {
            cancelled = true;
        };
    }, [urlInvoiceId]);

    const methods = useApi((signal) => listPaymentMethods({ direction, per_page: 100 }, signal), [direction]);
    const methodRows = methods.data?.data ?? [];
    const total = useMemo(() => sumDecimals(lines.map((line) => line.amount || '0.000000')), [lines]);
    const partyType = paymentType === 'supplier_payment' ? 'supplier' : 'customer';

    const activeAllocations = useMemo(() => {
        return invoiceAllocations.filter((a) => a.selected && isPositiveDecimal(a.allocatedAmount));
    }, [invoiceAllocations]);

    const totalAllocated = useMemo(() => {
        return sumDecimals(activeAllocations.map((a) => a.allocatedAmount));
    }, [activeAllocations]);

    const allocationsValid = activeAllocations.every((a) =>
        compareDecimalStrings(a.allocatedAmount, a.balanceDue) <= 0
    ) && (activeAllocations.length === 0 || compareDecimalStrings(totalAllocated, total) <= 0);

    const canSubmit = !busy
        && isPositiveDecimal(total)
        && allocationsValid
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
            const allocationsPayload = activeAllocations.map((a) => ({
                invoice_id: a.invoiceId,
                allocated_amount: a.allocatedAmount,
                allocation_date: paymentDate,
            }));
            const payment = await createPayment({
                payment_type: paymentType,
                direction,
                payment_date: paymentDate,
                party_type: party ? partyType : undefined,
                party_id: party?.id,
                reference_number: reference.trim() || undefined,
                lines: payloadLines,
                allocations: allocationsPayload.length > 0 ? allocationsPayload : undefined,
            });
            navigate(`/payments/${payment.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    return (
        <>
            <ContentHeader title="Payment entry" description="Create a payment with one or more configured settlement methods." />
            <ErrorAlert error={error ?? methods.error} />
            <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void submit(); }}>
                <Panel title="Header">
                    <div className="grid gap-4 md:grid-cols-4">
                        <Select label="Payment type" value={paymentType} options={typeOptions} onChange={(event) => {
                            const value = event.target.value;
                            setPaymentType(value);
                            if (value === 'supplier_payment') setDirection('outbound');
                            if (value === 'customer_receipt' || value === 'advance') setDirection('inbound');
                            setParty(null);
                            setInvoiceAllocations([]);
                        }} />
                        <Select label="Direction" value={direction} options={directionOptions} onChange={(event) => setDirection(event.target.value)} />
                        <Input label="Payment date" type="date" value={paymentDate} onChange={(event) => setPaymentDate(event.target.value)} />
                        <Input label="Reference" value={reference} onChange={(event) => setReference(event.target.value)} />
                        {partyType === 'customer'
                            ? <CustomerLookupSelect value={party as never} onChange={(next) => { setParty(next as NamedResource | null); setInvoiceAllocations([]); }} />
                            : <SupplierLookupSelect value={party as never} onChange={(next) => { setParty(next as NamedResource | null); setInvoiceAllocations([]); }} />}
                    </div>
                </Panel>

                {party && (
                    <Panel title="Settle open invoices">
                        <InvoiceSettlementSelector
                            partyId={party.id ?? null}
                            partyType={partyType}
                            direction={direction}
                            targetInvoiceId={urlInvoiceId}
                            targetAmount={total}
                            allocations={invoiceAllocations}
                            onChange={setInvoiceAllocations}
                            disabled={busy}
                            onApplyTotalToPayment={(allocatedSum) => {
                                if (lines.length === 1) {
                                    updateLine(lines[0].key, { amount: allocatedSum });
                                }
                            }}
                        />
                    </Panel>
                )}

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
                    <Button type="submit" loading={busy} disabled={!canSubmit}>Create payment</Button>
                </div>
            </form>
        </>
    );
}
