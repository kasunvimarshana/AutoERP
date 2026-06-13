import { DecimalInput } from '@/shared/components/DecimalInput';

export type PurchaseInvoiceSourceType = 'goods_receipt_note' | 'purchase_order';

export interface EditablePurchaseInvoiceLine {
    sourceType: PurchaseInvoiceSourceType;
    sourceId: number;
    sourceLabel: string;
    lineId: number;
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
    return (
        <div className="overflow-x-auto rounded-lg border border-slate-200">
            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        {['Source', 'Item', 'Source qty', 'Previously invoiced', 'Remaining', 'Invoice qty']
                            .map((header) => <th key={header} className="px-4 py-3 font-semibold">{header}</th>)}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {lines.map((line, index) => (
                        <tr key={`${line.sourceType}-${line.lineId}`}>
                            <td className="px-4 py-3">{line.sourceLabel}</td>
                            <td className="px-4 py-3">{line.itemName}</td>
                            <td className="px-4 py-3 tabular-nums">{line.sourceQty}</td>
                            <td className="px-4 py-3 tabular-nums">{line.previouslyInvoiced}</td>
                            <td className="px-4 py-3 tabular-nums">{line.remainingQty}</td>
                            <td className="min-w-44 px-4 py-3">
                                <DecimalInput
                                    value={line.quantity}
                                    error={errorFor(`sources.${sourceIndex(line)}.line_quantities.${line.lineId}`)}
                                    onChange={(event) => onChange(lines.map((current, currentIndex) => (
                                        currentIndex === index
                                            ? { ...current, quantity: event.target.value }
                                            : current
                                    )))}
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
    );
}
