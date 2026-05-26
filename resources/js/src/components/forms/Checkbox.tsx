import type { InputHTMLAttributes } from 'react';
import { cn } from '../../lib/cn';

type CheckboxProps = InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    description?: string;
};

export function Checkbox({ className, description, label, ...props }: CheckboxProps) {
    return (
        <label className={cn('flex items-start gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3', className)}>
            <input {...props} className="mt-0.5 h-4 w-4 rounded border-stone-300 text-stone-950 focus:ring-stone-300" type="checkbox" />
            <span className="min-w-0">
                <span className="block text-sm font-medium text-stone-900">{label}</span>
                {description ? <span className="mt-1 block text-xs leading-5 text-stone-500">{description}</span> : null}
            </span>
        </label>
    );
}
