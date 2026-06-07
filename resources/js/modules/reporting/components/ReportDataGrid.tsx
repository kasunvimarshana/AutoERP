import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import type { ReportColumn, ReportRow } from '../reportingTypes';

export function ReportDataGrid({ columns, rows, sort, direction, onSort }: {
    columns: ReportColumn[];
    rows: ReportRow[];
    sort?: string;
    direction?: 'asc' | 'desc';
    onSort: (key: string) => void;
}) {
    const tableColumns: DataColumn<ReportRow>[] = columns.map((column) => ({
        key: column.key,
        header: column.sortable ? (
            <Button type="button" variant="ghost" className="min-h-0 px-0 py-0 text-xs uppercase" onClick={() => onSort(column.key)}>
                {column.label}{sort === column.key ? (direction === 'asc' ? ' ASC' : ' DESC') : ''}
            </Button>
        ) : column.label,
        render: (row) => formatValue(row[column.key], column.format),
        className: ['money', 'decimal'].includes(column.format) ? 'tabular-nums' : undefined,
    }));

    return <DataTable rows={rows} columns={tableColumns} rowKey={(row) => String(row.id ?? JSON.stringify(row))} />;
}

function formatValue(value: ReportRow[string], format: string) {
    if (value === null || value === undefined || value === '') return '-';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if ((format === 'money' || format === 'decimal') && !Number.isNaN(Number(value))) {
        return Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 6 });
    }
    return String(value);
}
