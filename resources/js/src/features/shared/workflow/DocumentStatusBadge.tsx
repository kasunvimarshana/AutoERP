import { StatusBadge } from '../../../components/tables';
import { getStatusTone } from '../utils';

type DocumentStatusBadgeProps = {
    status: string | null | undefined;
};

export function DocumentStatusBadge({ status }: DocumentStatusBadgeProps) {
    return <StatusBadge tone={getStatusTone(status)}>{status ? status.replaceAll('_', ' ') : 'Unknown'}</StatusBadge>;
}
