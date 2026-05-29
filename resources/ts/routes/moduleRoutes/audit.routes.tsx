import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const auditPage = () => lazyNamed(() => import('../../modules/audit'), 'AuditPage');

export const auditRoutes: RouteObject[] = [{ element: auditPage(), path: 'audit' }];
