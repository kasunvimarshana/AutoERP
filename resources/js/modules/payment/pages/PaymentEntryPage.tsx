import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { sumDecimals } from '@/shared/utils/decimal';
import { CustomerLookupSelect } from '@/modules/sales/components/SalesLookups';
import { SupplierLookupSelect } from '@/modules/purchase/components/PurchaseLookups';
import { createPayment, listPaymentMethods, type PaymentLinePayload, type PaymentMethod } from '../paymentApi';
import { PaymentLineTable, type PaymentLineDraft } from '../components/PaymentLineTable';

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
    return new Date().toISOString().slice(0, 10);
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

function linePayload(line: PaymentLineDraft, method?: PaymentMethod, direction?: string): PaymentLinePayload {
    const metadata = Object.fromEntries(Object.entries(line.metadata).filter(([, value]) => value !== ''));
    const kind = methodKind(method);
    const payload: PaymentLinePayload = {
        payment_method_id: line.paymentMethodId ? Number(line.paymentMethodId) : undefined,
        amount: line.amount,
        reference_number: line.reference || undefined,
        instrument_direction: direction === 'outbound' ? 'issued' : 'received',
        metadata,
    };

    if (kind === 'cheque') {
        payload.instrument_number = line.metadata.cheque_number || undefined;
        payload.instrument_date = line.metadata.cheque_date || undefined;
        payload.deposit_date = line.metadata.value_date || undefined;
        payload.external_bank_name = line.metadata.bank_account || undefined;
    }
    if (kind === 'bank_transfer') {
        payload.instrument_number = line.metadata.transfer_reference || undefined;
        payload.instrument_date = line.metadata.transfer_date || undefined;
        payload.realized_date = line.metadata.settlement_date || undefined;
        payload.external_bank_name = line.metadata.bank_account || undefined;
    }
    if (kind === 'card') {
        payload.instrument_number = line.metadata.card_reference || line.metadata.authorization_code || undefined;
        payload.external_bank_name = line.metadata.terminal || undefined;
    }
    if (kind === 'wallet') {
        payload.instrument_number = line.metadata.wallet_reference || undefined;
        payload.realized_date = line.metadata.settlement_date || undefined;
        payload.external_bank_name = line.metadata.provider || undefined;
    }

    return payload;
}

export default function PaymentEntryPage() {
    const navigate = useNavigate();
    const [paymentType, setPaymentType] = useState('customer_receipt');
    const [direction, setDirection] = useState('inbound');
    const [paymentDate, setPaymentDate] = useState(today());
    const [reference, setReference] = useState('');
    const [party, setParty] = useState<NamedResource | null>(null);
    const [lines, setLines] = useState<PaymentLineDraft[]>([emptyLine(1)]);
    const [nextKey, setNextKey] = useState(2);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const methods = useApi((signal) => listPaymentMethods({ direction, per_page: 100 }, signal), [direction]);
    const methodRows = methods.data?.data ?? [];
    const total = useMemo(() => sumDecimals(lines.map((line) => line.amount || '0.000000')), [lines]);
    const partyType = paymentType === 'supplier_payment' ? 'supplier' : 'customer';

    const updateLine = (key: number, patch: Partial<PaymentLineDraft>) => {
        setLines((current) => current.map((line) => line.key === key ? { ...line, ...patch } : line));
    };
    const updateMetadata = (key: number, field: string, value: string) => {
        setLines((current) => current.map((line) => line.key === key
            ? { ...line, metadata: { ...line.metadata, [field]: value } }
            : line));
    };

    async function submit() {
        if (busy) return;
        setBusy(true);
        setError(null);
        try {
            const payloadLines: PaymentLinePayload[] = lines.map((line) => linePayload(
                line,
                methodRows.find((method) => String(method.id) === line.paymentMethodId),
                direction,
            ));
            const payment = await createPayment({
                payment_type: paymentType,
                direction,
                payment_date: paymentDate,
                party_type: party ? partyType : undefined,
                party_id: party?.id,
                reference_number: reference || undefined,
                lines: payloadLines,
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
                        }} />
                        <Select label="Direction" value={direction} options={directionOptions} onChange={(event) => setDirection(event.target.value)} />
                        <Input label="Payment date" type="date" value={paymentDate} onChange={(event) => setPaymentDate(event.target.value)} />
                        <Input label="Reference" value={reference} onChange={(event) => setReference(event.target.value)} />
                        {partyType === 'customer'
                            ? <CustomerLookupSelect value={party} onChange={setParty} />
                            : <SupplierLookupSelect value={party} onChange={setParty} />}
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
                    <Button type="submit" loading={busy}>Create payment</Button>
                </div>
            </form>
        </>
    );
}
