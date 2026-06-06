import type { PaginationMeta } from '@/shared/types/pagination';
import { Button } from './Button';

export function Pagination({ meta, onPageChange }: {
    meta?: PaginationMeta;
    onPageChange: (page: number) => void;
}) {
    if (!meta || meta.last_page <= 1) return null;
    return (
        <div className="mt-4 flex items-center justify-between gap-3 text-sm text-slate-600">
            <span>{meta.from ?? 0}-{meta.to ?? 0} of {meta.total}</span>
            <div className="flex gap-2">
                <Button variant="secondary" disabled={meta.current_page <= 1} onClick={() => onPageChange(meta.current_page - 1)}>Previous</Button>
                <Button variant="secondary" disabled={meta.current_page >= meta.last_page} onClick={() => onPageChange(meta.current_page + 1)}>Next</Button>
            </div>
        </div>
    );
}
