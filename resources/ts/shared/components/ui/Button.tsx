import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { cn } from '../../utils/cn';

type ButtonVariant = 'primary' | 'secondary' | 'blue' | 'ghost' | 'danger';

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    icon?: ReactNode;
    variant?: ButtonVariant;
};

const variants: Record<ButtonVariant, string> = {
    blue: 'bg-blue-600 text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700',
    danger: 'bg-red-600 text-white shadow-sm shadow-red-600/20 hover:bg-red-700',
    ghost: 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-950',
    primary: 'bg-blue-600 text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700',
    secondary: 'border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50',
};

export function Button({ children, className, icon, type = 'button', variant = 'primary', ...props }: ButtonProps) {
    return (
        <button
            className={cn(
                'inline-flex h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-300 disabled:cursor-not-allowed disabled:opacity-50',
                variants[variant],
                className,
            )}
            type={type}
            {...props}
        >
            {icon}
            {children}
        </button>
    );
}
