import { Button } from '../ui/Button';

export type ConfirmDialogState = {
    description: string;
    title: string;
} | null;

export function ConfirmDialog({ busy = false, confirmLabel = 'Confirm', onCancel, onConfirm, state }: { busy?: boolean; confirmLabel?: string; onCancel: () => void; onConfirm: () => void; state: ConfirmDialogState }) {
    if (!state) return null;

    return (
        <div aria-modal="true" className="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" role="dialog">
            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                <h2 className="text-lg font-bold text-slate-950">{state.title}</h2>
                <p className="mt-2 text-sm leading-6 text-slate-500">{state.description}</p>
                <div className="mt-6 flex justify-end gap-2">
                    <Button disabled={busy} onClick={onCancel} variant="secondary">Cancel</Button>
                    <Button disabled={busy} onClick={onConfirm} variant="danger">{busy ? 'Working...' : confirmLabel}</Button>
                </div>
            </div>
        </div>
    );
}
