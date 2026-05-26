import { ContentCard } from '../../../components/ui/ContentCard';
import { DocumentStatusBadge } from './DocumentStatusBadge';
import { formatDate } from '../utils';

type DocumentHeaderMetric = {
    label: string;
    value: string;
};

type DocumentHeaderProps = {
    title: string;
    helperText?: string;
    documentNumberLabel: string;
    documentNumber: string | null | undefined;
    status: string | null | undefined;
    primaryPartyLabel: string;
    primaryPartyValue: string;
    dateLabel: string;
    dateValue: string | null | undefined;
    metrics?: DocumentHeaderMetric[];
};

export function DocumentHeader({
    title,
    helperText,
    documentNumberLabel,
    documentNumber,
    status,
    primaryPartyLabel,
    primaryPartyValue,
    dateLabel,
    dateValue,
    metrics = [],
}: DocumentHeaderProps) {
    return (
        <ContentCard>
            <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div className="space-y-3">
                    <div>
                        <p className="text-xs uppercase tracking-[0.18em] text-stone-500">{documentNumberLabel}</p>
                        <h2 className="mt-2 text-2xl font-semibold text-stone-950">{documentNumber || 'Not assigned'}</h2>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <DocumentStatusBadge status={status} />
                        <span className="text-sm text-stone-500">
                            {dateLabel}: <span className="font-medium text-stone-800">{formatDate(dateValue)}</span>
                        </span>
                    </div>
                    <div>
                        <p className="text-xs uppercase tracking-[0.16em] text-stone-500">{primaryPartyLabel}</p>
                        <p className="mt-1 text-sm font-medium text-stone-950">{primaryPartyValue}</p>
                    </div>
                </div>

                <div className="space-y-2 lg:max-w-md">
                    <div>
                        <h3 className="text-lg font-semibold text-stone-950">{title}</h3>
                        {helperText ? <p className="mt-1 text-sm leading-6 text-stone-600">{helperText}</p> : null}
                    </div>
                    {metrics.length > 0 ? (
                        <div className="grid gap-3 sm:grid-cols-2">
                            {metrics.map((metric) => (
                                <div key={metric.label} className="rounded-2xl border border-stone-200/80 bg-stone-50/80 px-4 py-3">
                                    <p className="text-xs uppercase tracking-[0.14em] text-stone-500">{metric.label}</p>
                                    <p className="mt-2 text-sm font-semibold text-stone-950">{metric.value}</p>
                                </div>
                            ))}
                        </div>
                    ) : null}
                </div>
            </div>
        </ContentCard>
    );
}
