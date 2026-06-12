import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DecimalInput } from '@/shared/components/DecimalInput';
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

type LineDraft = {
    key: number;
    paymentMethodId: string;
    amount: string;
    reference: string;
    metadata: Record<string, string>;
};

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

function emptyLine(key: number): LineDraft {
    return { key, paymentMethodId: '', amount: '0.000000', reference: '', metadata: {} };
}

function methodKind(method?: PaymentMethod): string {
    const type = method?.method_type ?? '';
    if (['bank_transfer', 'bank', 'transfer'].includes(type)) return 'bank_transfer';
    if (['wallet', 'mobile_wallet', 'online'].includes(type)) return 'wallet';
    return type || 'custom';
}

export default function PaymentEntryPage() {
    const navigate = useNavigate();
    const [paymentType, setPaymentType] = useState('customer_receipt');
    const [direction, setDirection] = useState('inbound');
    const [paymentDate, setPaymentDate] = useState(today());
    const [reference, setReference] = useState('');
    const [party, setParty] = useState<NamedResource | null>(null);
    const [lines, setLines] = useState<LineDraft[]>([emptyLine(1)]);
    const [nextKey, setNextKey] = useState(2);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const methods = useApi((signal) => listPaymentMethods({ direction, per_page: 100 }, signal), [direction]);
    const methodRows = methods.data?.data ?? [];
    const methodOptions = methodRows.map((method) => ({
        value: String(method.id),
        label: `${method.name}${method.method_type ? ` / ${method.method_type.replaceAll('_', ' ')}` : ''}`,
    }));
    const total = useMemo(() => sumDecimals(lines.map((line) => line.amount || '0.000000')), [lines]);
    const partyType = paymentType === 'supplier_payment' ? 'supplier' : 'customer';

    const updateLine = (key: number, patch: Partial<LineDraft>) => {
        setLines((current) => current.map((line) => line.key === key ? { ...line, ...patch } : line));
    };
    const updateMetadata = (key: number, field: string, value: string) => {
        setLines((current) => current.map((line) => line.key === key
            ? { ...line, metadata: { ...line.metadata, [field]: value } }
            : line));
    };

    async function submit() {
        setBusy(true);
        setError(null);
        try {
            const payloadLines: PaymentLinePayload[] = lines.map((line) => ({
                payment_method_id: line.paymentMethodId ? Number(line.paymentMethodId) : undefined,
                amount: line.amount,
                reference_number: line.reference || undefined,
                metadata: Object.fromEntries(Object.entries(line.metadata).filter(([, value]) => value !== '')),
            }));
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
                    <div className="space-y-4">
                        {lines.map((line) => {
                            const method = methodRows.find((row) => row.id === Number(line.paymentMethodId));
                            const kind = methodKind(method);
                            return (
                                <div key={line.key} className="rounded-lg border border-slate-200 bg-white p-4">
                                    <div className="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_220px_auto] md:items-end">
                                        <Select label="Method" value={line.paymentMethodId} options={methodOptions} placeholder={methods.loading ? 'Loading methods...' : 'Configured method'} onChange={(event) => updateLine(line.key, { paymentMethodId: event.target.value, metadata: {} })} />
                                        <DecimalInput label="Amount" value={line.amount} onChange={(event) => updateLine(line.key, { amount: event.target.value })} />
                                        <Input label="Line reference" value={line.reference} onChange={(event) => updateLine(line.key, { reference: event.target.value })} />
                                        <Button type="button" variant="danger" disabled={lines.length === 1} onClick={() => setLines((current) => current.filter((item) => item.key !== line.key))}>Remove</Button>
                                    </div>
                                    {['cheque', 'bank_transfer', 'card'].includes(kind) && (
                                        <div className="mt-4 grid gap-4 md:grid-cols-4">
                                            {kind === 'cheque' && <>
                                                <Input label="Cheque number" value={line.metadata.cheque_number ?? ''} onChange={(event) => updateMetadata(line.key, 'cheque_number', event.target.value)} />
                                                <Input label="Cheque date" type="date" value={line.metadata.cheque_date ?? ''} onChange={(event) => updateMetadata(line.key, 'cheque_date', event.target.value)} />
                                                <Input label="Value date" type="date" value={line.metadata.value_date ?? ''} onChange={(event) => updateMetadata(line.key, 'value_date', event.target.value)} />
                                                <Input label="Bank account" value={line.metadata.bank_account ?? ''} onChange={(event) => updateMetadata(line.key, 'bank_account', event.target.value)} />
                                            </>}
                                            {kind === 'bank_transfer' && <>
                                                <Input label="Transfer reference" value={line.metadata.transfer_reference ?? ''} onChange={(event) => updateMetadata(line.key, 'transfer_reference', event.target.value)} />
                                                <Input label="Transfer date" type="date" value={line.metadata.transfer_date ?? ''} onChange={(event) => updateMetadata(line.key, 'transfer_date', event.target.value)} />
                                                <Input label="Settlement date" type="date" value={line.metadata.settlement_date ?? ''} onChange={(event) => updateMetadata(line.key, 'settlement_date', event.target.value)} />
                                                <Input label="Bank account" value={line.metadata.bank_account ?? ''} onChange={(event) => updateMetadata(line.key, 'bank_account', event.target.value)} />
                                            </>}
                                            {kind === 'card' && <>
                                                <Input label="Terminal" value={line.metadata.terminal ?? ''} onChange={(event) => updateMetadata(line.key, 'terminal', event.target.value)} />
                                                <Input label="Authorization code" value={line.metadata.authorization_code ?? ''} onChange={(event) => updateMetadata(line.key, 'authorization_code', event.target.value)} />
                                                <Input label="Card reference" value={line.metadata.card_reference ?? ''} onChange={(event) => updateMetadata(line.key, 'card_reference', event.target.value)} />
                                                <Input label="Card type" value={line.metadata.card_type ?? ''} onChange={(event) => updateMetadata(line.key, 'card_type', event.target.value)} />
                                            </>}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <Button type="button" variant="secondary" onClick={() => {
                            setLines((current) => [...current, emptyLine(nextKey)]);
                            setNextKey((value) => value + 1);
                        }}>Add method</Button>
                        <div className="text-sm font-semibold text-slate-800">Total {total}</div>
                    </div>
                </Panel>

                <div className="flex justify-end">
                    <Button type="submit" loading={busy}>Create payment</Button>
                </div>
            </form>
        </>
    );
}
