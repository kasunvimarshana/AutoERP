import type { InputHTMLAttributes } from 'react';
import { cn } from '../../utils/cn';

export function Checkbox({ className, ...props }: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            className={cn('h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-slate-300', className)}
            type="checkbox"
            {...props}
        />
    );
}
