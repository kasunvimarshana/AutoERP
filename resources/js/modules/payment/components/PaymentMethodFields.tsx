import { Input } from '@/shared/components/Input';

interface PaymentMethodFieldsProps {
    kind: string;
    metadata: Record<string, string>;
    onChange: (field: string, value: string) => void;
}

export function PaymentMethodFields({ kind, metadata, onChange }: PaymentMethodFieldsProps) {
    if (!['cheque', 'bank_transfer', 'card', 'wallet'].includes(kind)) return null;

    return (
        <div className="mt-4 grid gap-4 md:grid-cols-4">
            {kind === 'cheque' && <>
                <Input label="Cheque number" value={metadata.cheque_number ?? ''} onChange={(event) => onChange('cheque_number', event.target.value)} />
                <Input label="Cheque date" type="date" value={metadata.cheque_date ?? ''} onChange={(event) => onChange('cheque_date', event.target.value)} />
                <Input label="External bank" value={metadata.bank_account ?? ''} onChange={(event) => onChange('bank_account', event.target.value)} />
            </>}
            {kind === 'bank_transfer' && <>
                <Input label="Transfer reference" value={metadata.transfer_reference ?? ''} onChange={(event) => onChange('transfer_reference', event.target.value)} />
                <Input label="Transfer date" type="date" value={metadata.transfer_date ?? ''} onChange={(event) => onChange('transfer_date', event.target.value)} />
                <Input label="External bank" value={metadata.bank_account ?? ''} onChange={(event) => onChange('bank_account', event.target.value)} />
            </>}
            {kind === 'card' && <>
                <Input label="Terminal or acquirer" value={metadata.terminal ?? ''} onChange={(event) => onChange('terminal', event.target.value)} />
                <Input label="Authorization code" value={metadata.authorization_code ?? ''} onChange={(event) => onChange('authorization_code', event.target.value)} />
                <Input label="Card reference" value={metadata.card_reference ?? ''} onChange={(event) => onChange('card_reference', event.target.value)} />
            </>}
            {kind === 'wallet' && <>
                <Input label="Wallet reference" value={metadata.wallet_reference ?? ''} onChange={(event) => onChange('wallet_reference', event.target.value)} />
                <Input label="Provider" value={metadata.provider ?? ''} onChange={(event) => onChange('provider', event.target.value)} />
            </>}
        </div>
    );
}
