import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';
import { Link, type LinkProps } from 'react-router-dom';

export type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost';

const variants: Record<ButtonVariant, string> = {
    primary: 'bg-blue-600 text-white shadow-xs hover:bg-blue-700 active:bg-blue-800 focus-visible:ring-blue-500',
    secondary: 'border border-slate-300 bg-white text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 active:bg-slate-100 focus-visible:ring-blue-500',
    danger: 'bg-rose-600 text-white shadow-xs hover:bg-rose-700 active:bg-rose-800 focus-visible:ring-rose-500',
    ghost: 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 active:bg-slate-200 focus-visible:ring-slate-400',
};

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    loading?: boolean;
    loadingLabel?: ReactNode;
    children: ReactNode;
}

export function buttonClassName(variant: ButtonVariant = 'primary', className = ''): string {
    return `inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all duration-150 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ${variants[variant]} ${className}`;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button({ variant = 'primary', loading, loadingLabel, className = '', children, disabled, type = 'button', ...props }, ref) {
    return (
        <button
            ref={ref}
            type={type}
            className={buttonClassName(variant, className)}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            {...props}
        >
            {loading ? (
                <>
                    <svg className="h-4 w-4 shrink-0 animate-spin text-current" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" />
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <span>{loadingLabel ?? loadingText(children)}</span>
                </>
            ) : children}
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

function loadingText(children: ReactNode): ReactNode {
    return typeof children === 'string' ? `${children.replace(/\.+$/, '')}...` : 'Please wait...';
}
