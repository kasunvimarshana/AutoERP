import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { SalesOrderProgress } from '../salesTypes';

const labels: Record<keyof SalesOrderProgress, string> = {
    allocation: 'Allocation',
    delivery: 'Delivery',
    invoice: 'Invoice',
    payment: 'Payment',
    return: 'Return',
};

export function SalesProgressPanel({ progress }: { progress?: SalesOrderProgress }) {
    if (!progress) return null;

    return (
        <Panel title="Progress">
            <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                {Object.entries(labels).map(([key, label]) => (
                    <div key={key} className="space-y-1">
                        <dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt>
                        <dd><StatusBadge status={progress[key as keyof SalesOrderProgress]} /></dd>
                    </div>
                ))}
            </dl>
        </Panel>
    );
}
