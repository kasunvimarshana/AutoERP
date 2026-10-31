import { Button } from '@/shared/components/Button';
import { Link } from 'react-router-dom';
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
        render: (row) => column.format === 'link' && typeof row[`${column.key}_url`] === 'string'
            ? <Link className="font-medium text-sky-700 hover:underline" to={String(row[`${column.key}_url`])}>{formatValue(row[column.key], column.format)}</Link>
            : formatValue(row[column.key], column.format),
        className: ['money', 'decimal'].includes(column.format) ? 'tabular-nums' : undefined,
    }));

    return <DataTable rows={rows} columns={tableColumns} rowKey={(row, index) => String(row.id ?? `${JSON.stringify(row)}-${index}`)} />;
}

function formatValue(value: ReportRow[string], format: string) {
    if (value === null || value === undefined || value === '') return '-';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (format === 'money' || format === 'decimal') {
        const formatted = formatDecimalString(String(value));
        if (formatted !== null) return formatted;
    }
    return String(value);
}

function formatDecimalString(value: string): string | null {
    const match = value.trim().match(/^([+-]?)(\d+)(?:\.(\d+))?$/);
    if (!match) return null;

    const [, sign, integer, rawFraction = ''] = match;
    const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const fraction = rawFraction.replace(/0+$/, '').padEnd(2, '0');

    return `${sign}${grouped}.${fraction}`;
}
