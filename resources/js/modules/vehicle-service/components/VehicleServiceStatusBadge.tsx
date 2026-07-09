import { StatusBadge } from '@/shared/components/StatusBadge';
import type { VehicleServiceLifecycleStatus } from '../vehicleServiceTypes';

export function VehicleServiceStatusBadge({ status }: { status?: VehicleServiceLifecycleStatus | null }) {
    return <StatusBadge status={status} />;
}
