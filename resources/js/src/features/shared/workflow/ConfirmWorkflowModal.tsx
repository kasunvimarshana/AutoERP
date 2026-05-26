import { ConfirmModal } from '../../../components/feedback/ConfirmModal';

type ConfirmWorkflowModalProps = {
    open: boolean;
    title: string;
    description: string;
    confirmLabel: string;
    isLoading?: boolean;
    onCancel: () => void;
    onConfirm: () => void;
};

export function ConfirmWorkflowModal(props: ConfirmWorkflowModalProps) {
    return <ConfirmModal {...props} />;
}
