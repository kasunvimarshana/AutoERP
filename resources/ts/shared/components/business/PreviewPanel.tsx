import type { ReactNode } from 'react';
import { Card } from '../ui/Card';
import { StatusBadge } from './StatusBadge';

type PreviewPanelRow = {
    label: string;
    value: ReactNode;
};

type PreviewPanelProps = {
    children?: ReactNode;
    rows?: PreviewPanelRow[];
    status?: string;
    subtitle?: string;
    title: string;
};

export function PreviewPanel({ children, rows, status = 'Preview', subtitle, title }: PreviewPanelProps) {
    return (
        <Card className="p-5">
            <div className="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-700">{title}</h2>
                    <p className="mt-1 text-sm text-slate-500">{subtitle ?? 'Backend preview data appears here when the API returns it.'}</p>
                </div>
                <StatusBadge status={status} />
            </div>
            {rows?.length ? (
                <div className="divide-y divide-slate-100 rounded-lg border border-slate-200">
                    {rows.map((row, index) => (
                        <div className="flex items-center justify-between gap-4 px-4 py-3 text-sm" key={`${title}:${row.label}:${index}`}>
                            <span className="text-slate-500">{row.label}</span>
                            <span className="font-semibold text-slate-900">{row.value}</span>
                        </div>
                    ))}
                </div>
            ) : null}
            {children ? <div className="mt-4">{children}</div> : null}
        </Card>
    );
}
