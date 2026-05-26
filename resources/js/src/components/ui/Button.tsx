import type { ButtonHTMLAttributes } from 'react';
import { cn } from '../../lib/cn';

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: 'primary' | 'secondary';
};

export function Button({ className, type = 'button', variant = 'primary', ...props }: ButtonProps) {
    return (
        <button
            {...props}
            type={type}
            className={cn(
                'inline-flex h-11 items-center justify-center rounded-xl px-4 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-stone-300 disabled:cursor-not-allowed disabled:opacity-60',
                variant === 'primary'
                    ? 'bg-stone-950 text-white hover:bg-stone-800'
                    : 'border border-stone-200 bg-white text-stone-700 hover:bg-stone-50',
                className,
            )}
        />
    );
}
