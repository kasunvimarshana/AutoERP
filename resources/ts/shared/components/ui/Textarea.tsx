import type { TextareaHTMLAttributes } from 'react';
import { cn } from '../../utils/cn';

export function Textarea({ className, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return (
        <textarea
            className={cn(
                'min-h-28 w-full rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-100',
                className,
            )}
            {...props}
        />
    );
}
