import { useState, type FormEvent } from 'react';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { Button } from './Button';
import { Input } from './Input';
import { Modal } from './Modal';
import { Textarea } from './Textarea';

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

    const defaultDate = props.defaultDate ?? businessDateInputValue();

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

                <Input
                    label="Reversal date"
                    type="date"
                    required
                    value={reversalDate}
                    onChange={(event) => setReversalDate(event.target.value)}
                    disabled={loading}
                />

                <Textarea
                    label="Business reason"
                    required
                    rows={4}
                    value={reason}
                    onChange={(event) => setReason(event.target.value)}
                    disabled={loading}
                    placeholder="Explain why the posted document must be reversed."
                />

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
    return businessDateInputValue(date);
}
