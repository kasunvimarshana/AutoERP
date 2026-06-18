import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { FormDrawer } from '@/shared/components/Drawer';
import { Input } from '@/shared/components/Input';
import type { ReturnableLine } from '../purchaseApi';

export interface EditableReturnLine {
    source: ReturnableLine;
    include: boolean;
    returned_quantity: string;
    reason: string;
}

type ReturnLineDialog = { index: number; line: EditableReturnLine };

export function PurchaseReturnLineEditor({ lines, onChange, errorFor }: {
    lines: EditableReturnLine[];
    onChange: (lines: EditableReturnLine[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const [dialog, setDialog] = useState<ReturnLineDialog | null>(null);
    const updateLine = (index: number, line: EditableReturnLine) => {
        onChange(lines.map((current, currentIndex) => currentIndex === index ? line : current));
        setDialog(null);
    };
    const toggleInclude = (index: number, include: boolean) => {
        onChange(lines.map((line, currentIndex) => currentIndex === index ? { ...line, include } : line));
    };
    const selectAll = () => onChange(lines.map((line) => ({ ...line, include: true })));
    const returnSelected = () => onChange(lines.map((line) => (
        line.include ? { ...line, returned_quantity: line.source.returnable_quantity } : line
    )));
    const clearQuantities = () => onChange(lines.map((line) => ({ ...line, include: false, returned_quantity: '0.000000' })));
    const columns: DataColumn<EditableReturnLine & { rowIndex: number }>[] = [
        { key: 'include', header: 'Include', render: (line) => <input type="checkbox" className="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" checked={line.include} onChange={(event) => toggleInclude(line.rowIndex, event.target.checked)} /> },
        { key: 'item', header: 'Item', render: formatReturnItem },
        { key: 'quantity', header: 'Quantity now', render: (line) => line.returned_quantity, className: 'tabular-nums' },
        { key: 'uom', header: 'UOM', render: (line) => line.source.uom?.code ?? '-' },
        { key: 'price', header: 'Unit price', render: (line) => line.source.unit_price, className: 'tabular-nums' },
        { key: 'returnable', header: 'Remaining quantity', render: (line) => line.source.returnable_quantity, className: 'tabular-nums' },
        { key: 'reason', header: 'Reason', render: (line) => line.reason || '-' },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (line) => <button type="button" className="font-semibold text-sky-700" onClick={() => setDialog({ index: line.rowIndex, line })}>Edit line</button> },
    ];
    const rows = lines.map((line, index) => ({ ...line, rowIndex: index }));

    return (
        <>
            <div className="mb-3 flex flex-wrap gap-2">
                <Button type="button" variant="secondary" onClick={selectAll}>Select All</Button>
                <Button type="button" variant="ghost" onClick={clearQuantities}>Clear</Button>
                <Button type="button" variant="secondary" onClick={returnSelected}>Return Selected</Button>
            </div>
            <DataTable rows={rows} columns={columns} rowKey={(line) => line.source.id} emptyMessage="Select a GRN with returnable lines." mobileSummary={formatReturnItem} mobileDetails={(line) => <ReturnLineMobileDetails line={line} />} mobileActions={(line) => <button type="button" className="font-semibold text-sky-700" onClick={() => setDialog({ index: line.rowIndex, line })}>Edit line</button>} />
            <FormDrawer open={Boolean(dialog)} title="Edit return line" onClose={() => setDialog(null)}>
                {dialog && (
                    <PurchaseReturnLineForm
                        key={dialog.line.source.id}
                        line={dialog.line}
                        errorFor={(field) => errorFor(`lines.${dialog.index}.${field}`)}
                        onCancel={() => setDialog(null)}
                        onSave={(line) => updateLine(dialog.index, line)}
                    />
                )}
            </FormDrawer>
        </>
    );
}

function PurchaseReturnLineForm({ line, errorFor, onSave, onCancel }: {
    line: EditableReturnLine;
    errorFor: (field: string) => string | undefined;
    onSave: (line: EditableReturnLine) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(line);
    const set = <K extends keyof EditableReturnLine>(key: K, value: EditableReturnLine[K]) => {
        setDraft((current) => ({
            ...current,
            [key]: value,
            include: key === 'returned_quantity' ? current.include || value !== '0.000000' : current.include,
        }));
    };

    return (
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); onSave(draft); }}>
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">{formatReturnItem(draft)} / {draft.source.uom?.code ?? '-'}</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" className="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" checked={draft.include} onChange={(event) => set('include', event.target.checked)} />
                        Include
                    </label>
                    <DecimalInput label="Return quantity" value={draft.returned_quantity} error={errorFor('returned_quantity')} onChange={(event) => set('returned_quantity', event.target.value)} />
                    <Input label="Reason" value={draft.reason} error={errorFor('reason')} onChange={(event) => set('reason', event.target.value)} />
                </div>
            </section>
            <div className="rounded-lg border border-slate-200 p-4 text-sm">
                <h3 className="font-semibold text-slate-900">Source Line</h3>
                <div className="mt-2 grid gap-2 sm:grid-cols-3">
                    <Summary label="Remaining quantity" value={draft.source.returnable_quantity} />
                    <Summary label="Unit price" value={draft.source.unit_price} />
                    <Summary label="UOM" value={draft.source.uom?.code ?? '-'} />
                </div>
            </div>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit">Save line</Button>
            </div>
        </form>
    );
}

function formatReturnItem(line: EditableReturnLine): string {
    return line.source.item?.name ?? '-';
}

function Summary({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block tabular-nums text-slate-900">{value}</strong></div>;
}

function ReturnLineMobileDetails({ line }: { line: EditableReturnLine }) {
    return <div className="grid grid-cols-2 gap-2"><Summary label="Include" value={line.include ? 'Yes' : 'No'} /><Summary label="Quantity now" value={line.returned_quantity} /><Summary label="Remaining quantity" value={line.source.returnable_quantity} /><Summary label="UOM" value={line.source.uom?.code ?? '-'} /><Summary label="Unit price" value={line.source.unit_price} /></div>;
}
