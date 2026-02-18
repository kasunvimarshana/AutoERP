import { ref, computed } from 'vue'
import { useAuthStore } from '../stores/authStore'

// User's preferred currency (defaults to tenant currency or USD)
const userCurrency = ref('USD')

// Exchange rates cache (base currency: USD)
const exchangeRates = ref({
  USD: 1.0,
  EUR: 0.85,
  GBP: 0.73,
  JPY: 110.0,
  AUD: 1.35,
  CAD: 1.25,
  CHF: 0.92,
  CNY: 6.45,
  INR: 74.5,
  SGD: 1.35
})

// Last update timestamp
const lastUpdate = ref(null)

/**
 * Multi-currency support composable
 * Handles currency conversion, formatting, and exchange rates
 */
export function useCurrency() {
  const authStore = useAuthStore()

  /**
   * Set user's preferred currency
   * @param {string} currency - ISO 4217 currency code (e.g., 'USD', 'EUR')
   */
  const setUserCurrency = (currency) => {
    if (!isValidCurrency(currency)) {
      console.warn(`Invalid currency code: ${currency}. Using USD.`)
      return
    }
    
    userCurrency.value = currency
    localStorage.setItem('user_currency', currency)
  }

  /**
   * Validate currency code
   * @param {string} currency - ISO 4217 currency code
   * @returns {boolean}
   */
  const isValidCurrency = (currency) => {
    try {
      new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
      })
      return true
    } catch (error) {
      return false
    }
  }

  /**
   * Fetch latest exchange rates from API
   * @returns {Promise<Object>}
   */
  const fetchExchangeRates = async () => {
    try {
      // In production, this would call the backend API
      // For now, we'll use cached rates
      // const response = await apiClient.get('/exchange-rates')
      // exchangeRates.value = response.data.rates
      
      lastUpdate.value = new Date()
      return exchangeRates.value
    } catch (error) {
      console.error('Failed to fetch exchange rates:', error)
      return exchangeRates.value
    }
  }

  /**
   * Convert amount from one currency to another
   * @param {number} amount - Amount to convert
   * @param {string} fromCurrency - Source currency code
   * @param {string} toCurrency - Target currency code
   * @returns {number}
   */
  const convert = (amount, fromCurrency, toCurrency) => {
    if (fromCurrency === toCurrency) {
      return amount
    }
    
    const fromRate = exchangeRates.value[fromCurrency]
    const toRate = exchangeRates.value[toCurrency]
    
    if (!fromRate || !toRate) {
      console.warn(`Exchange rate not available for ${fromCurrency} or ${toCurrency}`)
      return amount
    }
    
    // Convert to USD first, then to target currency
    const usdAmount = amount / fromRate
    return usdAmount * toRate
  }

  /**
   * Convert to user's preferred currency
   * @param {number} amount - Amount to convert
   * @param {string} fromCurrency - Source currency code
   * @returns {number}
   */
  const convertToUserCurrency = (amount, fromCurrency) => {
    return convert(amount, fromCurrency, userCurrency.value)
  }

  /**
   * Format amount in specified currency
   * @param {number} amount - Amount to format
   * @param {string} currency - Currency code (defaults to user currency)
   * @param {Object} options - Intl.NumberFormat options
   * @returns {string}
   */
  const format = (amount, currency = null, options = {}) => {
    const currencyCode = currency || userCurrency.value
    
    const defaultOptions = {
      style: 'currency',
      currency: currencyCode,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }
    
    return new Intl.NumberFormat('en-US', {
      ...defaultOptions,
      ...options
    }).format(amount)
  }

  /**
   * Format and convert amount to user's currency
   * @param {number} amount - Amount to format
   * @param {string} fromCurrency - Source currency code
   * @param {Object} options - Formatting options
   * @returns {string}
   */
  const formatAndConvert = (amount, fromCurrency, options = {}) => {
    const converted = convertToUserCurrency(amount, fromCurrency)
    return format(converted, userCurrency.value, options)
  }

  /**
   * Get currency symbol
   * @param {string} currency - Currency code
   * @returns {string}
   */
  const getSymbol = (currency = null) => {
    const currencyCode = currency || userCurrency.value
    
    const formatted = new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currencyCode,
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(0)
    
    // Extract symbol from formatted string
    return formatted.replace(/[\d.,\s]/g, '')
  }

  /**
   * Get currency display name
   * @param {string} currency - Currency code
   * @param {string} locale - Locale for display name
   * @returns {string}
   */
  const getDisplayName = (currency = null, locale = 'en') => {
    const currencyCode = currency || userCurrency.value
    
    try {
      const displayNames = new Intl.DisplayNames([locale], { type: 'currency' })
      return displayNames.of(currencyCode)
    } catch (error) {
      return currencyCode
    }
  }

  /**
   * Get list of supported currencies
   * @returns {Array<{code: string, name: string, symbol: string, rate: number}>}
   */
  const getSupportedCurrencies = () => {
    return Object.keys(exchangeRates.value).map(code => ({
      code,
      name: getDisplayName(code),
      symbol: getSymbol(code),
      rate: exchangeRates.value[code]
    })).sort((a, b) => a.code.localeCompare(b.code))
  }

  /**
   * Get exchange rate for a currency pair
   * @param {string} fromCurrency - Source currency
   * @param {string} toCurrency - Target currency
   * @returns {number}
   */
  const getExchangeRate = (fromCurrency, toCurrency) => {
    if (fromCurrency === toCurrency) return 1.0
    
    const fromRate = exchangeRates.value[fromCurrency]
    const toRate = exchangeRates.value[toCurrency]
    
    if (!fromRate || !toRate) return null
    
    return toRate / fromRate
  }

  /**
   * Format compact currency (e.g., "$1.2K", "$3.4M")
   * @param {number} amount - Amount to format
   * @param {string} currency - Currency code
   * @returns {string}
   */
  const formatCompact = (amount, currency = null) => {
    const currencyCode = currency || userCurrency.value
    
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currencyCode,
      notation: 'compact',
      compactDisplay: 'short'
    }).format(amount)
  }

  /**
   * Parse currency string to number
   * @param {string} currencyString - Formatted currency string
   * @returns {number}
   */
  const parse = (currencyString) => {
    if (!currencyString) return 0
    
    // Remove currency symbols, spaces, and commas
    const cleaned = currencyString.replace(/[^0-9.-]/g, '')
    return parseFloat(cleaned) || 0
  }

  /**
   * Get tenant's default currency
   * @returns {string}
   */
  const getTenantCurrency = () => {
    return authStore.user?.tenant?.currency || 'USD'
  }

  /**
   * Initialize currency from user preferences or tenant settings
   */
  const initialize = async () => {
    const savedCurrency = localStorage.getItem('user_currency')
    const tenantCurrency = getTenantCurrency()
    
    if (savedCurrency && isValidCurrency(savedCurrency)) {
      userCurrency.value = savedCurrency
    } else if (tenantCurrency) {
      userCurrency.value = tenantCurrency
    }
    
    // Fetch latest exchange rates
    await fetchExchangeRates()
  }

  /**
   * Check if exchange rates need updating (older than 1 hour)
   * @returns {boolean}
   */
  const needsUpdate = computed(() => {
    if (!lastUpdate.value) return true
    
    const hoursSinceUpdate = (Date.now() - lastUpdate.value) / (1000 * 60 * 60)
    return hoursSinceUpdate > 1
  })

  return {
    // State
    userCurrency: computed(() => userCurrency.value),
    exchangeRates: computed(() => exchangeRates.value),
    lastUpdate: computed(() => lastUpdate.value),
    needsUpdate,
    
    // Methods
    setUserCurrency,
    isValidCurrency,
    fetchExchangeRates,
    convert,
    convertToUserCurrency,
    format,
    formatAndConvert,
    formatCompact,
    getSymbol,
    getDisplayName,
    getSupportedCurrencies,
    getExchangeRate,
    parse,
    getTenantCurrency,
    initialize
  }
}
