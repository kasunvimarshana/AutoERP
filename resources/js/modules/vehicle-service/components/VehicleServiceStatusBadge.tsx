/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { StatusBadge } from '@/shared/components/StatusBadge';
import type { VehicleServiceJobStatus } from '../vehicleServiceTypes';

export function VehicleServiceStatusBadge({ status }: { status?: VehicleServiceJobStatus | null }) {
    return <StatusBadge status={status} />;
}
