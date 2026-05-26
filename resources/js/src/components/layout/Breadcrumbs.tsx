import { Link } from 'react-router-dom';
import { cn } from '../../lib/cn';

export type BreadcrumbItem = {
    label: string;
    href?: string;
};

type BreadcrumbsProps = {
    items: BreadcrumbItem[];
    className?: string;
};

export function Breadcrumbs({ className, items }: BreadcrumbsProps) {
    if (items.length === 0) {
        return null;
    }

    return (
        <nav aria-label="Breadcrumb" className={cn('flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-stone-500', className)}>
            {items.map((item, index) => (
                <div key={`${item.label}-${index}`} className="flex items-center gap-2">
                    {index > 0 ? <span className="text-stone-300">/</span> : null}
                    {item.href ? (
                        <Link className="transition hover:text-stone-800" to={item.href}>
                            {item.label}
                        </Link>
                    ) : (
                        <span className="text-stone-400">{item.label}</span>
                    )}
                </div>
            ))}
        </nav>
    );
}
