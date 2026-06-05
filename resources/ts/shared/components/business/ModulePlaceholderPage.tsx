import { Link } from 'react-router-dom';
import { Card } from '../ui/Card';
import { Button } from '../ui/Button';
import { SearchFilterBar } from '../data/SearchFilterBar';
import { DataTable } from '../data/DataTable';
import { StatusBadge } from './StatusBadge';

type ModulePlaceholderPageProps = {
    actions?: Array<{ label: string; path: string; variant?: 'primary' | 'secondary' | 'blue' }>;
    description: string;
    metrics?: Array<{ helper?: string; label: string; value: string | number }>;
    rows?: Array<{ area: string; owner: string; status: string }>;
    sections?: Array<{ description: string; label: string; path: string; status?: string }>;
    title: string;
};

const defaultRows = [
    { area: 'Workflow', owner: 'Operations', status: 'Ready for UI' },
    { area: 'Preview API', owner: 'Backend', status: 'Mocked' },
    { area: 'Permissions', owner: 'Admin', status: 'Planned' },
];

const defaultMetrics = [
    { label: 'Open records', value: 24 },
    { label: 'Pending approval', value: 8 },
    { label: 'Backend previews', value: 5 },
];

export function ModulePlaceholderPage({
    actions,
    description,
    metrics = defaultMetrics,
    rows = defaultRows,
    sections,
    title,
}: ModulePlaceholderPageProps) {
    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 className="text-3xl font-bold tracking-normal text-slate-950">{title}</h1>
                    <p className="mt-2 text-sm text-slate-500">{description}</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    {(actions ?? [{ label: 'New Record', path: '#', variant: 'primary' }]).map((action) => (
                        <Link key={action.label} to={action.path}>
                            <Button variant={action.variant}>{action.label}</Button>
                        </Link>
                    ))}
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                {metrics.map((metric) => (
                    <Card className="p-5" key={metric.label}>
                        <p className="text-sm text-slate-500">{metric.label}</p>
                        <p className="mt-2 text-2xl font-bold text-slate-950">{metric.value}</p>
                        {metric.helper ? <p className="mt-1 text-xs text-slate-400">{metric.helper}</p> : null}
                    </Card>
                ))}
            </div>

            {sections?.length ? (
                <div className="grid gap-4 lg:grid-cols-2">
                    {sections.map((section) => (
                        <Link className="block" key={section.path} to={section.path}>
                            <Card className="h-full p-5 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 className="text-base font-bold text-slate-950">{section.label}</h2>
                                        <p className="mt-1 text-sm text-slate-500">{section.description}</p>
                                    </div>
                                    {section.status ? <StatusBadge status={section.status} /> : null}
                                </div>
                            </Card>
                        </Link>
                    ))}
                </div>
            ) : null}

            <SearchFilterBar placeholder={`Search ${title.toLowerCase()}...`} />
            <DataTable
                columns={[
                    { header: 'Area', key: 'area' },
                    { header: 'Owner', key: 'owner' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                ]}
                getRowKey={(row) => row.area}
                rows={rows}
            />
        </div>
    );
}
