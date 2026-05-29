import { Button } from './Button';
import { Modal } from './Modal';

type ConfirmDialogProps = {
    message: string;
    onCancel: () => void;
    onConfirm: () => void;
    open: boolean;
    title: string;
};

export function ConfirmDialog({ message, onCancel, onConfirm, open, title }: ConfirmDialogProps) {
    return (
        <Modal onClose={onCancel} open={open} title={title}>
            <p className="text-sm text-slate-600">{message}</p>
            <div className="mt-5 flex justify-end gap-3">
                <Button onClick={onCancel} variant="secondary">
                    Cancel
                </Button>
                <Button onClick={onConfirm}>Confirm</Button>
            </div>
        </Modal>
    );
}
