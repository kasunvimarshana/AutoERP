import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboardPage = () => lazyNamed(() => import('../../modules/dashboard/pages/DashboardPage'), 'DashboardPage');

export const dashboardRoutes: RouteObject[] = [{ element: dashboardPage(), path: 'dashboard' }];
