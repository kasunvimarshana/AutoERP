import { Card } from '../../../shared/components/ui/Card';
import type { Customer } from '../types/customer.types';
import { CustomerStatusBadge } from './CustomerStatusBadge';

export function CustomerSummaryCard({ customer }: { customer: Customer }) {
    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-widest text-slate-400">{customer.code}</p>
                    <h2 className="mt-1 text-xl font-bold text-slate-950">{customer.name}</h2>
                    <p className="mt-1 text-sm text-slate-500">{customer.industry}</p>
                </div>
                <CustomerStatusBadge status={customer.status} />
            </div>
            <div className="mt-5 grid gap-4 md:grid-cols-4">
                {[
                    ['Primary contact', customer.contactPerson],
                    ['Email', customer.email],
                    ['Phone', customer.phone],
                    ['User access', customer.userAccessStatus === 'linked' ? 'Linked' : 'Not linked'],
                ].map(([label, value]) => (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-3" key={label}>
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
                        <p className="mt-1 text-sm font-semibold text-slate-800">{value}</p>
                    </div>
                ))}
            </div>
        </Card>
    );
}
