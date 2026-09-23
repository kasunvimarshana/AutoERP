import { useAuth } from '@/modules/auth/AuthProvider';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import type { VehicleServiceJob } from '../vehicleServiceTypes';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import { VehicleServiceJobDiscountEditor } from './VehicleServiceJobDiscountEditor';

export function VehicleServiceJobDiscountValue({ job, onChanged }: {
    job: VehicleServiceJob;
    onChanged: (job: VehicleServiceJob) => void;
}) {
    const { permissions } = useAuth();
    const canManage = permissions.includes(vehicleServicePermissions.discountsManage)
        && ['draft', 'inspected', 'in_progress'].includes(job.status);

    return (
        <section className="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div className="w-full sm:max-w-sm">
                    <span className="mb-1.5 block text-sm font-medium text-slate-700">Job Discount Value</span>
                    <div
                        className="flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-900"
                        aria-label="Job Discount Value"
                    >
                        <MoneyDisplay value={job.job_discount_amount} />
                    </div>
                </div>
                {canManage && <VehicleServiceJobDiscountEditor job={job} onChanged={onChanged} />}
            </div>
        </section>
    );
}
