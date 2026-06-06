import { Input } from '@/shared/components/Input';
import type { ReturnableLine } from '../purchaseApi';

export interface EditableReturnLine {
    source: ReturnableLine;
    returned_quantity: string;
    reason: string;
}

export function PurchaseReturnLineEditor({ lines, onChange, errorFor }: {
    lines: EditableReturnLine[];
    onChange: (lines: EditableReturnLine[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    return (
        <div className="overflow-x-auto rounded-lg border border-slate-200">
            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>{['Item', 'UOM', 'Returnable', 'Unit price', 'Return qty', 'Reason'].map((header) => <th key={header} className="px-4 py-3 font-semibold">{header}</th>)}</tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {lines.map((line, index) => (
                        <tr key={line.source.id}>
                            <td className="px-4 py-3">{line.source.item?.name ?? '-'}</td>
                            <td className="px-4 py-3">{line.source.uom?.code ?? '-'}</td>
                            <td className="px-4 py-3 tabular-nums">{line.source.returnable_quantity}</td>
                            <td className="px-4 py-3 tabular-nums">{line.source.unit_price}</td>
                            <td className="min-w-44 px-4 py-3"><Input type="number" min="0" step="0.000001" value={line.returned_quantity} error={errorFor(`lines.${index}.returned_quantity`)} onChange={(event) => onChange(lines.map((current, currentIndex) => currentIndex === index ? { ...current, returned_quantity: event.target.value } : current))} /></td>
                            <td className="min-w-56 px-4 py-3"><Input value={line.reason} error={errorFor(`lines.${index}.reason`)} onChange={(event) => onChange(lines.map((current, currentIndex) => currentIndex === index ? { ...current, reason: event.target.value } : current))} /></td>
                        </tr>
                    ))}
                </tbody>
            </table>
            {lines.length === 0 && <div className="px-4 py-10 text-center text-sm text-slate-500">Select a GRN with returnable lines.</div>}
        </div>
    );
}
