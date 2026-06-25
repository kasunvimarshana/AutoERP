import { DataTable, type DataColumn } from './DataTable';
import { humanize, readableRelation } from '@/shared/utils/object';

type Row = Record<string, unknown>;

function renderValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return '-';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return readableRelation(value);
    return String(value);
}

export function RecordTable({ rows, fields }: { rows: Row[]; fields: string[] }) {
    const columns: DataColumn<Row>[] = fields.map((field) => ({
        key: field,
        header: humanize(field),
        render: (row) => renderValue(row[field]),
    }));
    return <DataTable rows={rows} columns={columns} rowKey={(row) => String(row.id ?? JSON.stringify(row))} />;
}
