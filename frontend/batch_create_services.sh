#!/bin/bash

# IAM Service
cat > src/services/iamService.js << 'EOF'
import apiClient from './api'

export default {
  // Users
  getUsers(params = {}) {
    return apiClient.get('/iam/users', { params })
  },
  getUser(id) {
    return apiClient.get(`/iam/users/${id}`)
  },
  createUser(data) {
    return apiClient.post('/iam/users', data)
  },
  updateUser(id, data) {
    return apiClient.put(`/iam/users/${id}`, data)
  },
  deleteUser(id) {
    return apiClient.delete(`/iam/users/${id}`)
  },
  
  // Roles
  getRoles(params = {}) {
    return apiClient.get('/iam/roles', { params })
  },
  getRole(id) {
    return apiClient.get(`/iam/roles/${id}`)
  },
  createRole(data) {
    return apiClient.post('/iam/roles', data)
  },
  updateRole(id, data) {
    return apiClient.put(`/iam/roles/${id}`, data)
  },
  deleteRole(id) {
    return apiClient.delete(`/iam/roles/${id}`)
  },
  
  // Permissions
  getPermissions(params = {}) {
    return apiClient.get('/iam/permissions', { params })
  },
  getPermission(id) {
    return apiClient.get(`/iam/permissions/${id}`)
  }
}
EOF

# Inventory Service (extended)
cat > src/services/inventoryService.js << 'EOF'
import apiClient from './api'

export default {
  // Products
  getProducts(params = {}) {
    return apiClient.get('/inventory/products', { params })
  },
  getProduct(id) {
    return apiClient.get(`/inventory/products/${id}`)
  },
  createProduct(data) {
    return apiClient.post('/inventory/products', data)
  },
  updateProduct(id, data) {
    return apiClient.put(`/inventory/products/${id}`, data)
  },
  deleteProduct(id) {
    return apiClient.delete(`/inventory/products/${id}`)
  },
  
  // Stock Ledgers
  getStockLedgers(params = {}) {
    return apiClient.get('/inventory/stock-ledgers', { params })
  },
  getStockLedger(id) {
    return apiClient.get(`/inventory/stock-ledgers/${id}`)
  },
  
  // Stock Movements
  getStockMovements(params = {}) {
    return apiClient.get('/inventory/stock-movements', { params })
  },
  createStockMovement(data) {
    return apiClient.post('/inventory/stock-movements', data)
  },
  
  // Warehouses
  getWarehouses(params = {}) {
    return apiClient.get('/inventory/warehouses', { params })
  },
  getWarehouse(id) {
    return apiClient.get(`/inventory/warehouses/${id}`)
  },
  createWarehouse(data) {
    return apiClient.post('/inventory/warehouses', data)
  },
  updateWarehouse(id, data) {
    return apiClient.put(`/inventory/warehouses/${id}`, data)
  },
  deleteWarehouse(id) {
    return apiClient.delete(`/inventory/warehouses/${id}`)
  }
}
EOF

# CRM Service
cat > src/services/crmService.js << 'EOF'
import apiClient from './api'

export default {
  // Customers
  getCustomers(params = {}) {
    return apiClient.get('/crm/customers', { params })
  },
  getCustomer(id) {
    return apiClient.get(`/crm/customers/${id}`)
  },
  createCustomer(data) {
    return apiClient.post('/crm/customers', data)
  },
  updateCustomer(id, data) {
    return apiClient.put(`/crm/customers/${id}`, data)
  },
  deleteCustomer(id) {
    return apiClient.delete(`/crm/customers/${id}`)
  },
  
  // Contacts
  getContacts(params = {}) {
    return apiClient.get('/crm/contacts', { params })
  },
  getContact(id) {
    return apiClient.get(`/crm/contacts/${id}`)
  },
  createContact(data) {
    return apiClient.post('/crm/contacts', data)
  },
  updateContact(id, data) {
    return apiClient.put(`/crm/contacts/${id}`, data)
  },
  deleteContact(id) {
    return apiClient.delete(`/crm/contacts/${id}`)
  }
}
EOF

# POS Service
cat > src/services/posService.js << 'EOF'
import apiClient from './api'

export default {
  // Quotations
  getQuotations(params = {}) {
    return apiClient.get('/pos/quotations', { params })
  },
  getQuotation(id) {
    return apiClient.get(`/pos/quotations/${id}`)
  },
  createQuotation(data) {
    return apiClient.post('/pos/quotations', data)
  },
  updateQuotation(id, data) {
    return apiClient.put(`/pos/quotations/${id}`, data)
  },
  deleteQuotation(id) {
    return apiClient.delete(`/pos/quotations/${id}`)
  },
  
  // Sales Orders
  getSalesOrders(params = {}) {
    return apiClient.get('/pos/sales-orders', { params })
  },
  getSalesOrder(id) {
    return apiClient.get(`/pos/sales-orders/${id}`)
  },
  createSalesOrder(data) {
    return apiClient.post('/pos/sales-orders', data)
  },
  updateSalesOrder(id, data) {
    return apiClient.put(`/pos/sales-orders/${id}`, data)
  },
  
  // Invoices
  getInvoices(params = {}) {
    return apiClient.get('/pos/invoices', { params })
  },
  getInvoice(id) {
    return apiClient.get(`/pos/invoices/${id}`)
  },
  createInvoice(data) {
    return apiClient.post('/pos/invoices', data)
  },
  
  // Payments
  getPayments(params = {}) {
    return apiClient.get('/pos/payments', { params })
  },
  createPayment(data) {
    return apiClient.post('/pos/payments', data)
  }
}
EOF

# Procurement Service
cat > src/services/procurementService.js << 'EOF'
import apiClient from './api'

export default {
  // Suppliers
  getSuppliers(params = {}) {
    return apiClient.get('/procurement/suppliers', { params })
  },
  getSupplier(id) {
    return apiClient.get(`/procurement/suppliers/${id}`)
  },
  createSupplier(data) {
    return apiClient.post('/procurement/suppliers', data)
  },
  updateSupplier(id, data) {
    return apiClient.put(`/procurement/suppliers/${id}`, data)
  },
  deleteSupplier(id) {
    return apiClient.delete(`/procurement/suppliers/${id}`)
  },
  
  // Purchase Orders
  getPurchaseOrders(params = {}) {
    return apiClient.get('/procurement/purchase-orders', { params })
  },
  getPurchaseOrder(id) {
    return apiClient.get(`/procurement/purchase-orders/${id}`)
  },
  createPurchaseOrder(data) {
    return apiClient.post('/procurement/purchase-orders', data)
  },
  updatePurchaseOrder(id, data) {
    return apiClient.put(`/procurement/purchase-orders/${id}`, data)
  },
  
  // GRNs
  getGRNs(params = {}) {
    return apiClient.get('/procurement/grns', { params })
  },
  getGRN(id) {
    return apiClient.get(`/procurement/grns/${id}`)
  },
  createGRN(data) {
    return apiClient.post('/procurement/grns', data)
  }
}
EOF

# Manufacturing Service
cat > src/services/manufacturingService.js << 'EOF'
import apiClient from './api'

export default {
  // BOMs
  getBOMs(params = {}) {
    return apiClient.get('/manufacturing/boms', { params })
  },
  getBOM(id) {
    return apiClient.get(`/manufacturing/boms/${id}`)
  },
  createBOM(data) {
    return apiClient.post('/manufacturing/boms', data)
  },
  updateBOM(id, data) {
    return apiClient.put(`/manufacturing/boms/${id}`, data)
  },
  deleteBOM(id) {
    return apiClient.delete(`/manufacturing/boms/${id}`)
  },
  
  // Production Orders
  getProductionOrders(params = {}) {
    return apiClient.get('/manufacturing/production-orders', { params })
  },
  getProductionOrder(id) {
    return apiClient.get(`/manufacturing/production-orders/${id}`)
  },
  createProductionOrder(data) {
    return apiClient.post('/manufacturing/production-orders', data)
  },
  updateProductionOrder(id, data) {
    return apiClient.put(`/manufacturing/production-orders/${id}`, data)
  },
  
  // Work Orders
  getWorkOrders(params = {}) {
    return apiClient.get('/manufacturing/work-orders', { params })
  },
  getWorkOrder(id) {
    return apiClient.get(`/manufacturing/work-orders/${id}`)
  },
  createWorkOrder(data) {
    return apiClient.post('/manufacturing/work-orders', data)
  },
  updateWorkOrder(id, data) {
    return apiClient.put(`/manufacturing/work-orders/${id}`, data)
  }
}
EOF

# Finance Service
cat > src/services/financeService.js << 'EOF'
import apiClient from './api'

export default {
  // Accounts
  getAccounts(params = {}) {
    return apiClient.get('/finance/accounts', { params })
  },
  getAccount(id) {
    return apiClient.get(`/finance/accounts/${id}`)
  },
  createAccount(data) {
    return apiClient.post('/finance/accounts', data)
  },
  updateAccount(id, data) {
    return apiClient.put(`/finance/accounts/${id}`, data)
  },
  
  // Journal Entries
  getJournalEntries(params = {}) {
    return apiClient.get('/finance/journal-entries', { params })
  },
  getJournalEntry(id) {
    return apiClient.get(`/finance/journal-entries/${id}`)
  },
  createJournalEntry(data) {
    return apiClient.post('/finance/journal-entries', data)
  },
  
  // Reports
  getFinancialReports(params = {}) {
    return apiClient.get('/finance/reports', { params })
  },
  getBalanceSheet(params = {}) {
    return apiClient.get('/finance/reports/balance-sheet', { params })
  },
  getIncomeStatement(params = {}) {
    return apiClient.get('/finance/reports/income-statement', { params })
  }
}
EOF

# Reporting Service
cat > src/services/reportingService.js << 'EOF'
import apiClient from './api'

export default {
  // Dashboards
  getDashboards(params = {}) {
    return apiClient.get('/reporting/dashboards', { params })
  },
  getDashboard(id) {
    return apiClient.get(`/reporting/dashboards/${id}`)
  },
  getDashboardData(id, params = {}) {
    return apiClient.get(`/reporting/dashboards/${id}/data`, { params })
  },
  
  // Analytics
  getAnalytics(params = {}) {
    return apiClient.get('/reporting/analytics', { params })
  },
  getSalesAnalytics(params = {}) {
    return apiClient.get('/reporting/analytics/sales', { params })
  },
  getInventoryAnalytics(params = {}) {
    return apiClient.get('/reporting/analytics/inventory', { params })
  }
}
EOF

echo "All services created successfully"
