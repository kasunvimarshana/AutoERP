import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const hrPage = () => lazyNamed(() => import('../../modules/hr/pages/HrPage'), 'HrPage');

export const hrRoutes: RouteObject[] = [
    { element: hrPage(), path: 'hr' },
    { element: hrPage(), path: 'hr/employees' },
    { element: hrPage(), path: 'hr/employees/new' },
    { element: hrPage(), path: 'hr/employees/:id' },
    { element: hrPage(), path: 'hr/employees/:id/edit' },
];
