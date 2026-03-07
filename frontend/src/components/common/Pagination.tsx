import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import clsx from 'clsx';
import type { PaginationMeta } from '@/types';

interface PaginationProps {
  meta: PaginationMeta;
  onPageChange: (page: number) => void;
}

const Pagination: React.FC<PaginationProps> = ({ meta, onPageChange }) => {
  const { current_page, last_page, per_page, total, from, to } = meta;

  const getPages = (): (number | '...')[] => {
    const pages: (number | '...')[] = [];
    if (last_page <= 7) {
      for (let i = 1; i <= last_page; i++) pages.push(i);
    } else {
      pages.push(1);
      if (current_page > 3) pages.push('...');
      const start = Math.max(2, current_page - 1);
      const end = Math.min(last_page - 1, current_page + 1);
      for (let i = start; i <= end; i++) pages.push(i);
      if (current_page < last_page - 2) pages.push('...');
      pages.push(last_page);
    }
    return pages;
  };

  if (last_page <= 1) return null;

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4">
      <p className="text-sm text-gray-500">
        Showing <span className="font-medium text-gray-700">{from}</span> –{' '}
        <span className="font-medium text-gray-700">{to}</span> of{' '}
        <span className="font-medium text-gray-700">{total}</span> results
        {per_page && ` (${per_page} per page)`}
      </p>

      <nav className="flex items-center gap-1">
        <button
          onClick={() => onPageChange(current_page - 1)}
          disabled={current_page === 1}
          className="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          <ChevronLeft size={16} />
        </button>

        {getPages().map((page, idx) =>
          page === '...' ? (
            <span key={`ellipsis-${idx}`} className="px-2 text-gray-400 select-none">
              …
            </span>
          ) : (
            <button
              key={page}
              onClick={() => onPageChange(page as number)}
              className={clsx(
                'w-9 h-9 rounded-lg text-sm font-medium transition-colors',
                page === current_page
                  ? 'bg-primary-600 text-white shadow-sm'
                  : 'border border-gray-200 text-gray-700 hover:bg-gray-50',
              )}
            >
              {page}
            </button>
          ),
        )}

        <button
          onClick={() => onPageChange(current_page + 1)}
          disabled={current_page === last_page}
          className="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          <ChevronRight size={16} />
        </button>
      </nav>
    </div>
  );
};

export default Pagination;
