/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { StatusBadge } from '@/shared/components/StatusBadge';
import type { PurchaseOrderStatus } from '../purchaseApi';

export function PurchaseOrderStatusBadge({ status }: { status?: PurchaseOrderStatus | string | null }) {
    return <StatusBadge status={status ?? undefined} />;
}
