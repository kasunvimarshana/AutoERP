import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const tenantPage = () => lazyNamed(() => import('../../modules/tenant'), 'TenantPage');

export const tenantRoutes: RouteObject[] = [{ element: tenantPage(), path: 'tenant' }];
