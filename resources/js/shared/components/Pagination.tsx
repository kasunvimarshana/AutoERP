import type { PaginationMeta } from '@/shared/types/pagination';
import { Button } from './Button';

export function Pagination({ meta, onPageChange, disabled = false }: {
    meta?: PaginationMeta;
    onPageChange: (page: number) => void;
    disabled?: boolean;
}) {
    if (!meta || meta.last_page <= 1) return null;

    return (
        <nav className="mt-4 flex flex-col gap-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination">
            <span>
                {meta.from ?? 0}-{meta.to ?? 0} of {meta.total}
                <span className="sr-only">. Page {meta.current_page} of {meta.last_page}.</span>
            </span>
            <div className="flex flex-wrap items-center gap-2">
                <Button
                    variant="secondary"
                    disabled={disabled || meta.current_page <= 1}
                    aria-label={`Go to page ${Math.max(1, meta.current_page - 1)}`}
                    onClick={() => onPageChange(meta.current_page - 1)}
                >
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fillRule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clipRule="evenodd" />
                    </svg>
                    Previous
                </Button>
                <span aria-current="page" className="min-w-20 rounded-md bg-slate-100 px-3 py-1.5 text-center text-xs font-semibold text-slate-700">
                    {meta.current_page} / {meta.last_page}
                </span>
                <Button
                    variant="secondary"
                    disabled={disabled || meta.current_page >= meta.last_page}
                    aria-label={`Go to page ${Math.min(meta.last_page, meta.current_page + 1)}`}
                    onClick={() => onPageChange(meta.current_page + 1)}
                >
                    Next
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fillRule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clipRule="evenodd" />
                    </svg>
                </Button>
            </div>
        </nav>
    );
}
