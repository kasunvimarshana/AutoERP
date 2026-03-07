import React from 'react';
import clsx from 'clsx';

type BadgeVariant = 'success' | 'warning' | 'danger' | 'info' | 'gray' | 'purple';

interface BadgeProps {
  children: React.ReactNode;
  variant?: BadgeVariant;
  className?: string;
}

const Badge: React.FC<BadgeProps> = ({ children, variant = 'gray', className }) => {
  const variants: Record<BadgeVariant, string> = {
    success: 'bg-green-100 text-green-800 ring-green-200',
    warning: 'bg-yellow-100 text-yellow-800 ring-yellow-200',
    danger: 'bg-red-100 text-red-800 ring-red-200',
    info: 'bg-blue-100 text-blue-800 ring-blue-200',
    gray: 'bg-gray-100 text-gray-800 ring-gray-200',
    purple: 'bg-purple-100 text-purple-800 ring-purple-200',
  };
  return (
    <span
      className={clsx(
        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset',
        variants[variant],
        className
      )}
    >
      {children}
    </span>
  );
};

export default Badge;
