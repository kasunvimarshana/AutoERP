import { StatusBadge } from '@/shared/components/StatusBadge';

export function SalesStatusBadge({ status }: { status?: string | null }) {
    return <StatusBadge status={status ?? undefined} />;
}
