import { Navigate, RouterProvider as ReactRouterProvider, createBrowserRouter } from 'react-router-dom';
import { AuthLayout } from '../layouts/AuthLayout';
import { LoginPage } from '../modules/auth/pages/LoginPage';

const router = createBrowserRouter([
    {
        element: <AuthLayout />,
        children: [
            { element: <Navigate replace to="/login" />, index: true },
            { element: <LoginPage />, path: 'login' },
        ],
    },
    { element: <Navigate replace to="/login" />, path: '*' },
]);

export function AppRouter() {
    return <ReactRouterProvider router={router} />;
}
