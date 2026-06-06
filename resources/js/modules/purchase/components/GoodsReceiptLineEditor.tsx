import { Input } from '@/shared/components/Input';
import type { PurchaseOrderLine } from '../purchaseApi';

export interface EditableGoodsReceiptLine {
    source: PurchaseOrderLine;
    received_quantity: string;
    accepted_quantity: string;
    rejected_quantity: string;
}

export function GoodsReceiptLineEditor({ lines, onChange, errorFor }: {
    lines: EditableGoodsReceiptLine[];
    onChange: (lines: EditableGoodsReceiptLine[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const update = (index: number, line: EditableGoodsReceiptLine) => {
        onChange(lines.map((current, currentIndex) => currentIndex === index ? line : current));
    };

    return (
        <div className="overflow-x-auto rounded-lg border border-slate-200">
            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>{['Item', 'UOM', 'Remaining', 'Unit price', 'Received', 'Accepted', 'Rejected'].map((header) => <th key={header} className="px-4 py-3 font-semibold">{header}</th>)}</tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {lines.map((line, index) => (
                        <tr key={line.source.id}>
                            <td className="px-4 py-3">{line.source.item?.name ?? '-'}</td>
                            <td className="px-4 py-3">{line.source.uom?.code ?? '-'}</td>
                            <td className="px-4 py-3 tabular-nums">{line.source.remaining_receivable_quantity ?? line.source.remaining_quantity ?? '0.000000'}</td>
                            <td className="px-4 py-3 tabular-nums">{line.source.unit_price}</td>
                            <td className="min-w-40 px-4 py-3"><Input type="number" min="0" step="0.000001" value={line.received_quantity} error={errorFor(`lines.${index}.received_quantity`)} onChange={(event) => update(index, { ...line, received_quantity: event.target.value })} /></td>
                            <td className="min-w-40 px-4 py-3"><Input type="number" min="0" step="0.000001" value={line.accepted_quantity} error={errorFor(`lines.${index}.accepted_quantity`)} onChange={(event) => update(index, { ...line, accepted_quantity: event.target.value })} /></td>
                            <td className="min-w-40 px-4 py-3"><Input type="number" min="0" step="0.000001" value={line.rejected_quantity} error={errorFor(`lines.${index}.rejected_quantity`)} onChange={(event) => update(index, { ...line, rejected_quantity: event.target.value })} /></td>
                        </tr>
                    ))}
                </tbody>
            </table>
            {lines.length === 0 && <div className="px-4 py-10 text-center text-sm text-slate-500">Select a purchase order with receivable lines.</div>}
        </div>
    );
}
