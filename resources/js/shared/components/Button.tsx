import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';
import { Link, type LinkProps } from 'react-router-dom';

export type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost';

const variants: Record<ButtonVariant, string> = {
    primary: 'bg-blue-600 text-white shadow-sm hover:bg-blue-700 focus:ring-blue-500',
    secondary: 'border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50 focus:ring-blue-500',
    danger: 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-500',
    ghost: 'text-slate-600 hover:bg-slate-100 focus:ring-slate-400',
};

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    loading?: boolean;
    children: ReactNode;
}

export function buttonClassName(variant: ButtonVariant = 'primary', className = ''): string {
    return `inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ${variants[variant]} ${className}`;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button({ variant = 'primary', loading, className = '', children, disabled, type = 'button', ...props }, ref) {
    return (
        <button
            ref={ref}
            type={type}
            className={buttonClassName(variant, className)}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            {...props}
        >
            {loading ? 'Working...' : children}
        </button>
    );
});

export function LinkButton({
    variant = 'primary',
    className = '',
    children,
    ...props
}: LinkProps & { variant?: ButtonVariant; className?: string; children: ReactNode }) {
    return <Link className={buttonClassName(variant, className)} {...props}>{children}</Link>;
}
