import type { FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { ReportDefinition, ReportParams } from '../reportingTypes';

export function FilterPanel({ report, value, onChange, onApply }: {
    report: ReportDefinition;
    value: ReportParams;
    onChange: (value: ReportParams) => void;
    onApply: () => void;
}) {
    const filters = value.filters ?? {};
    const set = (patch: Partial<ReportParams>) => onChange({ ...value, ...patch, page: 1 });
    const setFilter = (key: string, next: string) => onChange({ ...value, page: 1, filters: { ...filters, [key]: next || undefined } });

    return (
        <form className="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 lg:grid-cols-5" onSubmit={(event: FormEvent) => { event.preventDefault(); onApply(); }}>
            <Input label="Search" value={value.search ?? ''} onChange={(event) => set({ search: event.target.value })} />
            {report.supports_date_range && <Input label="From" type="date" value={value.date_from ?? ''} onChange={(event) => set({ date_from: event.target.value })} />}
            {report.supports_date_range && <Input label="To" type="date" value={value.date_to ?? ''} onChange={(event) => set({ date_to: event.target.value })} />}
            {report.filters.slice(0, 2).map((filter) => filter.type === 'select'
                ? <Select key={filter.key} label={filter.label} value={String(filters[filter.key] ?? '')} options={[{ value: '', label: 'All' }, ...(filter.options ?? [])]} onChange={(event) => setFilter(filter.key, event.target.value)} />
                : <Input key={filter.key} label={filter.label} value={String(filters[filter.key] ?? '')} onChange={(event) => setFilter(filter.key, event.target.value)} />
            )}
            <div className="flex items-end gap-2">
                <Button type="submit">Apply</Button>
                <Button type="button" variant="secondary" onClick={() => onChange({ page: 1, per_page: value.per_page ?? 25 })}>Reset</Button>
            </div>
        </form>
    );
}
