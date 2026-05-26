import type { ReactNode } from 'react';

type SearchFilterToolbarProps = {
    search?: ReactNode;
    filters?: ReactNode;
    trailing?: ReactNode;
};

export function SearchFilterToolbar({ filters, search, trailing }: SearchFilterToolbarProps) {
    return (
        <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div className="flex flex-1 flex-col gap-3 md:flex-row">{search}{filters}</div>
            {trailing ? <div className="flex shrink-0 justify-end">{trailing}</div> : null}
        </div>
    );
}
