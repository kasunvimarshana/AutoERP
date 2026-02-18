import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/auth/Login.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: () => import('../views/Unauthorized.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: () => import('../layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard'
      },
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue')
      },
      // IAM Routes
      {
        path: 'iam/users',
        name: 'Users',
        component: () => import('../modules/iam/views/UserList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'users.view',
          module: 'iam',
          title: 'Users Management'
        }
      },
      {
        path: 'iam/roles',
        name: 'Roles',
        component: () => import('../modules/iam/views/RoleList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'roles.view',
          module: 'iam',
          title: 'Roles Management'
        }
      },
      {
        path: 'iam/permissions',
        name: 'Permissions',
        component: () => import('../modules/iam/views/PermissionList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'permissions.view',
          module: 'iam',
          title: 'Permissions Management'
        }
      },
      // Inventory Routes
      {
        path: 'inventory/products',
        name: 'Products',
        component: () => import('../modules/inventory/views/ProductList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'products.view',
          module: 'inventory',
          title: 'Products'
        }
      },
      {
        path: 'inventory/stock-ledgers',
        name: 'StockLedgers',
        component: () => import('../modules/inventory/views/StockLedgerList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'stock-ledgers.view',
          module: 'inventory',
          title: 'Stock Ledgers'
        }
      },
      {
        path: 'inventory/stock-movements',
        name: 'StockMovements',
        component: () => import('../modules/inventory/views/StockMovementList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'stock-movements.view',
          module: 'inventory',
          title: 'Stock Movements'
        }
      },
      {
        path: 'inventory/warehouses',
        name: 'Warehouses',
        component: () => import('../modules/inventory/views/WarehouseList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'warehouses.view',
          module: 'inventory',
          title: 'Warehouses'
        }
      },
      // CRM Routes
      {
        path: 'crm/customers',
        name: 'Customers',
        component: () => import('../modules/crm/views/CustomerList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'customers.view',
          module: 'crm',
          title: 'Customers'
        }
      },
      {
        path: 'crm/contacts',
        name: 'Contacts',
        component: () => import('../modules/crm/views/ContactList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'contacts.view',
          module: 'crm',
          title: 'Contacts'
        }
      },
      // POS Routes
      {
        path: 'pos/quotations',
        name: 'Quotations',
        component: () => import('../modules/pos/views/QuotationList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'quotations.view',
          module: 'pos',
          title: 'Quotations'
        }
      },
      {
        path: 'pos/sales-orders',
        name: 'SalesOrders',
        component: () => import('../modules/pos/views/SalesOrderList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'sales-orders.view',
          module: 'pos',
          title: 'Sales Orders'
        }
      },
      {
        path: 'pos/invoices',
        name: 'Invoices',
        component: () => import('../modules/pos/views/InvoiceList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'invoices.view',
          module: 'pos',
          title: 'Invoices'
        }
      },
      {
        path: 'pos/payments',
        name: 'Payments',
        component: () => import('../modules/pos/views/PaymentList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'payments.view',
          module: 'pos',
          title: 'Payments'
        }
      },
      // Procurement Routes
      {
        path: 'procurement/suppliers',
        name: 'Suppliers',
        component: () => import('../modules/procurement/views/SupplierList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'suppliers.view',
          module: 'procurement',
          title: 'Suppliers'
        }
      },
      {
        path: 'procurement/purchase-orders',
        name: 'PurchaseOrders',
        component: () => import('../modules/procurement/views/PurchaseOrderList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'purchase-orders.view',
          module: 'procurement',
          title: 'Purchase Orders'
        }
      },
      {
        path: 'procurement/grns',
        name: 'GRNs',
        component: () => import('../modules/procurement/views/GRNList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'grns.view',
          module: 'procurement',
          title: 'Goods Receipt Notes'
        }
      },
      // Manufacturing Routes
      {
        path: 'manufacturing/boms',
        name: 'BOMs',
        component: () => import('../modules/manufacturing/views/BOMList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'boms.view',
          module: 'manufacturing',
          title: 'Bills of Materials'
        }
      },
      {
        path: 'manufacturing/production-orders',
        name: 'ProductionOrders',
        component: () => import('../modules/manufacturing/views/ProductionOrderList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'production-orders.view',
          module: 'manufacturing',
          title: 'Production Orders'
        }
      },
      {
        path: 'manufacturing/work-orders',
        name: 'WorkOrders',
        component: () => import('../modules/manufacturing/views/WorkOrderList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'work-orders.view',
          module: 'manufacturing',
          title: 'Work Orders'
        }
      },
      // Finance Routes
      {
        path: 'finance/accounts',
        name: 'Accounts',
        component: () => import('../modules/finance/views/AccountList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'accounts.view',
          module: 'finance',
          title: 'Chart of Accounts'
        }
      },
      {
        path: 'finance/journal-entries',
        name: 'JournalEntries',
        component: () => import('../modules/finance/views/JournalEntryList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'journal-entries.view',
          module: 'finance',
          title: 'Journal Entries'
        }
      },
      {
        path: 'finance/reports',
        name: 'FinanceReports',
        component: () => import('../modules/finance/views/ReportList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'reports.view',
          module: 'finance',
          title: 'Financial Reports'
        }
      },
      // Reporting Routes
      {
        path: 'reporting/dashboards',
        name: 'ReportingDashboards',
        component: () => import('../modules/reporting/views/DashboardList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'dashboards.view',
          module: 'reporting',
          title: 'Dashboards'
        }
      },
      {
        path: 'reporting/analytics',
        name: 'Analytics',
        component: () => import('../modules/reporting/views/AnalyticsList.vue'),
        meta: { 
          requiresAuth: true,
          permission: 'analytics.view',
          module: 'reporting',
          title: 'Analytics'
        }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Check authentication requirement
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'Login', query: { redirect: to.fullPath } })
  }
  
  // Redirect authenticated users away from login
  if (to.name === 'Login' && authStore.isAuthenticated) {
    return next({ name: 'Dashboard' })
  }
  
  // Check permission requirement for authenticated routes
  if (to.meta.requiresAuth && to.meta.permission) {
    // Check if user has the required permission
    const hasPermission = authStore.hasPermission(to.meta.permission)
    
    if (!hasPermission) {
      // User doesn't have permission - redirect to unauthorized page
      console.warn(`Access denied: User lacks permission '${to.meta.permission}' for route '${to.name}'`)
      return next({ 
        name: 'Unauthorized', 
        query: { 
          redirect: to.fullPath,
          permission: to.meta.permission 
        } 
      })
    }
  }
  
  // Set document title based on route meta
  if (to.meta.title) {
    document.title = `${to.meta.title} - AutoERP`
  } else {
    document.title = 'AutoERP'
  }
  
  next()
})

export default router
