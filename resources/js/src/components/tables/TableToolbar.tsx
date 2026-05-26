import type { PropsWithChildren, ReactNode } from 'react';

type TableToolbarProps = PropsWithChildren<{
    title?: string;
    description?: string;
    actions?: ReactNode;
}>;

export function TableToolbar({ actions, children, description, title }: TableToolbarProps) {
    return (
        <div className="flex flex-col gap-4 border-b border-stone-200/80 px-6 py-5">
            {(title || description || actions) ? (
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        {title ? <h3 className="text-lg font-semibold text-stone-950">{title}</h3> : null}
                        {description ? <p className="mt-1 text-sm leading-6 text-stone-600">{description}</p> : null}
                    </div>
                    {actions ? <div className="flex flex-wrap gap-2">{actions}</div> : null}
                </div>
            ) : null}
            {children}
        </div>
    );
}
