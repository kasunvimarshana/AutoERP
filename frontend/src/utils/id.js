let idCounter = 0

export function generateUniqueId(prefix = 'id') {
  return `${prefix}-${++idCounter}-${Date.now()}`
}
