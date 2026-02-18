import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';

// Lazy load views
const Login = () => import('@/views/auth/Login.vue');
const Register = () => import('@/views/auth/Register.vue');
const ForgotPassword = () => import('@/views/auth/ForgotPassword.vue');
const ResetPassword = () => import('@/views/auth/ResetPassword.vue');

const DashboardLayout = () => import('@/components/layout/DashboardLayout.vue');
const Dashboard = () => import('@/views/dashboard/Dashboard.vue');

const ModuleList = () => import('@/views/modules/ModuleList.vue');
const ModuleForm = () => import('@/views/modules/ModuleForm.vue');
const ModuleDetail = () => import('@/views/modules/ModuleDetail.vue');

// Inventory views
const ProductList = () => import('@/views/inventory/ProductList.vue');
const ProductForm = () => import('@/views/inventory/ProductForm.vue');
const ProductDetail = () => import('@/views/inventory/ProductDetail.vue');
const WarehouseList = () => import('@/views/inventory/WarehouseList.vue');
const WarehouseDetail = () => import('@/views/inventory/WarehouseDetail.vue');
const WarehouseForm = () => import('@/views/inventory/WarehouseForm.vue');
const StockMovementList = () => import('@/views/inventory/StockMovementList.vue');
const CategoryList = () => import('@/views/inventory/CategoryList.vue');

// Module overview pages
const AccountingOverview = () => import('@/views/accounting/AccountingOverview.vue');
const SalesOverview = () => import('@/views/sales/SalesOverview.vue');
const PurchasingOverview = () => import('@/views/purchasing/PurchasingOverview.vue');
const IAMOverview = () => import('@/views/iam/IAMOverview.vue');

// Accounting views
const AccountList = () => import('@/views/accounting/AccountList.vue');
const AccountListView = () => import('@/views/accounting/AccountListView.vue');
const AccountDetail = () => import('@/views/accounting/AccountDetail.vue');
const AccountForm = () => import('@/views/accounting/AccountForm.vue');
const JournalEntryList = () => import('@/views/accounting/JournalEntryList.vue');
const JournalEntryListView = () => import('@/views/accounting/JournalEntryListView.vue');
const JournalEntryDetail = () => import('@/views/accounting/JournalEntryDetail.vue');
const JournalEntryForm = () => import('@/views/accounting/JournalEntryForm.vue');
const InvoiceList = () => import('@/views/accounting/InvoiceList.vue');
const InvoiceListView = () => import('@/views/accounting/InvoiceListView.vue');
const InvoiceDetail = () => import('@/views/accounting/InvoiceDetail.vue');
const InvoiceForm = () => import('@/views/accounting/InvoiceForm.vue');
const PaymentList = () => import('@/views/accounting/PaymentList.vue');
const PaymentListView = () => import('@/views/accounting/PaymentListView.vue');
const PaymentDetail = () => import('@/views/accounting/PaymentDetail.vue');
const PaymentForm = () => import('@/views/accounting/PaymentForm.vue');

// Sales views
const CustomerList = () => import('@/views/sales/CustomerList.vue');
const QuotationList = () => import('@/views/sales/QuotationList.vue');
const SalesOrderList = () => import('@/views/sales/SalesOrderList.vue');

// Purchasing views
const SupplierList = () => import('@/views/purchasing/SupplierList.vue');
const PurchaseOrderList = () => import('@/views/purchasing/PurchaseOrderList.vue');
const GoodsReceiptList = () => import('@/views/purchasing/GoodsReceiptList.vue');

// IAM views
const UserList = () => import('@/views/iam/UserList.vue');
const RoleList = () => import('@/views/iam/RoleList.vue');
const PermissionList = () => import('@/views/iam/PermissionList.vue');

// Core views
const TenantList = () => import('@/views/core/TenantList.vue');
const AuditLogList = () => import('@/views/core/AuditLogList.vue');
const NotificationPreferenceList = () => import('@/views/core/NotificationPreferenceList.vue');

// App views
const Settings = () => import('@/views/settings/Settings.vue');
const Profile = () => import('@/views/profile/Profile.vue');
const Reports = () => import('@/views/reports/Reports.vue');
const NotificationList = () => import('@/views/notifications/NotificationList.vue');

const NotFound = () => import('@/views/NotFound.vue');
const Unauthorized = () => import('@/views/Unauthorized.vue');

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { 
      requiresAuth: false,
      layout: 'auth',
    },
  },
  {
    path: '/register',
    name: 'register',
    component: Register,
    meta: { 
      requiresAuth: false,
      layout: 'auth',
    },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: ForgotPassword,
    meta: { 
      requiresAuth: false,
      layout: 'auth',
    },
  },
  {
    path: '/reset-password/:token',
    name: 'reset-password',
    component: ResetPassword,
    meta: { 
      requiresAuth: false,
      layout: 'auth',
    },
  },
  {
    path: '/',
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: Dashboard,
        meta: {
          title: 'Dashboard',
          permissions: [],
        },
      },
      {
        path: 'modules/:module',
        name: 'module-list',
        component: ModuleList,
        meta: {
          title: 'Module List',
          permissions: [],
        },
      },
      {
        path: 'modules/:module/create',
        name: 'module-create',
        component: ModuleForm,
        meta: {
          title: 'Create Record',
          permissions: [],
        },
      },
      {
        path: 'modules/:module/:id',
        name: 'module-detail',
        component: ModuleDetail,
        meta: {
          title: 'Record Detail',
          permissions: [],
        },
      },
      {
        path: 'modules/:module/:id/edit',
        name: 'module-edit',
        component: ModuleForm,
        meta: {
          title: 'Edit Record',
          permissions: [],
        },
      },
      // Inventory routes
      {
        path: 'inventory/products',
        name: 'inventory-products',
        component: ProductList,
        meta: {
          title: 'Products',
          permissions: ['inventory.products.view'],
        },
      },
      {
        path: 'inventory/products/create',
        name: 'inventory-product-create',
        component: ProductForm,
        meta: {
          title: 'Create Product',
          permissions: ['inventory.products.create'],
        },
      },
      {
        path: 'inventory/products/:id',
        name: 'inventory-product-detail',
        component: ProductDetail,
        meta: {
          title: 'Product Details',
          permissions: ['inventory.products.view'],
        },
      },
      {
        path: 'inventory/products/:id/edit',
        name: 'inventory-product-edit',
        component: ProductForm,
        meta: {
          title: 'Edit Product',
          permissions: ['inventory.products.update'],
        },
      },
      {
        path: 'inventory/warehouses',
        name: 'inventory-warehouses',
        component: WarehouseList,
        meta: {
          title: 'Warehouses',
          permissions: ['inventory.warehouses.view'],
        },
      },
      {
        path: 'inventory/warehouses/create',
        name: 'inventory-warehouse-create',
        component: WarehouseForm,
        meta: {
          title: 'Create Warehouse',
          permissions: ['inventory.warehouses.create'],
        },
      },
      {
        path: 'inventory/warehouses/:id',
        name: 'inventory-warehouse-detail',
        component: WarehouseDetail,
        meta: {
          title: 'Warehouse Details',
          permissions: ['inventory.warehouses.view'],
        },
      },
      {
        path: 'inventory/warehouses/:id/edit',
        name: 'inventory-warehouse-edit',
        component: WarehouseForm,
        meta: {
          title: 'Edit Warehouse',
          permissions: ['inventory.warehouses.update'],
        },
      },
      // Inventory - Stock Movements
      {
        path: 'inventory/stock-movements',
        name: 'inventory-stock-movements',
        component: StockMovementList,
        meta: {
          title: 'Stock Movements',
          permissions: ['inventory.stock.view'],
        },
      },
      // Inventory - Categories
      {
        path: 'inventory/categories',
        name: 'inventory-categories',
        component: CategoryList,
        meta: {
          title: 'Categories',
          permissions: ['inventory.categories.view'],
        },
      },
      {
        path: 'inventory/categories/create',
        name: 'inventory-category-create',
        component: CategoryList,
        meta: {
          title: 'Create Category',
          permissions: ['inventory.categories.create'],
        },
      },
      {
        path: 'inventory/categories/:id',
        name: 'inventory-category-detail',
        component: CategoryList,
        meta: {
          title: 'Category Details',
          permissions: ['inventory.categories.view'],
        },
      },
      {
        path: 'inventory/categories/:id/edit',
        name: 'inventory-category-edit',
        component: CategoryList,
        meta: {
          title: 'Edit Category',
          permissions: ['inventory.categories.update'],
        },
      },
      // Accounting Module
      {
        path: 'accounting',
        name: 'accounting-overview',
        component: AccountingOverview,
        meta: {
          title: 'Accounting',
          permissions: [],
        },
      },
      // Accounting - Accounts
      {
        path: 'accounting/accounts',
        name: 'accounting-accounts',
        component: AccountListView,
        meta: {
          title: 'Chart of Accounts',
          permissions: ['accounting.accounts.view'],
        },
      },
      {
        path: 'accounting/accounts/create',
        name: 'accounting-account-create',
        component: AccountForm,
        meta: {
          title: 'Create Account',
          permissions: ['accounting.accounts.create'],
        },
      },
      {
        path: 'accounting/accounts/:id',
        name: 'accounting-account-detail',
        component: AccountDetail,
        meta: {
          title: 'Account Details',
          permissions: ['accounting.accounts.view'],
        },
      },
      {
        path: 'accounting/accounts/:id/edit',
        name: 'accounting-account-edit',
        component: AccountForm,
        meta: {
          title: 'Edit Account',
          permissions: ['accounting.accounts.update'],
        },
      },
      // Accounting - Journal Entries
      {
        path: 'accounting/journal-entries',
        name: 'accounting-journal-entries',
        component: JournalEntryListView,
        meta: {
          title: 'Journal Entries',
          permissions: ['accounting.journal_entries.view'],
        },
      },
      {
        path: 'accounting/journal-entries/create',
        name: 'accounting-journal-entry-create',
        component: JournalEntryForm,
        meta: {
          title: 'Create Journal Entry',
          permissions: ['accounting.journal_entries.create'],
        },
      },
      {
        path: 'accounting/journal-entries/:id',
        name: 'accounting-journal-entry-detail',
        component: JournalEntryDetail,
        meta: {
          title: 'Journal Entry Details',
          permissions: ['accounting.journal_entries.view'],
        },
      },
      {
        path: 'accounting/journal-entries/:id/edit',
        name: 'accounting-journal-entry-edit',
        component: JournalEntryForm,
        meta: {
          title: 'Edit Journal Entry',
          permissions: ['accounting.journal_entries.update'],
        },
      },
      // Accounting - Invoices
      {
        path: 'accounting/invoices',
        name: 'accounting-invoices',
        component: InvoiceListView,
        meta: {
          title: 'Invoices',
          permissions: ['accounting.invoices.view'],
        },
      },
      {
        path: 'accounting/invoices/create',
        name: 'accounting-invoice-create',
        component: InvoiceForm,
        meta: {
          title: 'Create Invoice',
          permissions: ['accounting.invoices.create'],
        },
      },
      {
        path: 'accounting/invoices/:id',
        name: 'accounting-invoice-detail',
        component: InvoiceDetail,
        meta: {
          title: 'Invoice Details',
          permissions: ['accounting.invoices.view'],
        },
      },
      {
        path: 'accounting/invoices/:id/edit',
        name: 'accounting-invoice-edit',
        component: InvoiceForm,
        meta: {
          title: 'Edit Invoice',
          permissions: ['accounting.invoices.update'],
        },
      },
      // Accounting - Payments
      {
        path: 'accounting/payments',
        name: 'accounting-payments',
        component: PaymentListView,
        meta: {
          title: 'Payments',
          permissions: ['accounting.payments.view'],
        },
      },
      {
        path: 'accounting/payments/create',
        name: 'accounting-payment-create',
        component: PaymentForm,
        meta: {
          title: 'Create Payment',
          permissions: ['accounting.payments.create'],
        },
      },
      {
        path: 'accounting/payments/:id',
        name: 'accounting-payment-detail',
        component: PaymentDetail,
        meta: {
          title: 'Payment Details',
          permissions: ['accounting.payments.view'],
        },
      },
      {
        path: 'accounting/payments/:id/edit',
        name: 'accounting-payment-edit',
        component: PaymentForm,
        meta: {
          title: 'Edit Payment',
          permissions: ['accounting.payments.update'],
        },
      },
      // Sales Module
      {
        path: 'sales',
        name: 'sales-overview',
        component: SalesOverview,
        meta: {
          title: 'Sales',
          permissions: [],
        },
      },
      // Sales - Customers
      {
        path: 'sales/customers',
        name: 'sales-customers',
        component: CustomerList,
        meta: {
          title: 'Customers',
          permissions: ['sales.customers.view'],
        },
      },
      {
        path: 'sales/customers/create',
        name: 'sales-customer-create',
        component: CustomerList,
        meta: {
          title: 'Create Customer',
          permissions: ['sales.customers.create'],
        },
      },
      {
        path: 'sales/customers/:id',
        name: 'sales-customer-detail',
        component: CustomerList,
        meta: {
          title: 'Customer Details',
          permissions: ['sales.customers.view'],
        },
      },
      {
        path: 'sales/customers/:id/edit',
        name: 'sales-customer-edit',
        component: CustomerList,
        meta: {
          title: 'Edit Customer',
          permissions: ['sales.customers.update'],
        },
      },
      // Sales - Quotations
      {
        path: 'sales/quotations',
        name: 'sales-quotations',
        component: QuotationList,
        meta: {
          title: 'Quotations',
          permissions: ['sales.quotations.view'],
        },
      },
      {
        path: 'sales/quotations/create',
        name: 'sales-quotation-create',
        component: QuotationList,
        meta: {
          title: 'Create Quotation',
          permissions: ['sales.quotations.create'],
        },
      },
      {
        path: 'sales/quotations/:id',
        name: 'sales-quotation-detail',
        component: QuotationList,
        meta: {
          title: 'Quotation Details',
          permissions: ['sales.quotations.view'],
        },
      },
      {
        path: 'sales/quotations/:id/edit',
        name: 'sales-quotation-edit',
        component: QuotationList,
        meta: {
          title: 'Edit Quotation',
          permissions: ['sales.quotations.update'],
        },
      },
      // Sales - Sales Orders
      {
        path: 'sales/orders',
        name: 'sales-orders',
        component: SalesOrderList,
        meta: {
          title: 'Sales Orders',
          permissions: ['sales.orders.view'],
        },
      },
      {
        path: 'sales/orders/create',
        name: 'sales-order-create',
        component: SalesOrderList,
        meta: {
          title: 'Create Sales Order',
          permissions: ['sales.orders.create'],
        },
      },
      {
        path: 'sales/orders/:id',
        name: 'sales-order-detail',
        component: SalesOrderList,
        meta: {
          title: 'Sales Order Details',
          permissions: ['sales.orders.view'],
        },
      },
      {
        path: 'sales/orders/:id/edit',
        name: 'sales-order-edit',
        component: SalesOrderList,
        meta: {
          title: 'Edit Sales Order',
          permissions: ['sales.orders.update'],
        },
      },
      // Purchasing Module
      {
        path: 'purchasing',
        name: 'purchasing-overview',
        component: PurchasingOverview,
        meta: {
          title: 'Purchasing',
          permissions: [],
        },
      },
      // Purchasing - Suppliers
      {
        path: 'purchasing/suppliers',
        name: 'purchasing-suppliers',
        component: SupplierList,
        meta: {
          title: 'Suppliers',
          permissions: ['purchasing.suppliers.view'],
        },
      },
      {
        path: 'purchasing/suppliers/create',
        name: 'purchasing-supplier-create',
        component: SupplierList,
        meta: {
          title: 'Create Supplier',
          permissions: ['purchasing.suppliers.create'],
        },
      },
      {
        path: 'purchasing/suppliers/:id',
        name: 'purchasing-supplier-detail',
        component: SupplierList,
        meta: {
          title: 'Supplier Details',
          permissions: ['purchasing.suppliers.view'],
        },
      },
      {
        path: 'purchasing/suppliers/:id/edit',
        name: 'purchasing-supplier-edit',
        component: SupplierList,
        meta: {
          title: 'Edit Supplier',
          permissions: ['purchasing.suppliers.update'],
        },
      },
      // Purchasing - Purchase Orders
      {
        path: 'purchasing/orders',
        name: 'purchasing-orders',
        component: PurchaseOrderList,
        meta: {
          title: 'Purchase Orders',
          permissions: ['purchasing.purchase_orders.view'],
        },
      },
      {
        path: 'purchasing/orders/create',
        name: 'purchasing-order-create',
        component: PurchaseOrderList,
        meta: {
          title: 'Create Purchase Order',
          permissions: ['purchasing.purchase_orders.create'],
        },
      },
      {
        path: 'purchasing/orders/:id',
        name: 'purchasing-order-detail',
        component: PurchaseOrderList,
        meta: {
          title: 'Purchase Order Details',
          permissions: ['purchasing.purchase_orders.view'],
        },
      },
      {
        path: 'purchasing/orders/:id/edit',
        name: 'purchasing-order-edit',
        component: PurchaseOrderList,
        meta: {
          title: 'Edit Purchase Order',
          permissions: ['purchasing.purchase_orders.update'],
        },
      },
      // Purchasing - Goods Receipts
      {
        path: 'purchasing/goods-receipts',
        name: 'purchasing-goods-receipts',
        component: GoodsReceiptList,
        meta: {
          title: 'Goods Receipts',
          permissions: ['purchasing.goods_receipts.view'],
        },
      },
      {
        path: 'purchasing/goods-receipts/create',
        name: 'purchasing-goods-receipt-create',
        component: GoodsReceiptList,
        meta: {
          title: 'Create Goods Receipt',
          permissions: ['purchasing.goods_receipts.create'],
        },
      },
      {
        path: 'purchasing/goods-receipts/:id',
        name: 'purchasing-goods-receipt-detail',
        component: GoodsReceiptList,
        meta: {
          title: 'Goods Receipt Details',
          permissions: ['purchasing.goods_receipts.view'],
        },
      },
      {
        path: 'purchasing/goods-receipts/:id/edit',
        name: 'purchasing-goods-receipt-edit',
        component: GoodsReceiptList,
        meta: {
          title: 'Edit Goods Receipt',
          permissions: ['purchasing.goods_receipts.update'],
        },
      },
      // IAM Module
      {
        path: 'iam',
        name: 'iam-overview',
        component: IAMOverview,
        meta: {
          title: 'Identity & Access Management',
          permissions: [],
        },
      },
      // IAM - Users
      {
        path: 'iam/users',
        name: 'iam-users',
        component: UserList,
        meta: {
          title: 'Users',
          permissions: ['iam.users.view'],
        },
      },
      {
        path: 'iam/users/create',
        name: 'iam-user-create',
        component: UserList,
        meta: {
          title: 'Create User',
          permissions: ['iam.users.create'],
        },
      },
      {
        path: 'iam/users/:id',
        name: 'iam-user-detail',
        component: UserList,
        meta: {
          title: 'User Details',
          permissions: ['iam.users.view'],
        },
      },
      {
        path: 'iam/users/:id/edit',
        name: 'iam-user-edit',
        component: UserList,
        meta: {
          title: 'Edit User',
          permissions: ['iam.users.update'],
        },
      },
      // IAM - Roles
      {
        path: 'iam/roles',
        name: 'iam-roles',
        component: RoleList,
        meta: {
          title: 'Roles',
          permissions: ['iam.roles.view'],
        },
      },
      {
        path: 'iam/roles/create',
        name: 'iam-role-create',
        component: RoleList,
        meta: {
          title: 'Create Role',
          permissions: ['iam.roles.create'],
        },
      },
      {
        path: 'iam/roles/:id',
        name: 'iam-role-detail',
        component: RoleList,
        meta: {
          title: 'Role Details',
          permissions: ['iam.roles.view'],
        },
      },
      {
        path: 'iam/roles/:id/edit',
        name: 'iam-role-edit',
        component: RoleList,
        meta: {
          title: 'Edit Role',
          permissions: ['iam.roles.update'],
        },
      },
      // IAM - Permissions
      {
        path: 'iam/permissions',
        name: 'iam-permissions',
        component: PermissionList,
        meta: {
          title: 'Permissions',
          permissions: ['iam.permissions.view'],
        },
      },
      {
        path: 'iam/permissions/create',
        name: 'iam-permission-create',
        component: PermissionList,
        meta: {
          title: 'Create Permission',
          permissions: ['iam.permissions.create'],
        },
      },
      {
        path: 'iam/permissions/:id',
        name: 'iam-permission-detail',
        component: PermissionList,
        meta: {
          title: 'Permission Details',
          permissions: ['iam.permissions.view'],
        },
      },
      {
        path: 'iam/permissions/:id/edit',
        name: 'iam-permission-edit',
        component: PermissionList,
        meta: {
          title: 'Edit Permission',
          permissions: ['iam.permissions.update'],
        },
      },
      // Core - Tenants
      {
        path: 'core/tenants',
        name: 'core-tenants',
        component: TenantList,
        meta: {
          title: 'Tenants',
          permissions: ['core.tenants.view'],
        },
      },
      {
        path: 'core/tenants/create',
        name: 'core-tenant-create',
        component: TenantList,
        meta: {
          title: 'Create Tenant',
          permissions: ['core.tenants.create'],
        },
      },
      {
        path: 'core/tenants/:id',
        name: 'core-tenant-detail',
        component: TenantList,
        meta: {
          title: 'Tenant Details',
          permissions: ['core.tenants.view'],
        },
      },
      {
        path: 'core/tenants/:id/edit',
        name: 'core-tenant-edit',
        component: TenantList,
        meta: {
          title: 'Edit Tenant',
          permissions: ['core.tenants.update'],
        },
      },
      // Core - Audit Logs (read-only)
      {
        path: 'core/audit-logs',
        name: 'core-audit-logs',
        component: AuditLogList,
        meta: {
          title: 'Audit Logs',
          permissions: ['core.audit_logs.view'],
        },
      },
      {
        path: 'core/audit-logs/:id',
        name: 'core-audit-log-detail',
        component: AuditLogList,
        meta: {
          title: 'Audit Log Details',
          permissions: ['core.audit_logs.view'],
        },
      },
      // Core - Notification Preferences
      {
        path: 'core/notification-preferences',
        name: 'core-notification-preferences',
        component: NotificationPreferenceList,
        meta: {
          title: 'Notification Preferences',
          permissions: ['core.notifications.view'],
        },
      },
      {
        path: 'core/notification-preferences/create',
        name: 'core-notification-preference-create',
        component: NotificationPreferenceList,
        meta: {
          title: 'Create Notification Preference',
          permissions: ['core.notifications.create'],
        },
      },
      {
        path: 'core/notification-preferences/:id',
        name: 'core-notification-preference-detail',
        component: NotificationPreferenceList,
        meta: {
          title: 'Notification Preference Details',
          permissions: ['core.notifications.view'],
        },
      },
      {
        path: 'core/notification-preferences/:id/edit',
        name: 'core-notification-preference-edit',
        component: NotificationPreferenceList,
        meta: {
          title: 'Edit Notification Preference',
          permissions: ['core.notifications.update'],
        },
      },
      // Profile
      {
        path: 'profile',
        name: 'profile',
        component: Profile,
        meta: {
          title: 'Profile',
          permissions: [],
        },
      },
      // Settings
      {
        path: 'settings',
        name: 'settings',
        component: Settings,
        meta: {
          title: 'Settings',
          permissions: ['settings.view'],
        },
      },
      // Reports
      {
        path: 'reports',
        name: 'reports',
        component: Reports,
        meta: {
          title: 'Reports',
          permissions: ['reports.view'],
        },
      },
      // Notifications
      {
        path: 'notifications',
        name: 'notifications',
        component: NotificationList,
        meta: {
          title: 'Notifications',
          permissions: [],
        },
      },
    ],
  },
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: Unauthorized,
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFound,
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    }
    return { top: 0 };
  },
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const tenantStore = useTenantStore();

  // Check if route requires authentication
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth !== false);

  if (requiresAuth && !authStore.isAuthenticated) {
    // Redirect to login if not authenticated
    next({
      name: 'login',
      query: { redirect: to.fullPath },
    });
    return;
  }

  // Redirect to dashboard if authenticated user tries to access auth pages
  if (!requiresAuth && authStore.isAuthenticated) {
    next({ name: 'dashboard' });
    return;
  }

  // Check tenant subscription status
  if (authStore.isAuthenticated && !tenantStore.isActive) {
    // Allow access to subscription/billing pages
    if (!to.path.includes('/subscription') && !to.path.includes('/billing')) {
      next({ name: 'subscription-expired' });
      return;
    }
  }

  // Check permissions
  const permissions = to.meta.permissions as string[] | undefined;
  if (permissions && permissions.length > 0) {
    const hasPermission = authStore.hasAnyPermission(permissions);
    
    if (!hasPermission) {
      next({ name: 'unauthorized' });
      return;
    }
  }

  next();
});

// After navigation
router.afterEach((to) => {
  // Update page title
  const defaultTitle = 'AutoERP';
  const title = to.meta.title as string | undefined;
  document.title = title ? `${title} - ${defaultTitle}` : defaultTitle;
});

export default router;
