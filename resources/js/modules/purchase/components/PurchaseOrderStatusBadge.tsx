import { StatusBadge } from '@/shared/components/StatusBadge';
import type { PurchaseOrderStatus } from '../purchaseApi';

export function PurchaseOrderStatusBadge({ status }: { status?: PurchaseOrderStatus | string | null }) {
    return <StatusBadge status={status ?? undefined} />;
}
