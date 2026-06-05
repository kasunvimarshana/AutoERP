import { Navigate, RouterProvider as ReactRouterProvider, createBrowserRouter } from 'react-router-dom';
import { AppLayout } from '../layouts/AppLayout';
import { AuthLayout } from '../layouts/AuthLayout';
import { AuthGuard, GuestGuard } from '../app/guards/AuthGuard';
import { authGuestRoutes, logoutRoute } from './moduleRoutes/auth.routes';
import { auditRoutes } from './moduleRoutes/audit.routes';
import { configurationRoutes } from './moduleRoutes/configuration.routes';
import { customerRoutes } from './moduleRoutes/customer.routes';
import { dashboardRoutes } from './moduleRoutes/dashboard.routes';
import { financeRoutes } from './moduleRoutes/finance.routes';
import { hrRoutes } from './moduleRoutes/hr.routes';
import { inventoryRoutes } from './moduleRoutes/inventory.routes';
import { itemRoutes } from './moduleRoutes/item.routes';
import { paymentRoutes } from './moduleRoutes/payment.routes';
import { purchaseRoutes } from './moduleRoutes/purchase.routes';
import { settingsRoutes } from './moduleRoutes/settings.routes';
import { supplierRoutes } from './moduleRoutes/supplier.routes';
import { uomRoutes } from './moduleRoutes/uom.routes';
import { tenantRoutes } from './moduleRoutes/tenant.routes';
import { vehicleRoutes } from './moduleRoutes/vehicle.routes';
import { vehicleServiceRoutes } from './moduleRoutes/vehicleService.routes';

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
            ...vehicleServiceRoutes,
            ...financeRoutes,
            ...inventoryRoutes,
            ...paymentRoutes,
            ...itemRoutes,
            ...uomRoutes,
            ...supplierRoutes,
            ...customerRoutes,
            ...hrRoutes,
            ...vehicleRoutes,
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
