import { DetailGrid } from '@/shared/components/DetailGrid';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import type { VehicleServiceJob } from '../vehicleServiceTypes';
import { VehicleServiceStatusBadge } from './VehicleServiceStatusBadge';

export function VehicleServiceSummaryPanel({ job }: { job: VehicleServiceJob }) {
    return (
        <div>
            <DetailGrid items={[
                { label: 'Status', value: <VehicleServiceStatusBadge status={job.status} /> },
                { label: 'Customer', value: readableRelation(job.customer) },
                { label: 'Vehicle', value: readableRelation(job.vehicle) },
                { label: 'Make / model', value: `${job.vehicle?.make?.name ?? '-'} / ${job.vehicle?.model?.name ?? '-'}` },
                { label: 'Registered owner', value: readableRelation(job.vehicle?.customer ?? job.customer) },
                { label: 'Supervisor', value: readableRelation(job.supervisor) },
                { label: 'Job date', value: formatDate(job.job_date) },
                { label: 'Expected delivery', value: formatDate(job.expected_delivery_date) },
                { label: 'Odometer', value: job.odometer_reading ?? '-' },
                { label: 'Fuel level', value: job.fuel_level ?? '-' },
                { label: 'Priority', value: job.priority ?? '-' },
                { label: 'Subtotal', value: <MoneyDisplay value={job.subtotal} /> },
                { label: 'Discount', value: <MoneyDisplay value={job.discount_total} /> },
                { label: 'Tax', value: <MoneyDisplay value={job.tax_total} /> },
                { label: 'Charges', value: <MoneyDisplay value={job.charge_total} /> },
                { label: 'Grand total', value: <MoneyDisplay value={job.grand_total} /> },
                { label: 'Supervisor commission', value: <MoneyDisplay value={job.supervisor_commission_amount} /> },
                { label: 'Completed at', value: formatDate(job.completed_at) },
                { label: 'Notes', value: job.notes ?? '-' },
            ]} />
        </div>
    );
}
