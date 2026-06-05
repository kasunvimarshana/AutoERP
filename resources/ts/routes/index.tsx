import { Navigate, RouterProvider as ReactRouterProvider, createBrowserRouter } from 'react-router-dom';
import { ProtectedRoute } from '../app/guards/ProtectedRoute';
import { AppLayout } from '../layouts/AppLayout';
import { AuthLayout } from '../layouts/AuthLayout';
import { LoginPage } from '../modules/auth/pages/LoginPage';
import { CustomerCreatePage } from '../modules/customer/pages/CustomerCreatePage';
import { CustomerDetailPage } from '../modules/customer/pages/CustomerDetailPage';
import { CustomerEditPage } from '../modules/customer/pages/CustomerEditPage';
import { CustomerListPage } from '../modules/customer/pages/CustomerListPage';
import { SupplierCreatePage } from '../modules/supplier/pages/SupplierCreatePage';
import { SupplierDetailPage } from '../modules/supplier/pages/SupplierDetailPage';
import { SupplierEditPage } from '../modules/supplier/pages/SupplierEditPage';
import { SupplierListPage } from '../modules/supplier/pages/SupplierListPage';

const router = createBrowserRouter([
    {
        element: <AuthLayout />,
        children: [
            { element: <LoginPage />, path: 'login' },
        ],
    },
    {
        element: <ProtectedRoute />,
        children: [
            {
                element: <AppLayout />,
                children: [
                    { element: <Navigate replace to="/customers" />, index: true },
                    { element: <CustomerListPage />, path: 'customers' },
                    { element: <CustomerCreatePage />, path: 'customers/new' },
                    { element: <CustomerDetailPage />, path: 'customers/:id' },
                    { element: <CustomerEditPage />, path: 'customers/:id/edit' },
                    { element: <SupplierListPage />, path: 'suppliers' },
                    { element: <SupplierCreatePage />, path: 'suppliers/new' },
                    { element: <SupplierDetailPage />, path: 'suppliers/:id' },
                    { element: <SupplierEditPage />, path: 'suppliers/:id/edit' },
                ],
            },
        ],
    },
    { element: <Navigate replace to="/" />, path: '*' },
]);

export function AppRouter() {
    return <ReactRouterProvider router={router} />;
}
