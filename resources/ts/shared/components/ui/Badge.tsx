import type { HTMLAttributes } from 'react';
import { cn } from '../../utils/cn';
import type { StatusTone } from '../../types/common.types';

const tones: Record<StatusTone, string> = {
    danger: 'bg-red-50 text-red-700 ring-red-100',
    dark: 'bg-slate-950 text-white ring-slate-950',
    default: 'bg-slate-100 text-slate-700 ring-slate-200',
    info: 'bg-blue-50 text-blue-700 ring-blue-100',
    success: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    warning: 'bg-amber-50 text-amber-700 ring-amber-100',
};

type BadgeProps = HTMLAttributes<HTMLSpanElement> & {
    tone?: StatusTone;
};

export function Badge({ className, tone = 'default', ...props }: BadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                tones[tone],
                className,
            )}
            {...props}
        />
    );
}
