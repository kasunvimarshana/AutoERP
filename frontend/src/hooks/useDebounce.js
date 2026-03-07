import { useState, useEffect } from 'react'

/**
 * Debounces a value so that rapid changes are coalesced into a single update
 * after the given delay has elapsed without a new change.
 *
 * @template T
 * @param {T} value - The value to debounce.
 * @param {number} [delay=300] - Debounce delay in milliseconds.
 * @returns {T} The debounced value.
 */
export function useDebounce(value, delay = 300) {
  const [debouncedValue, setDebouncedValue] = useState(value)

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedValue(value), delay)
    return () => clearTimeout(timer)
  }, [value, delay])

  return debouncedValue
}
