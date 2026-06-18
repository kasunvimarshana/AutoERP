import { DecimalInput } from '@/shared/components/DecimalInput';

export type PurchaseInvoiceSourceType = 'goods_receipt_note' | 'purchase_order';

export interface EditablePurchaseInvoiceLine {
    sourceType: PurchaseInvoiceSourceType;
    sourceId: number;
    sourceLabel: string;
    lineId: number;
    include: boolean;
    itemName: string;
    sourceQty: string;
    previouslyInvoiced: string;
    remainingQty: string;
    quantity: string;
}

export function PurchaseInvoiceLineTable({ lines, sourceIndex, onChange, errorFor }: {
    lines: EditablePurchaseInvoiceLine[];
    sourceIndex: (line: EditablePurchaseInvoiceLine) => number;
    onChange: (lines: EditablePurchaseInvoiceLine[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const updateLine = (index: number, patch: Partial<EditablePurchaseInvoiceLine>) => {
        onChange(lines.map((current, currentIndex) => currentIndex === index ? { ...current, ...patch } : current));
    };

    return (
        <div className="space-y-3">
        <div className="flex flex-wrap gap-2">
            <button type="button" className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" onClick={() => onChange(lines.map((line) => ({ ...line, include: true })))}>Select All</button>
            <button type="button" className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" onClick={() => onChange(lines.map((line) => ({ ...line, include: false, quantity: '' })))}>Clear</button>
            <button type="button" className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" onClick={() => onChange(lines.map((line) => ({ ...line, include: true, quantity: line.remainingQty })))}>Invoice All Remaining</button>
        </div>
        <div className="overflow-hidden rounded-lg border border-slate-200 md:overflow-x-auto">
            <table className="w-full divide-y divide-slate-200 text-left text-sm md:min-w-[860px]">
                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        {['Include', 'Source', 'Item', 'Source qty', 'Previously invoiced', 'Remaining', 'Quantity now']
                            .map((header) => <th key={header} className="px-4 py-3 font-semibold">{header}</th>)}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {lines.map((line, index) => (
                        <tr key={`${line.sourceType}-${line.lineId}`}>
                            <td className="px-4 py-3">
                                <input
                                    type="checkbox"
                                    checked={line.include}
                                    onChange={(event) => updateLine(index, {
                                        include: event.target.checked,
                                        quantity: event.target.checked && line.quantity === '' ? line.remainingQty : line.quantity,
                                    })}
                                />
                            </td>
                            <td className="px-4 py-3">{line.sourceLabel}</td>
                            <td className="px-4 py-3">{line.itemName}</td>
                            <td className="px-4 py-3 tabular-nums">{line.sourceQty}</td>
                            <td className="px-4 py-3 tabular-nums">{line.previouslyInvoiced}</td>
                            <td className="px-4 py-3 tabular-nums">{line.remainingQty}</td>
                            <td className="min-w-44 px-4 py-3">
                                <DecimalInput
                                    value={line.quantity}
                                    error={errorFor(`sources.${sourceIndex(line)}.line_quantities.${line.lineId}`)}
                                    onChange={(event) => updateLine(index, { quantity: event.target.value, include: event.target.value.trim() !== '' })}
                                />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
            {lines.length === 0 && (
                <div className="px-4 py-10 text-center text-sm text-slate-500">
                    Add one or more GRN/PO sources.
                </div>
            )}
        </div>
        </div>
    );
}
