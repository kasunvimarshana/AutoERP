import { TrendingUp, TrendingDown } from 'lucide-react'

const colorMap = {
  blue:   { bg: 'bg-blue-100',   icon: 'text-blue-600',   ring: 'ring-blue-200' },
  green:  { bg: 'bg-green-100',  icon: 'text-green-600',  ring: 'ring-green-200' },
  yellow: { bg: 'bg-yellow-100', icon: 'text-yellow-600', ring: 'ring-yellow-200' },
  purple: { bg: 'bg-purple-100', icon: 'text-purple-600', ring: 'ring-purple-200' },
  red:    { bg: 'bg-red-100',    icon: 'text-red-600',    ring: 'ring-red-200' },
}

export default function StatsCard({
  title,
  value,
  icon: Icon,
  change,
  changeLabel,
  color = 'blue',
}) {
  const c = colorMap[color] ?? colorMap.blue
  const isPositive = change != null && change >= 0
  const hasChange = change != null

  return (
    <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex gap-4 items-start hover:shadow-md transition-shadow duration-200">
      {/* Icon circle */}
      {Icon && (
        <div
          className={[
            'flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full ring-4',
            c.bg,
            c.ring,
          ].join(' ')}
        >
          <Icon className={`w-6 h-6 ${c.icon}`} />
        </div>
      )}

      {/* Content */}
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium text-gray-500 truncate">{title}</p>
        <p className="mt-0.5 text-2xl font-bold text-gray-900 truncate">{value}</p>

        {hasChange && (
          <div className="mt-1.5 flex items-center gap-1 text-xs font-medium">
            {isPositive ? (
              <TrendingUp className="w-3.5 h-3.5 text-green-500" />
            ) : (
              <TrendingDown className="w-3.5 h-3.5 text-red-500" />
            )}
            <span className={isPositive ? 'text-green-600' : 'text-red-600'}>
              {isPositive ? '+' : ''}
              {change}%
            </span>
            {changeLabel && (
              <span className="text-gray-400 font-normal">{changeLabel}</span>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
