import { cn } from '../../lib/cn';

type StatusBadgeProps = {
    tone?: 'default' | 'success' | 'warning' | 'danger';
    children: string;
};

export function StatusBadge({ children, tone = 'default' }: StatusBadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                tone === 'success' && 'bg-emerald-50 text-emerald-700',
                tone === 'warning' && 'bg-amber-50 text-amber-700',
                tone === 'danger' && 'bg-red-50 text-red-700',
                tone === 'default' && 'bg-stone-100 text-stone-700',
            )}
        >
            {children}
        </span>
    );
}
