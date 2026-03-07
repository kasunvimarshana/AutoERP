import { useState, useEffect, useRef } from 'react'
import { Search, X } from 'lucide-react'

export default function SearchInput({
  value,
  onChange,
  placeholder = 'Search…',
  debounceMs = 300,
  className = '',
}) {
  const [localValue, setLocalValue] = useState(value ?? '')
  const timerRef = useRef(null)

  // Keep localValue in sync if parent resets value externally.
  useEffect(() => {
    setLocalValue(value ?? '')
  }, [value])

  function handleChange(e) {
    const next = e.target.value
    setLocalValue(next)
    clearTimeout(timerRef.current)
    timerRef.current = setTimeout(() => {
      onChange(next)
    }, debounceMs)
  }

  function handleClear() {
    clearTimeout(timerRef.current)
    setLocalValue('')
    onChange('')
  }

  // Clean up timer on unmount.
  useEffect(() => () => clearTimeout(timerRef.current), [])

  return (
    <div className={`relative flex items-center ${className}`}>
      <Search className="absolute left-3 w-4 h-4 text-gray-400 pointer-events-none" />

      <input
        type="text"
        value={localValue}
        onChange={handleChange}
        placeholder={placeholder}
        className={[
          'w-full pl-9 pr-8 py-2 text-sm rounded-lg border border-gray-300',
          'focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400',
          'placeholder:text-gray-400 transition-colors duration-150',
        ].join(' ')}
      />

      {localValue && (
        <button
          type="button"
          onClick={handleClear}
          aria-label="Clear search"
          className="absolute right-2.5 p-0.5 rounded text-gray-400 hover:text-gray-600 transition-colors"
        >
          <X className="w-4 h-4" />
        </button>
      )}
    </div>
  )
}
