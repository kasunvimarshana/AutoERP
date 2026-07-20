import { useEffect, useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Modal } from '@/shared/components/Modal';
import { Textarea } from '@/shared/components/Textarea';

export function RentalReasonDialog({
    open,
    title,
    message,
    confirmLabel,
    danger = true,
    minLength = 5,
    onClose,
    onConfirm,
}: {
    open: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    danger?: boolean;
    minLength?: number;
    onClose: () => void;
    onConfirm: (reason: string) => Promise<void>;
}) {
    const [reason, setReason] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (!open) return;
        setReason('');
        setError(null);
        setSubmitting(false);
    }, [open]);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        try {
            await onConfirm(reason.trim());
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open={open} title={title} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-4" onSubmit={(event) => void submit(event)}>
                <p className="text-sm text-slate-700">{message}</p>
                <ErrorAlert error={error} inline />
                <Textarea label="Reason" required minLength={minLength} value={reason} onChange={(event) => setReason(event.target.value)} />
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" variant={danger ? 'danger' : 'primary'} loading={submitting}>{confirmLabel}</Button>
                </div>
            </form>
        </Modal>
    );
}
