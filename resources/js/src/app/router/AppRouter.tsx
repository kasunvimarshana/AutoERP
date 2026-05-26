import { Route, Routes } from 'react-router-dom';
import { GuestRoute } from './guards/GuestRoute';
import { ProtectedRoute } from './guards/ProtectedRoute';
import { appPageRoutes } from './app-navigation';
import { AppShellLayout } from '../layouts/AppShellLayout';
import { AccessDeniedPage } from '../../features/app/pages/AccessDeniedPage';
import { ModulePlaceholderPage } from '../../features/app/pages/ModulePlaceholderPage';
import { NotFoundPage } from '../../features/app/pages/NotFoundPage';
import { AuditLogActivityPage } from '../../features/audit/pages/AuditLogActivityPage';
import { ProfilePage } from '../../features/access/pages/ProfilePage';
import { PermissionsPage } from '../../features/access/pages/PermissionsPage';
import { RoleCreatePage } from '../../features/access/pages/RoleCreatePage';
import { RoleEditPage } from '../../features/access/pages/RoleEditPage';
import { RolesListPage } from '../../features/access/pages/RolesListPage';
import { UserCreatePage } from '../../features/access/pages/UserCreatePage';
import { UserDetailPage } from '../../features/access/pages/UserDetailPage';
import { UserEditPage } from '../../features/access/pages/UserEditPage';
import { UsersListPage } from '../../features/access/pages/UsersListPage';
import { DashboardPage } from '../../features/dashboard/pages/DashboardPage';
import { CustomerCreatePage } from '../../features/customers/pages/CustomerCreatePage';
import { CustomerDetailsPage } from '../../features/customers/pages/CustomerDetailsPage';
import { CustomerEditPage } from '../../features/customers/pages/CustomerEditPage';
import { CustomerListPage } from '../../features/customers/pages/CustomerListPage';
import { EmployeeCreatePage } from '../../features/employees/pages/EmployeeCreatePage';
import { EmployeeDetailPage } from '../../features/employees/pages/EmployeeDetailPage';
import { EmployeeEditPage } from '../../features/employees/pages/EmployeeEditPage';
import { EmployeeListPage } from '../../features/employees/pages/EmployeeListPage';
import { FinanceAccountsPage } from '../../features/finance/pages/FinanceAccountsPage';
import { FinanceJournalEntriesPage } from '../../features/finance/pages/FinanceJournalEntriesPage';
import { FinancePaymentsPage } from '../../features/finance/pages/FinancePaymentsPage';
import { FinanceReportsPage } from '../../features/finance/pages/FinanceReportsPage';
import { LoginPage } from '../../features/auth/pages/LoginPage';
import { OrganizationUnitAssignmentsPage } from '../../features/organization/pages/OrganizationUnitAssignmentsPage';
import { OrganizationUnitTypesPage } from '../../features/organization/pages/OrganizationUnitTypesPage';
import { OrganizationUnitsPage } from '../../features/organization/pages/OrganizationUnitsPage';
import { CycleCountDetailPage } from '../../features/inventory/pages/CycleCountDetailPage';
import { CycleCountsPage } from '../../features/inventory/pages/CycleCountsPage';
import { InventoryDashboardPage } from '../../features/inventory/pages/InventoryDashboardPage';
import { InventoryStockLevelsPage } from '../../features/inventory/pages/InventoryStockLevelsPage';
import { InventoryStockMovementsPage } from '../../features/inventory/pages/InventoryStockMovementsPage';
import { StockReservationsPage } from '../../features/inventory/pages/StockReservationsPage';
import { TransferOrderDetailPage } from '../../features/inventory/pages/TransferOrderDetailPage';
import { TransferOrdersPage } from '../../features/inventory/pages/TransferOrdersPage';
import { ValuationConfigsPage } from '../../features/inventory/pages/ValuationConfigsPage';
import { JobCardCreatePage } from '../../features/jobCards/pages/JobCardCreatePage';
import { JobCardDetailPage } from '../../features/jobCards/pages/JobCardDetailPage';
import { JobCardListPage } from '../../features/jobCards/pages/JobCardListPage';
import { ProductBrandCreatePage } from '../../features/products/pages/ProductBrandCreatePage';
import { ProductBrandEditPage } from '../../features/products/pages/ProductBrandEditPage';
import { ProductBrandListPage } from '../../features/products/pages/ProductBrandListPage';
import { ProductCategoryCreatePage } from '../../features/products/pages/ProductCategoryCreatePage';
import { ProductCategoryEditPage } from '../../features/products/pages/ProductCategoryEditPage';
import { ProductCategoryListPage } from '../../features/products/pages/ProductCategoryListPage';
import { ProductCreatePage } from '../../features/products/pages/ProductCreatePage';
import { ProductDetailsPage } from '../../features/products/pages/ProductDetailsPage';
import { ProductEditPage } from '../../features/products/pages/ProductEditPage';
import { ProductListPage } from '../../features/products/pages/ProductListPage';
import { UnitOfMeasureCreatePage } from '../../features/products/pages/UnitOfMeasureCreatePage';
import { UnitOfMeasureEditPage } from '../../features/products/pages/UnitOfMeasureEditPage';
import { UnitOfMeasureListPage } from '../../features/products/pages/UnitOfMeasureListPage';
import { UomConversionPage } from '../../features/products/pages/UomConversionPage';
import { SupplierCreatePage } from '../../features/suppliers/pages/SupplierCreatePage';
import { SupplierDetailsPage } from '../../features/suppliers/pages/SupplierDetailsPage';
import { SupplierEditPage } from '../../features/suppliers/pages/SupplierEditPage';
import { SupplierListPage } from '../../features/suppliers/pages/SupplierListPage';
import { GrnDetailPage } from '../../features/purchase/pages/GrnDetailPage';
import { GrnCreatePage } from '../../features/purchase/pages/GrnCreatePage';
import { GrnEditPage } from '../../features/purchase/pages/GrnEditPage';
import { GrnListPage } from '../../features/purchase/pages/GrnListPage';
import { PurchaseInvoiceDetailPage } from '../../features/purchase/pages/PurchaseInvoiceDetailPage';
import { PurchaseInvoiceCreatePage } from '../../features/purchase/pages/PurchaseInvoiceCreatePage';
import { PurchaseInvoicesListPage } from '../../features/purchase/pages/PurchaseInvoicesListPage';
import { PurchaseOrderCreatePage } from '../../features/purchase/pages/PurchaseOrderCreatePage';
import { PurchaseOrderDetailPage } from '../../features/purchase/pages/PurchaseOrderDetailPage';
import { PurchaseOrderEditPage } from '../../features/purchase/pages/PurchaseOrderEditPage';
import { PurchaseOrdersListPage } from '../../features/purchase/pages/PurchaseOrdersListPage';
import { PurchasePaymentCreatePage } from '../../features/purchase/pages/PurchasePaymentCreatePage';
import { PurchaseReturnDetailPage } from '../../features/purchase/pages/PurchaseReturnDetailPage';
import { PurchaseReturnCreatePage } from '../../features/purchase/pages/PurchaseReturnCreatePage';
import { PurchaseReturnsListPage } from '../../features/purchase/pages/PurchaseReturnsListPage';
import { CustomerPricingPage } from '../../features/pricing/pages/CustomerPricingPage';
import { PriceListsPage } from '../../features/pricing/pages/PriceListsPage';
import { SupplierPricingPage } from '../../features/pricing/pages/SupplierPricingPage';
import { SalesInvoiceDetailPage } from '../../features/sales/pages/SalesInvoiceDetailPage';
import { SalesInvoicesListPage } from '../../features/sales/pages/SalesInvoicesListPage';
import { SalesOrderCreatePage } from '../../features/sales/pages/SalesOrderCreatePage';
import { SalesOrderDetailPage } from '../../features/sales/pages/SalesOrderDetailPage';
import { SalesOrdersListPage } from '../../features/sales/pages/SalesOrdersListPage';
import { SalesReturnDetailPage } from '../../features/sales/pages/SalesReturnDetailPage';
import { SalesReturnsListPage } from '../../features/sales/pages/SalesReturnsListPage';
import { ShipmentDetailPage } from '../../features/sales/pages/ShipmentDetailPage';
import { ShipmentsListPage } from '../../features/sales/pages/ShipmentsListPage';
import { CompanySettingsPage } from '../../features/settings/pages/CompanySettingsPage';
import { PreferencesSettingsPage } from '../../features/settings/pages/PreferencesSettingsPage';
import { TaxGroupsPage } from '../../features/tax/pages/TaxGroupsPage';
import { TaxRatesPage } from '../../features/tax/pages/TaxRatesPage';
import { TaxRulesPage } from '../../features/tax/pages/TaxRulesPage';
import { TenantDomainsPage } from '../../features/tenant-admin/pages/TenantDomainsPage';
import { TenantPlansPage } from '../../features/tenant-admin/pages/TenantPlansPage';
import { TenantsPage } from '../../features/tenant-admin/pages/TenantsPage';
import { VehicleCreatePage } from '../../features/vehicles/pages/VehicleCreatePage';
import { VehicleDashboardPage } from '../../features/vehicles/pages/VehicleDashboardPage';
import { VehicleDetailPage } from '../../features/vehicles/pages/VehicleDetailPage';
import { VehicleEditPage } from '../../features/vehicles/pages/VehicleEditPage';
import { VehicleListPage } from '../../features/vehicles/pages/VehicleListPage';
import { WarehouseCreatePage } from '../../features/warehouse/pages/WarehouseCreatePage';
import { WarehouseDetailPage } from '../../features/warehouse/pages/WarehouseDetailPage';
import { WarehouseEditPage } from '../../features/warehouse/pages/WarehouseEditPage';
import { WarehouseListPage } from '../../features/warehouse/pages/WarehouseListPage';

function isImplementedAppPath(path: string) {
    return (
        path.startsWith('/products') ||
        path.startsWith('/customers') ||
        path.startsWith('/suppliers') ||
        path.startsWith('/users-access') ||
        path.startsWith('/organization') ||
        path.startsWith('/employees') ||
        path.startsWith('/warehouses') ||
        path.startsWith('/inventory') ||
        path.startsWith('/purchase') ||
        path.startsWith('/sales') ||
        path.startsWith('/tenant-admin') ||
        path.startsWith('/pricing') ||
        path.startsWith('/tax') ||
        path.startsWith('/job-cards') ||
        path.startsWith('/vehicles') ||
        path.startsWith('/finance') ||
        path.startsWith('/audit-logs') ||
        path.startsWith('/settings')
    );
}

export function AppRouter() {
    return (
        <Routes>
            <Route element={<GuestRoute />}>
                <Route path="/login" element={<LoginPage />} />
            </Route>

            <Route element={<ProtectedRoute />}>
                <Route element={<AppShellLayout />}>
                    <Route index element={<DashboardPage />} />
                    <Route path="products" element={<ProductListPage />} />
                    <Route path="products/new" element={<ProductCreatePage />} />
                    <Route path="products/:productId/edit" element={<ProductEditPage />} />
                    <Route path="products/:productId" element={<ProductDetailsPage />} />
                    <Route path="products/brands" element={<ProductBrandListPage />} />
                    <Route path="products/brands/new" element={<ProductBrandCreatePage />} />
                    <Route path="products/brands/:brandId/edit" element={<ProductBrandEditPage />} />
                    <Route path="products/categories" element={<ProductCategoryListPage />} />
                    <Route path="products/categories/new" element={<ProductCategoryCreatePage />} />
                    <Route path="products/categories/:categoryId/edit" element={<ProductCategoryEditPage />} />
                    <Route path="products/units" element={<UnitOfMeasureListPage />} />
                    <Route path="products/units/conversions" element={<UomConversionPage />} />
                    <Route path="products/units/new" element={<UnitOfMeasureCreatePage />} />
                    <Route path="products/units/:unitId/edit" element={<UnitOfMeasureEditPage />} />

                    <Route path="tenant-admin/tenants" element={<TenantsPage />} />
                    <Route path="tenant-admin/plans" element={<TenantPlansPage />} />
                    <Route path="tenant-admin/domains" element={<TenantDomainsPage />} />

                    <Route path="pricing/price-lists" element={<PriceListsPage />} />
                    <Route path="pricing/customer-pricing" element={<CustomerPricingPage />} />
                    <Route path="pricing/supplier-pricing" element={<SupplierPricingPage />} />

                    <Route path="tax/groups" element={<TaxGroupsPage />} />
                    <Route path="tax/rates" element={<TaxRatesPage />} />
                    <Route path="tax/rules" element={<TaxRulesPage />} />

                    <Route path="vehicles/dashboard" element={<VehicleDashboardPage />} />
                    <Route path="vehicles" element={<VehicleListPage />} />
                    <Route path="vehicles/create" element={<VehicleCreatePage />} />
                    <Route path="vehicles/:vehicleId/job-cards" element={<JobCardListPage scopedToVehicle />} />
                    <Route path="vehicles/:vehicleId/job-cards/:jobCardId" element={<JobCardDetailPage />} />
                    <Route path="vehicles/:vehicleId/edit" element={<VehicleEditPage />} />
                    <Route path="vehicles/:vehicleId" element={<VehicleDetailPage />} />

                    <Route path="job-cards" element={<JobCardListPage />} />
                    <Route path="job-cards/create" element={<JobCardCreatePage />} />

                    <Route path="customers" element={<CustomerListPage />} />
                    <Route path="customers/new" element={<CustomerCreatePage />} />
                    <Route path="customers/:customerId/edit" element={<CustomerEditPage />} />
                    <Route path="customers/:customerId" element={<CustomerDetailsPage />} />

                    <Route path="suppliers" element={<SupplierListPage />} />
                    <Route path="suppliers/new" element={<SupplierCreatePage />} />
                    <Route path="suppliers/:supplierId/edit" element={<SupplierEditPage />} />
                    <Route path="suppliers/:supplierId" element={<SupplierDetailsPage />} />

                    <Route path="users-access/users" element={<UsersListPage />} />
                    <Route path="users-access/users/new" element={<UserCreatePage />} />
                    <Route path="users-access/users/:userId/edit" element={<UserEditPage />} />
                    <Route path="users-access/users/:userId" element={<UserDetailPage />} />
                    <Route path="users-access/roles" element={<RolesListPage />} />
                    <Route path="users-access/roles/new" element={<RoleCreatePage />} />
                    <Route path="users-access/roles/:roleId/edit" element={<RoleEditPage />} />
                    <Route path="users-access/permissions" element={<PermissionsPage />} />
                    <Route path="users-access/profile" element={<ProfilePage />} />

                    <Route path="organization/units" element={<OrganizationUnitsPage />} />
                    <Route path="organization/unit-types" element={<OrganizationUnitTypesPage />} />
                    <Route path="organization/assign-users" element={<OrganizationUnitAssignmentsPage />} />

                    <Route path="employees" element={<EmployeeListPage />} />
                    <Route path="employees/new" element={<EmployeeCreatePage />} />
                    <Route path="employees/:employeeId/edit" element={<EmployeeEditPage />} />
                    <Route path="employees/:employeeId" element={<EmployeeDetailPage />} />

                    <Route path="warehouses" element={<WarehouseListPage />} />
                    <Route path="warehouses/new" element={<WarehouseCreatePage />} />
                    <Route path="warehouses/stock-levels" element={<InventoryStockLevelsPage />} />
                    <Route path="warehouses/stock-movements" element={<InventoryStockMovementsPage />} />
                    <Route path="warehouses/:warehouseId/edit" element={<WarehouseEditPage />} />
                    <Route path="warehouses/:warehouseId" element={<WarehouseDetailPage />} />

                    <Route path="inventory" element={<InventoryDashboardPage />} />
                    <Route path="inventory/transfer-orders" element={<TransferOrdersPage />} />
                    <Route path="inventory/transfer-orders/:transferOrderId" element={<TransferOrderDetailPage />} />
                    <Route path="inventory/cycle-counts" element={<CycleCountsPage />} />
                    <Route path="inventory/cycle-counts/:cycleCountId" element={<CycleCountDetailPage />} />
                    <Route path="inventory/stock-reservations" element={<StockReservationsPage />} />
                    <Route path="inventory/valuation-configs" element={<ValuationConfigsPage />} />

                    <Route path="purchase/orders" element={<PurchaseOrdersListPage />} />
                    <Route path="purchase/orders/new" element={<PurchaseOrderCreatePage />} />
                    <Route path="purchase/orders/:purchaseOrderId/edit" element={<PurchaseOrderEditPage />} />
                    <Route path="purchase/orders/:purchaseOrderId" element={<PurchaseOrderDetailPage />} />
                    <Route path="purchase/grns" element={<GrnListPage />} />
                    <Route path="purchase/grns/new" element={<GrnCreatePage />} />
                    <Route path="purchase/grns/:grnId/edit" element={<GrnEditPage />} />
                    <Route path="purchase/grns/:grnId" element={<GrnDetailPage />} />
                    <Route path="purchase/invoices" element={<PurchaseInvoicesListPage />} />
                    <Route path="purchase/invoices/new" element={<PurchaseInvoiceCreatePage />} />
                    <Route path="purchase/payments/new" element={<PurchasePaymentCreatePage />} />
                    <Route path="purchase/invoices/:invoiceId" element={<PurchaseInvoiceDetailPage />} />
                    <Route path="purchase/returns" element={<PurchaseReturnsListPage />} />
                    <Route path="purchase/returns/new" element={<PurchaseReturnCreatePage />} />
                    <Route path="purchase/returns/:purchaseReturnId" element={<PurchaseReturnDetailPage />} />

                    <Route path="sales/orders" element={<SalesOrdersListPage />} />
                    <Route path="sales/orders/new" element={<SalesOrderCreatePage />} />
                    <Route path="sales/orders/:salesOrderId" element={<SalesOrderDetailPage />} />
                    <Route path="sales/shipments" element={<ShipmentsListPage />} />
                    <Route path="sales/shipments/:shipmentId" element={<ShipmentDetailPage />} />
                    <Route path="sales/invoices" element={<SalesInvoicesListPage />} />
                    <Route path="sales/invoices/:salesInvoiceId" element={<SalesInvoiceDetailPage />} />
                    <Route path="sales/returns" element={<SalesReturnsListPage />} />
                    <Route path="sales/returns/:salesReturnId" element={<SalesReturnDetailPage />} />

                    <Route path="finance/accounts" element={<FinanceAccountsPage />} />
                    <Route path="finance/journal-entries" element={<FinanceJournalEntriesPage />} />
                    <Route path="finance/payments" element={<FinancePaymentsPage />} />
                    <Route path="finance/reports" element={<FinanceReportsPage />} />

                    <Route path="audit-logs/activity" element={<AuditLogActivityPage />} />

                    <Route path="settings/company" element={<CompanySettingsPage />} />
                    <Route path="settings/preferences" element={<PreferencesSettingsPage />} />

                    {appPageRoutes
                        .filter((route) => route.path !== '/' && !isImplementedAppPath(route.path))
                        .map((route) => (
                            <Route key={route.path} path={route.path.slice(1)} element={<ModulePlaceholderPage page={route} />} />
                        ))}

                    <Route path="access-denied" element={<AccessDeniedPage />} />
                    <Route path="*" element={<NotFoundPage />} />
                </Route>
            </Route>
        </Routes>
    );
}
