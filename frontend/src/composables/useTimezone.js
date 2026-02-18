import { ref, computed } from 'vue'

// User's preferred timezone (defaults to browser timezone)
const userTimezone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC')

// Application default timezone (typically UTC or tenant default)
const appTimezone = ref('UTC')

/**
 * Multi-timezone support composable
 * Handles conversion between timezones and formatting dates
 */
export function useTimezone() {
  /**
   * Set user's preferred timezone
   * @param {string} timezone - IANA timezone identifier (e.g., 'America/New_York')
   */
  const setUserTimezone = (timezone) => {
    if (!isValidTimezone(timezone)) {
      console.warn(`Invalid timezone: ${timezone}. Using browser default.`)
      return
    }
    
    userTimezone.value = timezone
    
    // Store in localStorage for persistence
    localStorage.setItem('user_timezone', timezone)
  }

  /**
   * Set application/tenant default timezone
   * @param {string} timezone - IANA timezone identifier
   */
  const setAppTimezone = (timezone) => {
    if (!isValidTimezone(timezone)) {
      console.warn(`Invalid app timezone: ${timezone}. Using UTC.`)
      return
    }
    
    appTimezone.value = timezone
  }

  /**
   * Validate timezone identifier
   * @param {string} timezone - IANA timezone identifier
   * @returns {boolean}
   */
  const isValidTimezone = (timezone) => {
    try {
      Intl.DateTimeFormat(undefined, { timeZone: timezone })
      return true
    } catch (error) {
      return false
    }
  }

  /**
   * Convert date from one timezone to another
   * @param {Date|string} date - Date to convert
   * @param {string} fromTimezone - Source timezone
   * @param {string} toTimezone - Target timezone
   * @returns {Date}
   */
  const convertTimezone = (date, fromTimezone, toTimezone) => {
    const dateObj = new Date(date)
    
    // Get time in source timezone
    const sourceTime = dateObj.toLocaleString('en-US', { timeZone: fromTimezone })
    
    // Parse and create date in target timezone
    const targetDate = new Date(sourceTime)
    
    return targetDate
  }

  /**
   * Format date in user's timezone
   * @param {Date|string} date - Date to format
   * @param {Object} options - Intl.DateTimeFormat options
   * @returns {string}
   */
  const formatInUserTimezone = (date, options = {}) => {
    if (!date) return ''
    
    const defaultOptions = {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      timeZone: userTimezone.value
    }
    
    return new Intl.DateTimeFormat('en-US', {
      ...defaultOptions,
      ...options
    }).format(new Date(date))
  }

  /**
   * Format date with timezone display
   * @param {Date|string} date - Date to format
   * @param {Object} options - Formatting options
   * @returns {string}
   */
  const formatWithTimezone = (date, options = {}) => {
    if (!date) return ''
    
    const formatted = formatInUserTimezone(date, options)
    const tzAbbr = getTimezoneAbbreviation(userTimezone.value)
    
    return `${formatted} ${tzAbbr}`
  }

  /**
   * Get timezone abbreviation (e.g., 'EST', 'PST')
   * @param {string} timezone - IANA timezone identifier
   * @returns {string}
   */
  const getTimezoneAbbreviation = (timezone) => {
    const date = new Date()
    const options = {
      timeZone: timezone,
      timeZoneName: 'short'
    }
    
    const formatted = new Intl.DateTimeFormat('en-US', options).format(date)
    const parts = formatted.split(' ')
    
    return parts[parts.length - 1]
  }

  /**
   * Get timezone offset in hours
   * @param {string} timezone - IANA timezone identifier
   * @returns {number}
   */
  const getTimezoneOffset = (timezone) => {
    const now = new Date()
    const tzString = now.toLocaleString('en-US', { timeZone: timezone })
    const tzDate = new Date(tzString)
    const utcDate = new Date(now.toLocaleString('en-US', { timeZone: 'UTC' }))
    
    return (tzDate - utcDate) / (1000 * 60 * 60)
  }

  /**
   * Convert UTC to user's timezone
   * @param {Date|string} utcDate - UTC date
   * @returns {Date}
   */
  const utcToUserTime = (utcDate) => {
    return convertTimezone(utcDate, 'UTC', userTimezone.value)
  }

  /**
   * Convert user's timezone to UTC
   * @param {Date|string} userDate - Date in user's timezone
   * @returns {Date}
   */
  const userTimeToUtc = (userDate) => {
    return convertTimezone(userDate, userTimezone.value, 'UTC')
  }

  /**
   * Format date for API (ISO 8601 in UTC)
   * @param {Date|string} date - Date to format
   * @returns {string}
   */
  const formatForApi = (date) => {
    if (!date) return ''
    return new Date(date).toISOString()
  }

  /**
   * Parse date from API (ISO 8601 UTC) to user timezone
   * @param {string} isoString - ISO 8601 date string
   * @returns {Date}
   */
  const parseFromApi = (isoString) => {
    if (!isoString) return null
    return new Date(isoString)
  }

  /**
   * Get list of common timezones
   * @returns {Array<{value: string, label: string, offset: number}>}
   */
  const getCommonTimezones = () => {
    const timezones = [
      'UTC',
      'America/New_York',
      'America/Chicago',
      'America/Denver',
      'America/Los_Angeles',
      'America/Phoenix',
      'America/Anchorage',
      'Pacific/Honolulu',
      'Europe/London',
      'Europe/Paris',
      'Europe/Berlin',
      'Europe/Moscow',
      'Asia/Dubai',
      'Asia/Kolkata',
      'Asia/Bangkok',
      'Asia/Singapore',
      'Asia/Hong_Kong',
      'Asia/Tokyo',
      'Asia/Seoul',
      'Australia/Sydney',
      'Australia/Melbourne',
      'Pacific/Auckland'
    ]
    
    return timezones.map(tz => ({
      value: tz,
      label: `${tz.replace(/_/g, ' ')} (${getTimezoneAbbreviation(tz)})`,
      offset: getTimezoneOffset(tz)
    })).sort((a, b) => a.offset - b.offset)
  }

  /**
   * Format relative time (e.g., "2 hours ago", "in 3 days")
   * @param {Date|string} date - Date to format
   * @param {string} locale - Locale for formatting
   * @returns {string}
   */
  const formatRelative = (date, locale = 'en') => {
    if (!date) return ''
    
    const now = new Date()
    const target = new Date(date)
    const diffMs = target - now
    const diffSec = Math.floor(diffMs / 1000)
    const diffMin = Math.floor(diffSec / 60)
    const diffHour = Math.floor(diffMin / 60)
    const diffDay = Math.floor(diffHour / 24)
    
    const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })
    
    if (Math.abs(diffSec) < 60) {
      return rtf.format(diffSec, 'second')
    }
    if (Math.abs(diffMin) < 60) {
      return rtf.format(diffMin, 'minute')
    }
    if (Math.abs(diffHour) < 24) {
      return rtf.format(diffHour, 'hour')
    }
    if (Math.abs(diffDay) < 30) {
      return rtf.format(diffDay, 'day')
    }
    if (Math.abs(diffDay) < 365) {
      const diffMonth = Math.floor(diffDay / 30)
      return rtf.format(diffMonth, 'month')
    }
    const diffYear = Math.floor(diffDay / 365)
    return rtf.format(diffYear, 'year')
  }

  /**
   * Initialize timezone from user preferences or localStorage
   */
  const initialize = () => {
    const savedTimezone = localStorage.getItem('user_timezone')
    if (savedTimezone && isValidTimezone(savedTimezone)) {
      userTimezone.value = savedTimezone
    }
  }

  // Auto-initialize
  initialize()

  return {
    // State
    userTimezone: computed(() => userTimezone.value),
    appTimezone: computed(() => appTimezone.value),
    
    // Methods
    setUserTimezone,
    setAppTimezone,
    isValidTimezone,
    convertTimezone,
    formatInUserTimezone,
    formatWithTimezone,
    getTimezoneAbbreviation,
    getTimezoneOffset,
    utcToUserTime,
    userTimeToUtc,
    formatForApi,
    parseFromApi,
    getCommonTimezones,
    formatRelative,
    initialize
  }
}
