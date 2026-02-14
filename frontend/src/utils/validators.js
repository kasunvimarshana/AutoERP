export function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

export function validatePhone(phone) {
  const re = /^[\d\s()+-]+$/
  return re.test(phone) && phone.replace(/\D/g, '').length >= 10
}

export function validateRequired(value) {
  if (Array.isArray(value)) return value.length > 0
  if (typeof value === 'string') return value.trim().length > 0
  return value !== null && value !== undefined
}

export function validateMinLength(value, min) {
  return value && value.length >= min
}

export function validateMaxLength(value, max) {
  return !value || value.length <= max
}

export function validateNumber(value) {
  return !isNaN(parseFloat(value)) && isFinite(value)
}

export function validatePositive(value) {
  return validateNumber(value) && parseFloat(value) > 0
}

export function validateUrl(url) {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}
