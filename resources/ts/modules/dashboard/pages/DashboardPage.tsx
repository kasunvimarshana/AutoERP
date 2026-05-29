import { Card } from '../../../shared/components/ui/Card';
import { ModuleHeader } from '../../../layouts/components/ModuleHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';

const widgets = [
    { helper: 'Sales and service records awaiting attention', label: 'Open Workflows', value: '42' },
    { helper: 'Vehicles available in the fleet', label: 'Available Vehicles', value: '18' },
    { helper: 'Payments waiting for allocation preview', label: 'Unallocated Payments', value: '7' },
    { helper: 'Mock values only until backend summaries are connected', label: 'Backend Previews', value: '12' },
];

export function DashboardPage() {
    return (
        <div className="space-y-6">
            <ModuleHeader subtitle="A modular control center for workshop, fleet, finance, and inventory operations." title="Dashboard" />
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {widgets.map((widget) => (
                    <Card className="p-5" key={widget.label}>
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-medium text-slate-500">{widget.label}</p>
                            <StatusBadge status="Active" />
                        </div>
                        <p className="mt-4 text-3xl font-bold text-slate-950">{widget.value}</p>
                        <p className="mt-2 text-sm text-slate-500">{widget.helper}</p>
                    </Card>
                ))}
            </div>
        </div>
    );
}
