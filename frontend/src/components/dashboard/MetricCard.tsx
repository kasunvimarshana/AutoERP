import React from 'react';
import clsx from 'clsx';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';

interface MetricCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon: React.ReactNode;
  trend?: { value: number; label: string };
  color?: 'blue' | 'green' | 'yellow' | 'red' | 'purple';
}

const colorMap = {
  blue: { bg: 'bg-blue-50', icon: 'bg-blue-100 text-blue-600', text: 'text-blue-600' },
  green: { bg: 'bg-green-50', icon: 'bg-green-100 text-green-600', text: 'text-green-600' },
  yellow: { bg: 'bg-yellow-50', icon: 'bg-yellow-100 text-yellow-600', text: 'text-yellow-600' },
  red: { bg: 'bg-red-50', icon: 'bg-red-100 text-red-600', text: 'text-red-600' },
  purple: { bg: 'bg-purple-50', icon: 'bg-purple-100 text-purple-600', text: 'text-purple-600' },
};

const MetricCard: React.FC<MetricCardProps> = ({
  title,
  value,
  subtitle,
  icon,
  trend,
  color = 'blue',
}) => {
  const colors = colorMap[color];

  const TrendIcon =
    !trend ? null : trend.value > 0 ? TrendingUp : trend.value < 0 ? TrendingDown : Minus;
  const trendColor = !trend
    ? ''
    : trend.value > 0
    ? 'text-green-600'
    : trend.value < 0
    ? 'text-red-600'
    : 'text-gray-500';

  return (
    <div className="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm font-medium text-gray-500">{title}</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
          {subtitle && <p className="text-xs text-gray-400 mt-0.5">{subtitle}</p>}
        </div>
        <div className={clsx('w-11 h-11 rounded-xl flex items-center justify-center', colors.icon)}>
          {icon}
        </div>
      </div>
      {trend && TrendIcon && (
        <div className={clsx('flex items-center gap-1 mt-3 text-xs font-medium', trendColor)}>
          <TrendIcon size={13} />
          <span>
            {Math.abs(trend.value)}% {trend.label}
          </span>
        </div>
      )}
    </div>
  );
};

export default MetricCard;
