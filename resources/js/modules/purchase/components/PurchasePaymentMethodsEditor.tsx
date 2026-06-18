import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { sumDecimals } from '@/shared/utils/decimal';
import type { FastPurchaseOptionResource } from '../purchaseTypes';

export interface PurchasePaymentMethodRow {
    key: string;
    payment_method_id: string;
    source_account_id: string;
    amount: string;
    reference: string;
    instrument_number: string;
    instrument_date: string;
    external_bank_name: string;
    external_bank_branch: string;
}

export function blankPaymentMethodRow(amount = ''): PurchasePaymentMethodRow {
    return {
        key: typeof crypto !== 'undefined' && 'randomUUID' in crypto ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
        payment_method_id: '',
        source_account_id: '',
        amount,
        reference: '',
        instrument_number: '',
        instrument_date: '',
        external_bank_name: '',
        external_bank_branch: '',
    };
}

export function paymentRowsTotal(rows: PurchasePaymentMethodRow[]): string {
    return sumDecimals(rows.map((row) => row.amount || '0.000000'));
}

export function PurchasePaymentMethodsEditor({ rows, methods, accounts, errorFor, onChange }: {
    rows: PurchasePaymentMethodRow[];
    methods: FastPurchaseOptionResource[];
    accounts: FastPurchaseOptionResource[];
    errorFor: (field: string) => string | undefined;
    onChange: (rows: PurchasePaymentMethodRow[]) => void;
}) {
    const update = (index: number, patch: Partial<PurchasePaymentMethodRow>) => {
        onChange(rows.map((row, rowIndex) => rowIndex === index ? { ...row, ...patch } : row));
    };
    const addRow = () => onChange([...rows, blankPaymentMethodRow()]);
    const removeRow = (index: number) => onChange(rows.length === 1 ? [blankPaymentMethodRow()] : rows.filter((_, rowIndex) => rowIndex !== index));

    return (
        <div className="space-y-3">
            <div className="overflow-x-auto rounded-lg border border-slate-200">
                <table className="min-w-[980px] w-full table-fixed text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th className="w-44 px-3 py-2">Method</th>
                            <th className="w-52 px-3 py-2">Account</th>
                            <th className="w-36 px-3 py-2">Amount</th>
                            <th className="w-44 px-3 py-2">Reference</th>
                            <th className="w-40 px-3 py-2">Instrument</th>
                            <th className="w-36 px-3 py-2">Date</th>
                            <th className="w-24 px-3 py-2" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 bg-white">
                        {rows.map((row, index) => (
                            <tr key={row.key} className="align-top">
                                <td className="px-3 py-3">
                                    <Select
                                        value={row.payment_method_id}
                                        options={methods.map((method) => ({ value: method.id, label: `${method.code ?? ''} ${method.name ?? ''}`.trim() }))}
                                        onChange={(event) => update(index, { payment_method_id: event.target.value })}
                                        error={errorFor(`lines.${index}.payment_method_id`) ?? errorFor(`payment.lines.${index}.payment_method_id`)}
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Select
                                        value={row.source_account_id}
                                        options={accounts.map((account) => ({ value: account.id, label: `${account.code ?? ''} ${account.name ?? ''}`.trim() }))}
                                        onChange={(event) => update(index, { source_account_id: event.target.value })}
                                        error={errorFor(`lines.${index}.source_account_id`) ?? errorFor(`payment.lines.${index}.source_account_id`)}
                                    />
                                </td>
                                <td className="px-3 py-3"><DecimalInput value={row.amount} onChange={(event) => update(index, { amount: event.target.value })} error={errorFor(`lines.${index}.amount`) ?? errorFor(`payment.lines.${index}.amount`)} /></td>
                                <td className="px-3 py-3"><Input value={row.reference} onChange={(event) => update(index, { reference: event.target.value })} error={errorFor(`lines.${index}.reference`) ?? errorFor(`payment.lines.${index}.reference`)} /></td>
                                <td className="px-3 py-3"><Input value={row.instrument_number} onChange={(event) => update(index, { instrument_number: event.target.value })} error={errorFor(`lines.${index}.instrument_number`) ?? errorFor(`payment.lines.${index}.instrument_number`)} /></td>
                                <td className="px-3 py-3"><Input type="date" value={row.instrument_date} onChange={(event) => update(index, { instrument_date: event.target.value })} error={errorFor(`lines.${index}.instrument_date`) ?? errorFor(`payment.lines.${index}.instrument_date`)} /></td>
                                <td className="px-3 py-3 text-right"><Button type="button" variant="ghost" className="min-h-9 px-3" onClick={() => removeRow(index)}>Remove</Button></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <Button type="button" variant="secondary" onClick={addRow}>Add payment method</Button>
                <span className="text-sm font-semibold text-slate-800">Rows total: {paymentRowsTotal(rows)}</span>
            </div>
        </div>
    );
}
