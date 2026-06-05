import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const loginPage = () => lazyNamed(() => import('../../modules/auth/pages/LoginPage'), 'LoginPage');
const forgotPasswordPage = () => lazyNamed(() => import('../../modules/auth/pages/ForgotPasswordPage'), 'ForgotPasswordPage');
const resetPasswordPage = () => lazyNamed(() => import('../../modules/auth/pages/ResetPasswordPage'), 'ResetPasswordPage');
const logoutPage = () => lazyNamed(() => import('../../modules/auth/pages/LogoutPage'), 'LogoutPage');

export const authGuestRoutes: RouteObject[] = [
    { element: loginPage(), path: 'login' },
    { element: forgotPasswordPage(), path: 'forgot-password' },
    { element: resetPasswordPage(), path: 'reset-password' },
];

export const logoutRoute: RouteObject = { element: logoutPage(), path: '/logout' };
