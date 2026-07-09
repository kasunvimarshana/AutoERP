import { StatusBadge } from '@/shared/components/StatusBadge';
import type { VehicleServiceBillingStatus, VehicleServiceOperationalStatus, VehicleServicePaymentStatus } from '../vehicleServiceTypes';

type VehicleServiceStatus = VehicleServiceOperationalStatus | VehicleServiceBillingStatus | VehicleServicePaymentStatus;

export function VehicleServiceStatusBadge({ status }: { status?: VehicleServiceStatus | null }) {
    return <StatusBadge status={status} />;
}
