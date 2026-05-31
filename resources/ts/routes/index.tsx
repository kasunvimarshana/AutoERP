import { Navigate, RouterProvider as ReactRouterProvider, createBrowserRouter } from 'react-router-dom';
import { AppLayout } from '../layouts/AppLayout';
import { AuthLayout } from '../layouts/AuthLayout';
import { AuthGuard, GuestGuard } from '../app/guards/AuthGuard';
import { authGuestRoutes, logoutRoute } from './moduleRoutes/auth.routes';
import { auditRoutes } from './moduleRoutes/audit.routes';
import { configurationRoutes } from './moduleRoutes/configuration.routes';
import { customerRoutes } from './moduleRoutes/customer.routes';
import { dashboardRoutes } from './moduleRoutes/dashboard.routes';
import { documentRoutes } from './moduleRoutes/document.routes';
import { financeRoutes } from './moduleRoutes/finance.routes';
import { hrRoutes } from './moduleRoutes/hr.routes';
import { inventoryRoutes } from './moduleRoutes/inventory.routes';
import { itemRoutes } from './moduleRoutes/item.routes';
import { paymentRoutes } from './moduleRoutes/payment.routes';
import { pricingRoutes } from './moduleRoutes/pricing.routes';
import { purchaseRoutes } from './moduleRoutes/purchase.routes';
import { salesRoutes } from './moduleRoutes/sales.routes';
import { settingsRoutes } from './moduleRoutes/settings.routes';
import { supplierRoutes } from './moduleRoutes/supplier.routes';
import { uomRoutes } from './moduleRoutes/uom.routes';
import { tenantRoutes } from './moduleRoutes/tenant.routes';
import { vehicleRoutes } from './moduleRoutes/vehicle.routes';
import { vehicleRentalRoutes } from './moduleRoutes/vehicleRental.routes';
import { vehicleServiceRoutes } from './moduleRoutes/vehicleService.routes';
import { voucherRoutes } from './moduleRoutes/voucher.routes';

const router = createBrowserRouter([
    {
        element: (
            <GuestGuard>
                <AuthLayout />
            </GuestGuard>
        ),
        children: authGuestRoutes,
    },
    logoutRoute,
    {
        element: (
            <AuthGuard>
                <AppLayout />
            </AuthGuard>
        ),
        path: '/',
        children: [
            { element: <Navigate replace to="/dashboard" />, index: true },
            ...dashboardRoutes,
            ...purchaseRoutes,
            ...salesRoutes,
            ...vehicleServiceRoutes,
            ...vehicleRentalRoutes,
            ...financeRoutes,
            ...inventoryRoutes,
            ...paymentRoutes,
            ...documentRoutes,
            ...itemRoutes,
            ...uomRoutes,
            ...pricingRoutes,
            ...supplierRoutes,
            ...customerRoutes,
            ...hrRoutes,
            ...vehicleRoutes,
            ...voucherRoutes,
            ...tenantRoutes,
            ...configurationRoutes,
            ...auditRoutes,
            ...settingsRoutes,
        ],
    },
]);

export function AppRouter() {
    return <ReactRouterProvider router={router} />;
}
