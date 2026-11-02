import { useCallback, useEffect, useMemo, useState } from 'react';
import { getInvoice, listInvoices, type Invoice } from '@/modules/invoice/invoiceApi';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { formatDate } from '@/shared/utils/formatDate';
import {
    addDecimal,
    compareDecimalStrings,
    isPositiveDecimal,
    subtractDecimal,
    sumDecimals,
} from '@/shared/utils/decimal';

export interface InvoiceAllocationDraft {
    invoiceId: number;
    invoiceNumber: string;
    invoiceDate: string;
    invoiceType?: string;
    totalAmount: string;
    balanceDue: string;
    allocatedAmount: string;
    selected: boolean;
}

interface InvoiceSettlementSelectorProps {
    partyId: number | null;
    partyType: 'customer' | 'supplier';
    direction?: string;
    targetInvoiceId?: number | null;
    targetAmount?: string;
    allocations: InvoiceAllocationDraft[];
    onChange: (allocations: InvoiceAllocationDraft[]) => void;
    disabled?: boolean;
    onApplyTotalToPayment?: (total: string) => void;
}

export function InvoiceSettlementSelector({
    partyId,
    partyType,
    direction,
    targetInvoiceId,
    targetAmount = '0.000000',
    allocations,
    onChange,
    disabled = false,
    onApplyTotalToPayment,
}: InvoiceSettlementSelectorProps) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<import('@/shared/api/apiError').ApiError | null>(null);

    const loadOpenInvoices = useCallback(async (signal?: AbortSignal) => {
        if (!partyId) {
            onChange([]);
            return;
        }
        setLoading(true);
        setError(null);
        try {
            const expectedInvoiceDirection = partyType === 'customer' ? 'outbound' : 'inbound';
            const response = await listInvoices({
                party_id: partyId,
                party_type: partyType,
                direction: expectedInvoiceDirection,
                has_balance_due: true,
                per_page: 100,
            }, signal);

            const openInvoices: Invoice[] = [...(response.data ?? [])];
            if (targetInvoiceId && !openInvoices.some((inv) => inv.id === targetInvoiceId)) {
                try {
                    const single = await getInvoice(targetInvoiceId, signal);
                    if (single) {
                        openInvoices.unshift(single);
                    }
                } catch {
                    // Ignore fallback failure
                }
            }
            const drafts: InvoiceAllocationDraft[] = openInvoices.map((inv) => {
                const balance = inv.balance_due ?? (inv.balance?.remaining_amount as string | undefined) ?? '0.000000';
                const total = inv.grand_total ?? '0.000000';
                const existing = allocations.find((a) => a.invoiceId === inv.id);
                const isTarget = Boolean(targetInvoiceId && inv.id === targetInvoiceId);
                const isSelected = existing ? existing.selected : isTarget;
                const allocated = existing ? existing.allocatedAmount : (isTarget ? balance : '0.000000');
                return {
                    invoiceId: inv.id,
                    invoiceNumber: inv.invoice_number ?? `Invoice #${inv.id}`,
                    invoiceDate: inv.invoice_date ?? '',
                    invoiceType: inv.invoice_type,
                    totalAmount: total,
                    balanceDue: balance,
                    allocatedAmount: allocated,
                    selected: isSelected,
                };
            });
            onChange(drafts);
            if (targetInvoiceId) {
                const targetInv = openInvoices.find((inv) => inv.id === targetInvoiceId);
                if (targetInv) {
                    const balance = targetInv.balance_due ?? (targetInv.balance?.remaining_amount as string | undefined) ?? '0.000000';
                    onApplyTotalToPayment?.(balance);
                }
            }
        } catch (err) {
            if (signal?.aborted) return;
            setError(err as never);
        } finally {
            if (!signal?.aborted) {
                setLoading(false);
            }
        }
    }, [partyId, partyType, direction, targetInvoiceId]);

    useEffect(() => {
        const controller = new AbortController();
        void loadOpenInvoices(controller.signal);
        return () => controller.abort();
    }, [partyId, partyType, direction]);

    const totalAllocated = useMemo(() => {
        const active = allocations.filter((a) => a.selected && isPositiveDecimal(a.allocatedAmount));
        return sumDecimals(active.map((a) => a.allocatedAmount));
    }, [allocations]);

    const totalOpenBalance = useMemo(() => {
        return sumDecimals(allocations.map((a) => a.balanceDue));
    }, [allocations]);

    const unallocatedRemaining = useMemo(() => {
        if (!isPositiveDecimal(targetAmount)) return '0.000000';
        if (compareDecimalStrings(targetAmount, totalAllocated) <= 0) return '0.000000';
        return subtractDecimal(targetAmount, totalAllocated);
    }, [targetAmount, totalAllocated]);

    const exceedsTarget = useMemo(() => {
        if (!isPositiveDecimal(targetAmount)) return false;
        return compareDecimalStrings(totalAllocated, targetAmount) > 0;
    }, [totalAllocated, targetAmount]);

    const toggleSelect = (invoiceId: number) => {
        onChange(allocations.map((item) => {
            if (item.invoiceId !== invoiceId) return item;
            const nextSelected = !item.selected;
            return {
                ...item,
                selected: nextSelected,
                allocatedAmount: nextSelected && !isPositiveDecimal(item.allocatedAmount)
                    ? item.balanceDue
                    : item.allocatedAmount,
            };
        }));
    };

    const updateAmount = (invoiceId: number, amount: string) => {
        onChange(allocations.map((item) => {
            if (item.invoiceId !== invoiceId) return item;
            return {
                ...item,
                allocatedAmount: amount,
                selected: isPositiveDecimal(amount) ? true : item.selected,
            };
        }));
    };

    const selectAll = () => {
        onChange(allocations.map((item) => ({
            ...item,
            selected: true,
            allocatedAmount: isPositiveDecimal(item.allocatedAmount) ? item.allocatedAmount : item.balanceDue,
        })));
    };

    const deselectAll = () => {
        onChange(allocations.map((item) => ({
            ...item,
            selected: false,
            allocatedAmount: '0.000000',
        })));
    };

    const autoAllocateFifo = () => {
        if (!isPositiveDecimal(targetAmount)) return;
        let remaining = targetAmount;
        const sorted = [...allocations].sort((a, b) => a.invoiceDate.localeCompare(b.invoiceDate));
        const updated = sorted.map((item) => {
            if (compareDecimalStrings(remaining, '0.000000') <= 0) {
                return { ...item, selected: false, allocatedAmount: '0.000000' };
            }
            const canPay = compareDecimalStrings(remaining, item.balanceDue) >= 0
                ? item.balanceDue
                : remaining;
            remaining = subtractDecimal(remaining, canPay);
            return {
                ...item,
                selected: isPositiveDecimal(canPay),
                allocatedAmount: canPay,
            };
        });
        // Restore original order
        onChange(allocations.map((original) => updated.find((u) => u.invoiceId === original.invoiceId) ?? original));
    };

    if (!partyId) {
        return (
            <div className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                Select a {partyType === 'customer' ? 'customer' : 'supplier'} above to view and settle outstanding invoices.
            </div>
        );
    }

    if (loading) {
        return <LoadingState />;
    }

    if (error) {
        return <ErrorAlert error={error} />;
    }

    if (allocations.length === 0) {
        return (
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                No outstanding invoices found for this {partyType === 'customer' ? 'customer' : 'supplier'}. Any payment recorded will be stored as an unapplied advance.
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="text-sm font-medium text-slate-700">
                    Outstanding Invoices ({allocations.length}) &middot; Total Due: <MoneyDisplay value={totalOpenBalance} />
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <Button type="button" variant="secondary" onClick={selectAll} disabled={disabled}>
                        Select all
                    </Button>
                    <Button type="button" variant="secondary" onClick={deselectAll} disabled={disabled}>
                        Deselect all
                    </Button>
                    {isPositiveDecimal(targetAmount) && (
                        <Button type="button" variant="secondary" onClick={autoAllocateFifo} disabled={disabled}>
                            Auto-allocate FIFO
                        </Button>
                    )}
                    {onApplyTotalToPayment && isPositiveDecimal(totalAllocated) && (
                        <Button type="button" variant="secondary" onClick={() => onApplyTotalToPayment(totalAllocated)} disabled={disabled}>
                            Set payment total to <MoneyDisplay value={totalAllocated} />
                        </Button>
                    )}
                </div>
            </div>

            {exceedsTarget && (
                <div className="rounded-md border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800">
                    Warning: Total allocated to invoices (<MoneyDisplay value={totalAllocated} />) exceeds the payment amount (<MoneyDisplay value={targetAmount} />).
                </div>
            )}

            <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table className="w-full text-left text-sm text-slate-700">
                    <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th className="w-12 px-4 py-3 text-center">
                                <span className="sr-only">Select</span>
                            </th>
                            <th className="px-4 py-3">Invoice</th>
                            <th className="px-4 py-3">Date</th>
                            <th className="px-4 py-3 text-right">Invoice Total</th>
                            <th className="px-4 py-3 text-right">Balance Due</th>
                            <th className="w-48 px-4 py-3 text-right">Amount to Settle</th>
                            <th className="px-4 py-3 text-right">Balance After</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {allocations.map((item) => {
                            const balanceAfter = subtractDecimal(
                                item.balanceDue,
                                item.selected && isPositiveDecimal(item.allocatedAmount) ? item.allocatedAmount : '0.000000',
                            );
                            const isOverAllocated = item.selected && compareDecimalStrings(item.allocatedAmount, item.balanceDue) > 0;

                            return (
                                <tr key={item.invoiceId} className={item.selected ? 'bg-sky-50/50' : 'hover:bg-slate-50/50'}>
                                    <td className="px-4 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            checked={item.selected}
                                            disabled={disabled}
                                            onChange={() => toggleSelect(item.invoiceId)}
                                            className="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                            aria-label={`Select invoice ${item.invoiceNumber}`}
                                        />
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="font-medium text-slate-900">{item.invoiceNumber}</div>
                                        {item.invoiceType && (
                                            <div className="text-xs capitalize text-slate-400">{item.invoiceType}</div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{formatDate(item.invoiceDate)}</td>
                                    <td className="px-4 py-3 text-right">
                                        <MoneyDisplay value={item.totalAmount} />
                                    </td>
                                    <td className="px-4 py-3 text-right font-medium text-slate-800">
                                        <MoneyDisplay value={item.balanceDue} />
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <DecimalInput
                                            value={item.allocatedAmount}
                                            disabled={disabled || !item.selected}
                                            onChange={(event) => updateAmount(item.invoiceId, event.target.value)}
                                            className={isOverAllocated ? 'border-red-500' : undefined}
                                        />
                                        {isOverAllocated && (
                                            <div className="mt-1 text-right text-xs text-red-600">Exceeds balance</div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <MoneyDisplay value={balanceAfter} />
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-wrap items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                <div>
                    <span className="text-slate-500">Selected Invoices to Settle: </span>
                    <span className="font-semibold text-slate-800">
                        {allocations.filter((a) => a.selected).length} of {allocations.length}
                    </span>
                </div>
                <div className="flex flex-wrap items-center gap-6">
                    <div>
                        <span className="text-slate-500">Total Allocated: </span>
                        <span className="font-semibold text-sky-700"><MoneyDisplay value={totalAllocated} /></span>
                    </div>
                    {isPositiveDecimal(targetAmount) && (
                        <div>
                            <span className="text-slate-500">Unapplied / Advance Remaining: </span>
                            <span className="font-semibold text-emerald-700"><MoneyDisplay value={unallocatedRemaining} /></span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
