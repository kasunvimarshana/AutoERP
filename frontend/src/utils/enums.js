/**
 * Backend Enums - Must match exactly with backend enum values
 * These are imported from backend/app/Enums/ and backend/app/Modules/*/Enums/
 */

// Product Types (app/Enums/ProductType.php)
export const ProductType = {
  INVENTORY: 'inventory',
  SERVICE: 'service',
  COMBO: 'combo',
  BUNDLE: 'bundle',
  
  isPhysical(type) {
    return type === this.INVENTORY
  },
  
  requiresStockTracking(type) {
    return type === this.INVENTORY || type === this.BUNDLE
  }
}

// Stock Movement Types (app/Enums/StockMovementType.php)
export const StockMovementType = {
  PURCHASE: 'purchase',
  SALE: 'sale',
  ADJUSTMENT_IN: 'adjustment_in',
  ADJUSTMENT_OUT: 'adjustment_out',
  TRANSFER_IN: 'transfer_in',
  TRANSFER_OUT: 'transfer_out',
  RETURN_IN: 'return_in',
  RETURN_OUT: 'return_out',
  PRODUCTION_IN: 'production_in',
  PRODUCTION_OUT: 'production_out',
  DAMAGE: 'damage',
  LOSS: 'loss',
  
  isIncrease(type) {
    return [
      this.PURCHASE,
      this.ADJUSTMENT_IN,
      this.TRANSFER_IN,
      this.RETURN_IN,
      this.PRODUCTION_IN
    ].includes(type)
  },
  
  isDecrease(type) {
    return [
      this.SALE,
      this.ADJUSTMENT_OUT,
      this.TRANSFER_OUT,
      this.RETURN_OUT,
      this.PRODUCTION_OUT,
      this.DAMAGE,
      this.LOSS
    ].includes(type)
  },
  
  getSign(type) {
    return this.isIncrease(type) ? 1 : -1
  }
}

// Invoice Status (app/Modules/POS/Enums/InvoiceStatus.php)
export const InvoiceStatus = {
  DRAFT: 'draft',
  PENDING: 'pending',
  PARTIALLY_PAID: 'partially_paid',
  PAID: 'paid',
  OVERDUE: 'overdue',
  CANCELLED: 'cancelled',
  
  canReceivePayment(status) {
    return [this.PENDING, this.PARTIALLY_PAID, this.OVERDUE].includes(status)
  },
  
  isFinal(status) {
    return [this.PAID, this.CANCELLED].includes(status)
  }
}

// Payment Method (app/Modules/POS/Enums/PaymentMethod.php)
export const PaymentMethod = {
  CASH: 'cash',
  CARD: 'card',
  BANK_TRANSFER: 'bank_transfer',
  CHEQUE: 'cheque',
  MOBILE_MONEY: 'mobile_money',
  CREDIT: 'credit',
  
  requiresReference(method) {
    return [this.BANK_TRANSFER, this.CHEQUE, this.CARD].includes(method)
  }
}

// Sales Order Status (app/Modules/POS/Enums/SalesOrderStatus.php)
export const SalesOrderStatus = {
  DRAFT: 'draft',
  QUOTATION: 'quotation',
  CONFIRMED: 'confirmed',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  INVOICED: 'invoiced',
  CANCELLED: 'cancelled',
  
  canTransitionTo(currentStatus, newStatus) {
    const transitions = {
      draft: ['quotation', 'confirmed', 'cancelled'],
      quotation: ['confirmed', 'cancelled'],
      confirmed: ['in_progress', 'cancelled'],
      in_progress: ['completed', 'cancelled'],
      completed: ['invoiced'],
      invoiced: [],
      cancelled: []
    }
    
    return transitions[currentStatus]?.includes(newStatus) || false
  }
}

// Account Type (app/Modules/Finance/Enums/AccountType.php)
export const AccountType = {
  ASSET: 'asset',
  LIABILITY: 'liability',
  EQUITY: 'equity',
  REVENUE: 'revenue',
  EXPENSE: 'expense',
  CONTRA_ASSET: 'contra_asset',
  CONTRA_LIABILITY: 'contra_liability',
  
  normalBalance(type) {
    return this.isDebitNormal(type) ? 'debit' : 'credit'
  },
  
  isDebitNormal(type) {
    return [this.ASSET, this.EXPENSE, this.CONTRA_LIABILITY].includes(type)
  },
  
  isCreditNormal(type) {
    return [this.LIABILITY, this.EQUITY, this.REVENUE, this.CONTRA_ASSET].includes(type)
  }
}

// Debit/Credit (app/Modules/Finance/Enums/DebitCredit.php)
export const DebitCredit = {
  DEBIT: 'debit',
  CREDIT: 'credit'
}

// Journal Entry Status (app/Modules/Finance/Enums/JournalEntryStatus.php)
export const JournalEntryStatus = {
  DRAFT: 'draft',
  POSTED: 'posted',
  VOID: 'void',
  
  canEdit(status) {
    return status === this.DRAFT
  },
  
  canPost(status) {
    return status === this.DRAFT
  },
  
  canVoid(status) {
    return status === this.POSTED
  }
}

// Production Order Status (app/Modules/Manufacturing/Enums/ProductionOrderStatus.php)
export const ProductionOrderStatus = {
  DRAFT: 'draft',
  RELEASED: 'released',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
  
  canTransitionTo(currentStatus, newStatus) {
    const transitions = {
      draft: ['released', 'cancelled'],
      released: ['in_progress', 'cancelled'],
      in_progress: ['completed', 'cancelled'],
      completed: [],
      cancelled: []
    }
    
    return transitions[currentStatus]?.includes(newStatus) || false
  }
}

// Work Order Status (app/Modules/Manufacturing/Enums/WorkOrderStatus.php)
export const WorkOrderStatus = {
  PENDING: 'pending',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
  
  canTransitionTo(currentStatus, newStatus) {
    const transitions = {
      pending: ['in_progress', 'cancelled'],
      in_progress: ['completed', 'cancelled'],
      completed: [],
      cancelled: []
    }
    
    return transitions[currentStatus]?.includes(newStatus) || false
  }
}

/**
 * Get human-readable label for enum value
 * @param {string} enumType - The enum type (e.g., 'ProductType', 'InvoiceStatus')
 * @param {string} value - The enum value
 * @returns {string} - Human-readable label
 */
export function getEnumLabel(enumType, value) {
  if (!value) return ''
  
  // Convert snake_case or camelCase to Title Case
  return value
    .replace(/_/g, ' ')
    .replace(/([A-Z])/g, ' $1')
    .trim()
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ')
}

/**
 * Get all values for an enum type
 * @param {Object} enumObj - The enum object
 * @returns {Array} - Array of enum values
 */
export function getEnumValues(enumObj) {
  return Object.values(enumObj).filter(v => typeof v === 'string')
}

/**
 * Get enum options for select/dropdown
 * @param {Object} enumObj - The enum object
 * @param {string} enumType - The enum type name for labels
 * @returns {Array} - Array of {value, label} objects
 */
export function getEnumOptions(enumObj, enumType) {
  return getEnumValues(enumObj).map(value => ({
    value,
    label: getEnumLabel(enumType, value)
  }))
}
