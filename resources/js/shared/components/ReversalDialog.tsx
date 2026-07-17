import { useState, type FormEvent } from 'react';
import { Button } from './Button';
import { Modal } from './Modal';

const DATE_PART_WIDTH = 2;
const MINIMUM_REASON_LENGTH = 1;

export interface ReversalFacts {
    reversal_date: string;
    reason: string;
}

interface ReversalDialogProps {
    open: boolean;
    title: string;
    loading?: boolean;
    defaultDate?: string;
    onCancel: () => void;
    onConfirm: (facts: ReversalFacts) => void | Promise<void>;
}

type ReversalDialogContentProps = Omit<ReversalDialogProps, 'open' | 'defaultDate'> & {
    defaultDate: string;
};

export function ReversalDialog(props: ReversalDialogProps) {
    if (!props.open) return null;

    const defaultDate = props.defaultDate ?? todayLocalDate();

    return (
        <ReversalDialogContent
            key={defaultDate}
            title={props.title}
            loading={props.loading}
            defaultDate={defaultDate}
            onCancel={props.onCancel}
            onConfirm={props.onConfirm}
        />
    );
}

function ReversalDialogContent({
    title,
    loading = false,
    defaultDate,
    onCancel,
    onConfirm,
}: ReversalDialogContentProps) {
    const [reversalDate, setReversalDate] = useState(defaultDate);
    const [reason, setReason] = useState('');
    const [error, setError] = useState<string | null>(null);

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const normalizedReason = reason.trim();
        if (reversalDate === '') {
            setError('Select the reversal date.');
            return;
        }
        if (normalizedReason.length < MINIMUM_REASON_LENGTH) {
            setError('Enter the business reason for this reversal.');
            return;
        }

        setError(null);
        await onConfirm({ reversal_date: reversalDate, reason: normalizedReason });
    }

    return (
        <Modal
            open
            title={title}
            onClose={onCancel}
            closeDisabled={loading}
            closeOnBackdrop={!loading}
        >
            <form className="space-y-5" onSubmit={submit}>
                <p className="text-sm text-slate-600">
                    Reversal creates opposite financial entries. The original document and its audit evidence remain unchanged.
                </p>

                <label className="block space-y-1.5 text-sm font-medium text-slate-700">
                    <span>Reversal date</span>
                    <input
                        type="date"
                        required
                        value={reversalDate}
                        onChange={(event) => setReversalDate(event.target.value)}
                        disabled={loading}
                        className="min-h-10 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:bg-slate-100"
                    />
                </label>

                <label className="block space-y-1.5 text-sm font-medium text-slate-700">
                    <span>Business reason</span>
                    <textarea
                        required
                        rows={4}
                        value={reason}
                        onChange={(event) => setReason(event.target.value)}
                        disabled={loading}
                        placeholder="Explain why the posted document must be reversed."
                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:bg-slate-100"
                    />
                </label>

                {error ? <p role="alert" className="text-sm font-medium text-rose-600">{error}</p> : null}

                <div className="flex justify-end gap-3 border-t border-slate-200 pt-4">
                    <Button variant="secondary" onClick={onCancel} disabled={loading}>Cancel</Button>
                    <Button type="submit" variant="danger" loading={loading} loadingLabel="Reversing...">
                        Reverse document
                    </Button>
                </div>
            </form>
        </Modal>
    );
}

export function todayLocalDate(date = new Date()): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(DATE_PART_WIDTH, '0');
    const day = String(date.getDate()).padStart(DATE_PART_WIDTH, '0');

    return `${year}-${month}-${day}`;
}
