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

export function PurchasePaymentMethodsEditor({ rows, methods, accounts, errorFor, errorIndexForRow, onChange }: {
    rows: PurchasePaymentMethodRow[];
    methods: FastPurchaseOptionResource[];
    accounts: FastPurchaseOptionResource[];
    errorFor: (field: string) => string | undefined;
    errorIndexForRow?: (row: PurchasePaymentMethodRow, index: number) => number;
    onChange: (rows: PurchasePaymentMethodRow[]) => void;
}) {
    const update = (index: number, patch: Partial<PurchasePaymentMethodRow>) => {
        onChange(rows.map((row, rowIndex) => rowIndex === index ? { ...row, ...patch } : row));
    };
    const addRow = () => onChange([...rows, blankPaymentMethodRow()]);
    const removeRow = (index: number) => onChange(rows.length === 1 ? [blankPaymentMethodRow()] : rows.filter((_, rowIndex) => rowIndex !== index));

    return (
        <div className="space-y-3">
            <div className="grid gap-3 md:hidden">
                {rows.map((row, index) => {
                    const method = methods.find((option) => String(option.id) === row.payment_method_id);
                    const errorIndex = errorIndexForRow?.(row, index) ?? index;

                    return (
                        <article key={row.key} className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                            <div className="grid gap-3">
                                <Select
                                    label="Method"
                                    value={row.payment_method_id}
                                    options={methods.map((option) => ({ value: option.id, label: optionLabel(option) }))}
                                    onChange={(event) => update(index, { payment_method_id: event.target.value })}
                                    error={errorFor(`lines.${errorIndex}.payment_method_id`) ?? errorFor(`payment.lines.${errorIndex}.payment_method_id`)}
                                />
                                <Select
                                    label="Account"
                                    value={row.source_account_id}
                                    options={accounts.map((account) => ({ value: account.id, label: optionLabel(account) }))}
                                    onChange={(event) => update(index, { source_account_id: event.target.value })}
                                    error={errorFor(`lines.${errorIndex}.source_account_id`) ?? errorFor(`payment.lines.${errorIndex}.source_account_id`)}
                                />
                                <Input
                                    label="Reference"
                                    value={row.reference}
                                    onChange={(event) => update(index, { reference: event.target.value })}
                                    error={errorFor(`lines.${errorIndex}.reference`) ?? errorFor(`payment.lines.${errorIndex}.reference`)}
                                />
                                <MethodSpecificFields methodType={method?.method_type} row={row} index={index} errorIndex={errorIndex} errorFor={errorFor} update={update} />
                                <DecimalInput
                                    label="Amount"
                                    value={row.amount}
                                    onChange={(event) => update(index, { amount: event.target.value })}
                                    error={errorFor(`lines.${errorIndex}.amount`) ?? errorFor(`payment.lines.${errorIndex}.amount`)}
                                />
                                <div className="flex justify-end">
                                    <Button type="button" variant="ghost" className="min-h-9 px-3" onClick={() => removeRow(index)}>Remove</Button>
                                </div>
                            </div>
                        </article>
                    );
                })}
            </div>
            <div className="hidden overflow-x-auto rounded-lg border border-slate-200 md:block">
                <table className="min-w-full table-fixed text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th className="w-44 px-3 py-2">Method</th>
                            <th className="w-52 px-3 py-2">Account</th>
                            <th className="px-3 py-2">Reference</th>
                            <th className="w-40 px-3 py-2">Amount</th>
                            <th className="w-24 px-3 py-2" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 bg-white">
                        {rows.map((row, index) => {
                            const method = methods.find((option) => String(option.id) === row.payment_method_id);
                            const errorIndex = errorIndexForRow?.(row, index) ?? index;

                            return (
                                <tr key={row.key} className="align-top">
                                <td className="px-3 py-3">
                                    <Select
                                        value={row.payment_method_id}
                                        options={methods.map((option) => ({ value: option.id, label: optionLabel(option) }))}
                                        onChange={(event) => update(index, { payment_method_id: event.target.value })}
                                        error={errorFor(`lines.${errorIndex}.payment_method_id`) ?? errorFor(`payment.lines.${errorIndex}.payment_method_id`)}
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Select
                                        value={row.source_account_id}
                                        options={accounts.map((account) => ({ value: account.id, label: optionLabel(account) }))}
                                        onChange={(event) => update(index, { source_account_id: event.target.value })}
                                        error={errorFor(`lines.${errorIndex}.source_account_id`) ?? errorFor(`payment.lines.${errorIndex}.source_account_id`)}
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <Input value={row.reference} onChange={(event) => update(index, { reference: event.target.value })} error={errorFor(`lines.${errorIndex}.reference`) ?? errorFor(`payment.lines.${errorIndex}.reference`)} />
                                    <MethodSpecificFields methodType={method?.method_type} row={row} index={index} errorIndex={errorIndex} errorFor={errorFor} update={update} />
                                </td>
                                <td className="px-3 py-3"><DecimalInput value={row.amount} onChange={(event) => update(index, { amount: event.target.value })} error={errorFor(`lines.${errorIndex}.amount`) ?? errorFor(`payment.lines.${errorIndex}.amount`)} /></td>
                                <td className="px-3 py-3 text-right"><Button type="button" variant="ghost" className="min-h-9 px-3" onClick={() => removeRow(index)}>Remove</Button></td>
                            </tr>
                            );
                        })}
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

function MethodSpecificFields({ methodType, row, index, errorIndex, errorFor, update }: {
    methodType?: string | null;
    row: PurchasePaymentMethodRow;
    index: number;
    errorIndex: number;
    errorFor: (field: string) => string | undefined;
    update: (index: number, patch: Partial<PurchasePaymentMethodRow>) => void;
}) {
    const type = methodType ?? '';
    if (type === 'cash' || type === '') return null;

    if (type === 'cheque') {
        return (
            <div className="mt-2 grid gap-2 sm:grid-cols-2">
                <Input placeholder="Cheque number" value={row.instrument_number} onChange={(event) => update(index, { instrument_number: event.target.value })} error={errorFor(`lines.${errorIndex}.instrument_number`) ?? errorFor(`payment.lines.${errorIndex}.instrument_number`)} />
                <Input type="date" value={row.instrument_date} onChange={(event) => update(index, { instrument_date: event.target.value })} error={errorFor(`lines.${errorIndex}.instrument_date`) ?? errorFor(`payment.lines.${errorIndex}.instrument_date`)} />
                <Input placeholder="Bank" value={row.external_bank_name} onChange={(event) => update(index, { external_bank_name: event.target.value })} error={errorFor(`lines.${errorIndex}.external_bank_name`) ?? errorFor(`payment.lines.${errorIndex}.external_bank_name`)} />
                <Input placeholder="Branch" value={row.external_bank_branch} onChange={(event) => update(index, { external_bank_branch: event.target.value })} error={errorFor(`lines.${errorIndex}.external_bank_branch`) ?? errorFor(`payment.lines.${errorIndex}.external_bank_branch`)} />
            </div>
        );
    }

    if (type === 'card') {
        return (
            <div className="mt-2">
                <Input placeholder="Card authorization/reference" value={row.instrument_number} onChange={(event) => update(index, { instrument_number: event.target.value })} error={errorFor(`lines.${errorIndex}.instrument_number`) ?? errorFor(`payment.lines.${errorIndex}.instrument_number`)} />
            </div>
        );
    }

    return (
        <div className="mt-2 grid gap-2 sm:grid-cols-2">
            <Input placeholder="Transfer reference" value={row.instrument_number} onChange={(event) => update(index, { instrument_number: event.target.value })} error={errorFor(`lines.${errorIndex}.instrument_number`) ?? errorFor(`payment.lines.${errorIndex}.instrument_number`)} />
            <Input type="date" value={row.instrument_date} onChange={(event) => update(index, { instrument_date: event.target.value })} error={errorFor(`lines.${errorIndex}.instrument_date`) ?? errorFor(`payment.lines.${errorIndex}.instrument_date`)} />
        </div>
    );
}

function optionLabel(option: FastPurchaseOptionResource): string {
    return `${option.code ?? ''} ${option.name ?? ''}`.trim() || 'Unnamed payment method';
}
