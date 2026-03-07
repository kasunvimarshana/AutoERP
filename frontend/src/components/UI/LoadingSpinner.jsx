const sizeMap = {
  sm: { spinner: 'w-5 h-5', border: 'border-2', text: 'text-xs' },
  md: { spinner: 'w-8 h-8', border: 'border-4', text: 'text-sm' },
  lg: { spinner: 'w-12 h-12', border: 'border-4', text: 'text-base' },
}

export default function LoadingSpinner({ size = 'md', className = '', text }) {
  const s = sizeMap[size] ?? sizeMap.md

  return (
    <div className={`flex flex-col items-center justify-center gap-2 ${className}`}>
      <span
        className={[
          s.spinner,
          s.border,
          'rounded-full border-gray-300 border-t-blue-600 animate-spin',
        ].join(' ')}
        role="status"
        aria-label="Loading"
      />
      {text && <p className={`text-gray-500 ${s.text}`}>{text}</p>}
    </div>
  )
}
