export default function Select({
  label,
  name,
  options = [],
  error,
  placeholder,
  className = '',
  ...rest
}) {
  const baseSelectClasses = [
    'block w-full rounded-lg border px-3 py-2 text-sm text-gray-900 bg-white',
    'focus:outline-none focus:ring-2 focus:ring-offset-0',
    'transition-colors duration-150',
    'disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500',
    error
      ? 'border-red-500 focus:border-red-500 focus:ring-red-300'
      : 'border-gray-300 focus:border-blue-500 focus:ring-blue-300',
  ].join(' ')

  return (
    <div className={`flex flex-col gap-1 ${className}`}>
      {label && (
        <label htmlFor={name} className="text-sm font-medium text-gray-700">
          {label}
        </label>
      )}

      <select id={name} name={name} className={baseSelectClasses} {...rest}>
        {placeholder && (
          <option value="" disabled>
            {placeholder}
          </option>
        )}
        {options.map((opt) => (
          <option key={opt.value} value={opt.value}>
            {opt.label}
          </option>
        ))}
      </select>

      {error && <p className="text-xs text-red-600">{error}</p>}
    </div>
  )
}
