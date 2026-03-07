import React from 'react';
import clsx from 'clsx';

type StatusVariant =
  | 'success'
  | 'warning'
  | 'danger'
  | 'info'
  | 'neutral'
  | 'purple';

interface StatusBadgeProps {
  label: string;
  variant?: StatusVariant;
  dot?: boolean;
  className?: string;
}

const variantStyles: Record<StatusVariant, string> = {
  success: 'bg-green-100 text-green-700 border-green-200',
  warning: 'bg-yellow-100 text-yellow-700 border-yellow-200',
  danger: 'bg-red-100 text-red-700 border-red-200',
  info: 'bg-blue-100 text-blue-700 border-blue-200',
  neutral: 'bg-gray-100 text-gray-600 border-gray-200',
  purple: 'bg-purple-100 text-purple-700 border-purple-200',
};

const dotStyles: Record<StatusVariant, string> = {
  success: 'bg-green-500',
  warning: 'bg-yellow-500',
  danger: 'bg-red-500',
  info: 'bg-blue-500',
  neutral: 'bg-gray-400',
  purple: 'bg-purple-500',
};

export const orderStatusVariant = (status: string): StatusVariant => {
  switch (status) {
    case 'delivered':
    case 'confirmed':
      return 'success';
    case 'processing':
    case 'shipped':
      return 'info';
    case 'pending':
      return 'warning';
    case 'cancelled':
    case 'failed':
      return 'danger';
    case 'refunded':
      return 'purple';
    default:
      return 'neutral';
  }
};

export const paymentStatusVariant = (status: string): StatusVariant => {
  switch (status) {
    case 'paid':
      return 'success';
    case 'pending':
      return 'warning';
    case 'failed':
      return 'danger';
    case 'refunded':
      return 'purple';
    default:
      return 'neutral';
  }
};

const StatusBadge: React.FC<StatusBadgeProps> = ({
  label,
  variant = 'neutral',
  dot = false,
  className,
}) => {
  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border',
        variantStyles[variant],
        className,
      )}
    >
      {dot && <span className={clsx('w-1.5 h-1.5 rounded-full', dotStyles[variant])} />}
      {label}
    </span>
  );
};

export default StatusBadge;
