import { Navigate, RouterProvider as ReactRouterProvider, createBrowserRouter } from 'react-router-dom';
import { ProtectedRoute } from '../app/guards/ProtectedRoute';
import { AppLayout } from '../layouts/AppLayout';
import { AuthLayout } from '../layouts/AuthLayout';
import { LoginPage } from '../modules/auth/pages/LoginPage';
import { CustomerCreatePage } from '../modules/customer/pages/CustomerCreatePage';
import { CustomerDetailPage } from '../modules/customer/pages/CustomerDetailPage';
import { CustomerEditPage } from '../modules/customer/pages/CustomerEditPage';
import { CustomerListPage } from '../modules/customer/pages/CustomerListPage';
import { ItemCreatePage } from '../modules/item/pages/ItemCreatePage';
import { ItemDetailPage } from '../modules/item/pages/ItemDetailPage';
import { ItemEditPage } from '../modules/item/pages/ItemEditPage';
import { ItemListPage } from '../modules/item/pages/ItemListPage';
import { SupplierCreatePage } from '../modules/supplier/pages/SupplierCreatePage';
import { SupplierDetailPage } from '../modules/supplier/pages/SupplierDetailPage';
import { SupplierEditPage } from '../modules/supplier/pages/SupplierEditPage';
import { SupplierListPage } from '../modules/supplier/pages/SupplierListPage';
import { VehicleCreatePage } from '../modules/vehicle/pages/VehicleCreatePage';
import { VehicleDetailPage } from '../modules/vehicle/pages/VehicleDetailPage';
import { VehicleEditPage } from '../modules/vehicle/pages/VehicleEditPage';
import { VehicleListPage } from '../modules/vehicle/pages/VehicleListPage';
import { UomCreatePage } from '../modules/uom/pages/UomCreatePage';
import { UomDetailPage } from '../modules/uom/pages/UomDetailPage';
import { UomEditPage } from '../modules/uom/pages/UomEditPage';
import { UomListPage } from '../modules/uom/pages/UomListPage';

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
                    { element: <VehicleListPage />, path: 'vehicles' },
                    { element: <VehicleCreatePage />, path: 'vehicles/new' },
                    { element: <VehicleDetailPage />, path: 'vehicles/:id' },
                    { element: <VehicleEditPage />, path: 'vehicles/:id/edit' },
                    { element: <UomListPage />, path: 'uoms' },
                    { element: <UomCreatePage />, path: 'uoms/new' },
                    { element: <UomDetailPage />, path: 'uoms/:id' },
                    { element: <UomEditPage />, path: 'uoms/:id/edit' },
                    { element: <ItemListPage />, path: 'items' },
                    { element: <ItemCreatePage />, path: 'items/new' },
                    { element: <ItemDetailPage />, path: 'items/:id' },
                    { element: <ItemEditPage />, path: 'items/:id/edit' },
                ],
            },
        ],
    },
    { element: <Navigate replace to="/" />, path: '*' },
]);

export function AppRouter() {
    return <ReactRouterProvider router={router} />;
}
