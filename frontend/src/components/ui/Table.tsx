import React from 'react';
import clsx from 'clsx';
import { ChevronUp, ChevronDown } from 'lucide-react';
import Spinner from './Spinner';

export interface Column<T> {
  key: string;
  header: string;
  render?: (row: T) => React.ReactNode;
  sortable?: boolean;
  className?: string;
}

interface TableProps<T> {
  columns: Column<T>[];
  data: T[];
  isLoading?: boolean;
  emptyMessage?: string;
  sortKey?: string;
  sortDir?: 'asc' | 'desc';
  onSort?: (key: string) => void;
  rowKey: (row: T) => string | number;
}

function Table<T>({
  columns,
  data,
  isLoading,
  emptyMessage = 'No data found',
  sortKey,
  sortDir,
  onSort,
  rowKey,
}: TableProps<T>) {
  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            {columns.map((col) => (
              <th
                key={col.key}
                className={clsx(
                  'px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider',
                  col.sortable && 'cursor-pointer select-none hover:text-gray-700',
                  col.className
                )}
                onClick={() => col.sortable && onSort?.(col.key)}
              >
                <div className="flex items-center gap-1">
                  {col.header}
                  {col.sortable && (
                    <span className="flex flex-col">
                      <ChevronUp
                        className={clsx(
                          'h-3 w-3',
                          sortKey === col.key && sortDir === 'asc' ? 'text-blue-600' : 'text-gray-300'
                        )}
                      />
                      <ChevronDown
                        className={clsx(
                          'h-3 w-3 -mt-1',
                          sortKey === col.key && sortDir === 'desc' ? 'text-blue-600' : 'text-gray-300'
                        )}
                      />
                    </span>
                  )}
                </div>
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-100">
          {isLoading ? (
            <tr>
              <td colSpan={columns.length} className="py-12 text-center">
                <div className="flex justify-center">
                  <Spinner size="lg" />
                </div>
              </td>
            </tr>
          ) : data.length === 0 ? (
            <tr>
              <td colSpan={columns.length} className="py-12 text-center text-sm text-gray-500">
                {emptyMessage}
              </td>
            </tr>
          ) : (
            data.map((row) => (
              <tr key={rowKey(row)} className="hover:bg-gray-50 transition-colors">
                {columns.map((col) => (
                  <td key={col.key} className={clsx('px-4 py-3 text-sm text-gray-700', col.className)}>
                    {col.render ? col.render(row) : String((row as any)[col.key] ?? '')}
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

export default Table;
