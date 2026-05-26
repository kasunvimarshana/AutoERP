import type { ReactNode } from 'react';
import { cn } from '../../lib/cn';

export type DataTableColumn<T> = {
    key: string;
    header: string;
    className?: string;
    headerClassName?: string;
    render: (row: T) => ReactNode;
};

type DataTableProps<T> = {
    columns: DataTableColumn<T>[];
    rows: T[];
    getRowKey: (row: T) => string | number;
    emptyState?: ReactNode;
    className?: string;
    footer?: ReactNode;
};

export function DataTable<T>({ className, columns, emptyState = null, footer, getRowKey, rows }: DataTableProps<T>) {
    if (rows.length === 0) {
        return <>{emptyState}</>;
    }

    return (
        <div className={className}>
            <div className="overflow-x-auto">
                <table className="min-w-full text-left text-sm">
                    <thead className="bg-stone-50 text-stone-500">
                        <tr>
                            {columns.map((column) => (
                                <th key={column.key} className={cn('px-4 py-3 font-medium', column.headerClassName)}>
                                    {column.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={getRowKey(row)} className="border-t border-stone-200/80">
                                {columns.map((column) => (
                                    <td key={column.key} className={cn('px-4 py-4 align-top text-stone-700', column.className)}>
                                        {column.render(row)}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {footer ? <div className="border-t border-stone-200/80 px-4 py-4 sm:px-6">{footer}</div> : null}
        </div>
    );
}
