import { StatusBadge } from '@/shared/components/StatusBadge';

export function RentalStatusBadge({ status }: { status?: string | null }) {
    return <StatusBadge status={status} />;
}
