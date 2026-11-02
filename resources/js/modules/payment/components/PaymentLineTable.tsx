import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { PaymentMethod } from '../paymentApi';
import { PaymentMethodFields } from './PaymentMethodFields';
import { PaymentSummary } from './PaymentSummary';

export type PaymentLineDraft = {
    key: number;
    paymentMethodId: string;
    amount: string;
    reference: string;
    metadata: Record<string, string>;
};

interface PaymentLineTableProps {
    lines: PaymentLineDraft[];
    methods: PaymentMethod[];
    methodsLoading: boolean;
    total: string;
    onLineChange: (key: number, patch: Partial<PaymentLineDraft>) => void;
    onMetadataChange: (key: number, field: string, value: string) => void;
    onAddLine: () => void;
    onRemoveLine: (key: number) => void;
}

function methodKind(method?: PaymentMethod): string {
    const type = method?.method_type ?? '';
    if (['bank_transfer', 'direct_debit'].includes(type)) return 'bank_transfer';
    if (['digital_wallet', 'mobile_wallet'].includes(type)) return 'wallet';
    return type || 'other';
}

function methodLabel(method: PaymentMethod): string {
    return `${method.name}${method.method_type ? ` / ${method.method_type.replaceAll('_', ' ')}` : ''}`;
}

export function PaymentLineTable({
    lines,
    methods,
    methodsLoading,
    total,
    onLineChange,
    onMetadataChange,
    onAddLine,
    onRemoveLine,
}: PaymentLineTableProps) {
    const methodOptions = methods.map((method) => ({
        value: String(method.id),
        label: methodLabel(method),
    }));

    return (
        <div className="space-y-4">
            {lines.map((line) => {
                const method = methods.find((row) => String(row.id) === line.paymentMethodId);
                const kind = methodKind(method);

                return (
                    <div key={line.key} className="rounded-lg border border-slate-200 bg-white p-4">
                        <div className="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_220px_auto] md:items-end">
                            <Select
                                label="Method"
                                value={line.paymentMethodId}
                                options={methodOptions}
                                placeholder={methodsLoading ? 'Loading methods...' : 'Configured method'}
                                onChange={(event) => onLineChange(line.key, { paymentMethodId: event.target.value, metadata: {} })}
                            />
                            <DecimalInput label="Amount" value={line.amount} onChange={(event) => onLineChange(line.key, { amount: event.target.value })} />
                            <Input label="Line reference" value={line.reference} onChange={(event) => onLineChange(line.key, { reference: event.target.value })} />
                            <Button type="button" variant="danger" disabled={lines.length === 1} onClick={() => onRemoveLine(line.key)}>Remove</Button>
                        </div>
                        <PaymentMethodFields
                            kind={kind}
                            metadata={line.metadata}
                            onChange={(field, value) => onMetadataChange(line.key, field, value)}
                        />
                    </div>
                );
            })}

            <div className="flex flex-wrap items-center justify-between gap-3">
                <Button type="button" variant="secondary" onClick={onAddLine}>Add method</Button>
                <PaymentSummary total={total} lineCount={lines.length} />
            </div>
        </div>
    );
}
