import { useAuth } from '@/modules/auth/AuthProvider';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import type { VehicleServiceJob } from '../vehicleServiceTypes';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import { VehicleServiceJobDiscountEditor } from './VehicleServiceJobDiscountEditor';
import { VehicleServiceStatusBadge } from './VehicleServiceStatusBadge';

export function VehicleServiceSummaryPanel({ job, onJobChanged }: {
    job: VehicleServiceJob;
    onJobChanged?: (job: VehicleServiceJob) => void;
}) {
    const { permissions } = useAuth();
    const currentCustomerOwner = job.vehicle?.current_ownerships?.find((ownership) => ownership.owner_type === 'customer')?.owner ?? job.customer;
    const canManageDiscount = permissions.includes(vehicleServicePermissions.discountsManage)
        && ['draft', 'inspected', 'in_progress'].includes(job.status);

    return (
        <div>
            <DetailGrid items={[
                { label: 'Status', value: <VehicleServiceStatusBadge status={job.status} /> },
                { label: 'Customer', value: readableRelation(job.customer) },
                { label: 'Vehicle', value: readableRelation(job.vehicle) },
                { label: 'Make / model', value: `${job.vehicle?.make?.name ?? '-'} / ${job.vehicle?.model?.name ?? '-'}` },
                { label: 'Registered owner', value: readableRelation(currentCustomerOwner) },
                { label: 'Supervisor', value: readableRelation(job.supervisor) },
                ...(['full_service', 'oil_change'].includes(job.type) ? [
                    { label: 'Odometer', value: job.odometer_reading ?? '-' },
                    { label: 'Next service mileage', value: job.next_service_mileage ?? '-' },
                ] : []),
                { label: 'Manual job card', value: job.manual_job_card ?? '-' },
                { label: 'Type', value: job.type_label ?? job.type.replaceAll('_', ' ') },
                { label: 'Subtotal', value: <MoneyDisplay value={job.subtotal} /> },
                { label: 'Line discounts', value: <MoneyDisplay value={job.line_discount_total} /> },
                { label: 'Whole-job discount', value: <MoneyDisplay value={job.job_discount_amount} /> },
                { label: 'Total discounts', value: <MoneyDisplay value={job.discount_total} /> },
                { label: 'Tax', value: <MoneyDisplay value={job.tax_total} /> },
                { label: 'Charges', value: <MoneyDisplay value={job.charge_total} /> },
                { label: 'Grand total', value: <MoneyDisplay value={job.grand_total} /> },
                { label: 'Completed at', value: formatDate(job.completed_at) },
                { label: 'Notes', value: job.notes ?? '-' },
            ]} />
            {canManageDiscount && onJobChanged && (
                <div className="mt-5 flex justify-end">
                    <VehicleServiceJobDiscountEditor job={job} onChanged={onJobChanged} />
                </div>
            )}
        </div>
    );
}
