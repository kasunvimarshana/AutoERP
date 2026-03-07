export default function Input({
  label,
  name,
  error,
  helperText,
  className = '',
  rows = 4,
  ...rest
}) {
  const baseInputClasses = [
    'block w-full rounded-lg border px-3 py-2 text-sm text-gray-900',
    'placeholder:text-gray-400',
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
        <label
          htmlFor={name}
          className="text-sm font-medium text-gray-700"
        >
          {label}
        </label>
      )}

      {rest.type === 'textarea' ? (
        <textarea
          id={name}
          name={name}
          rows={rows}
          className={`${baseInputClasses} resize-y`}
          {...rest}
          type={undefined}
        />
      ) : (
        <input
          id={name}
          name={name}
          className={baseInputClasses}
          {...rest}
        />
      )}

      {error && (
        <p className="text-xs text-red-600">{error}</p>
      )}

      {!error && helperText && (
        <p className="text-xs text-gray-500">{helperText}</p>
      )}
    </div>
  )
}
