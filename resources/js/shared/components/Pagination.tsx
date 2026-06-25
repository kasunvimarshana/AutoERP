import type { PaginationMeta } from '@/shared/types/pagination';
import { Button } from './Button';

export function Pagination({ meta, onPageChange }: {
    meta?: PaginationMeta;
    onPageChange: (page: number) => void;
}) {
    if (!meta || meta.last_page <= 1) return null;

    return (
        <nav className="mt-4 flex items-center justify-between gap-3 text-sm text-slate-600" aria-label="Pagination">
            <span>
                {meta.from ?? 0}-{meta.to ?? 0} of {meta.total}
                <span className="sr-only">. Page {meta.current_page} of {meta.last_page}.</span>
            </span>
            <div className="flex items-center gap-2">
                <Button
                    variant="secondary"
                    disabled={meta.current_page <= 1}
                    aria-label={`Go to page ${Math.max(1, meta.current_page - 1)}`}
                    onClick={() => onPageChange(meta.current_page - 1)}
                >
                    Previous
                </Button>
                <span aria-current="page" className="min-w-20 text-center font-medium text-slate-700">
                    {meta.current_page} / {meta.last_page}
                </span>
                <Button
                    variant="secondary"
                    disabled={meta.current_page >= meta.last_page}
                    aria-label={`Go to page ${Math.min(meta.last_page, meta.current_page + 1)}`}
                    onClick={() => onPageChange(meta.current_page + 1)}
                >
                    Next
                </Button>
            </div>
        </nav>
    );
}
