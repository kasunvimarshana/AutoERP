import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-react';
import clsx from 'clsx';
import LoadingSpinner from './LoadingSpinner';
import type { TableColumn } from '@/types';

interface DataTableProps<T> {
  columns: TableColumn<T>[];
  data: T[];
  isLoading?: boolean;
  sortBy?: string;
  sortDirection?: 'asc' | 'desc';
  onSort?: (key: string) => void;
  keyExtractor: (row: T) => string | number;
  emptyMessage?: string;
  onRowClick?: (row: T) => void;
}

function DataTable<T>({
  columns,
  data,
  isLoading = false,
  sortBy,
  sortDirection,
  onSort,
  keyExtractor,
  emptyMessage = 'No data found',
  onRowClick,
}: DataTableProps<T>) {
  const getSortIcon = (key: string) => {
    if (sortBy !== key) return <ChevronsUpDown size={14} className="text-gray-400" />;
    return sortDirection === 'asc' ? (
      <ChevronUp size={14} className="text-primary-600" />
    ) : (
      <ChevronDown size={14} className="text-primary-600" />
    );
  };

  const getCellValue = (row: T, key: string): unknown => {
    if (key.includes('.')) {
      return key.split('.').reduce<unknown>((obj, k) => (obj as Record<string, unknown>)?.[k], row);
    }
    return (row as Record<string, unknown>)[key];
  };

  return (
    <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            {columns.map((col) => (
              <th
                key={String(col.key)}
                className={clsx(
                  'px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider',
                  col.sortable && onSort && 'cursor-pointer hover:bg-gray-100 select-none',
                  col.className,
                )}
                onClick={() => col.sortable && onSort && onSort(String(col.key))}
              >
                <div className="flex items-center gap-1">
                  {col.label}
                  {col.sortable && onSort && getSortIcon(String(col.key))}
                </div>
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100">
          {isLoading ? (
            <tr>
              <td colSpan={columns.length} className="py-16 text-center">
                <div className="flex justify-center">
                  <LoadingSpinner size="lg" />
                </div>
              </td>
            </tr>
          ) : data.length === 0 ? (
            <tr>
              <td colSpan={columns.length} className="py-16 text-center text-gray-500 text-sm">
                {emptyMessage}
              </td>
            </tr>
          ) : (
            data.map((row) => (
              <tr
                key={keyExtractor(row)}
                className={clsx(
                  'hover:bg-gray-50 transition-colors',
                  onRowClick && 'cursor-pointer',
                )}
                onClick={() => onRowClick?.(row)}
              >
                {columns.map((col) => (
                  <td
                    key={String(col.key)}
                    className={clsx('px-4 py-3 text-sm text-gray-700', col.className)}
                  >
                    {col.render
                      ? col.render(getCellValue(row, String(col.key)), row)
                      : String(getCellValue(row, String(col.key)) ?? '—')}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}

export default DataTable;
