import type { ApiPaginationMeta } from '../../types/api';
import { Button } from '../ui/Button';

type TablePaginationProps = {
    meta: ApiPaginationMeta | null;
    onPageChange: (page: number) => void;
};

export function TablePagination({ meta, onPageChange }: TablePaginationProps) {
    if (!meta) {
        return null;
    }

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-stone-500">
                Showing <span className="font-medium text-stone-900">{meta.from ?? 0}</span> to{' '}
                <span className="font-medium text-stone-900">{meta.to ?? 0}</span> of{' '}
                <span className="font-medium text-stone-900">{meta.total}</span> records
            </p>

            <div className="flex items-center gap-2">
                <Button
                    disabled={meta.current_page <= 1}
                    onClick={() => onPageChange(meta.current_page - 1)}
                    type="button"
                    variant="secondary"
                >
                    Previous
                </Button>
                <span className="rounded-xl border border-stone-200 bg-white px-3 py-2 text-sm text-stone-600">
                    Page {meta.current_page} of {meta.last_page}
                </span>
                <Button
                    disabled={meta.current_page >= meta.last_page}
                    onClick={() => onPageChange(meta.current_page + 1)}
                    type="button"
                    variant="secondary"
                >
                    Next
                </Button>
            </div>
        </div>
    );
}
