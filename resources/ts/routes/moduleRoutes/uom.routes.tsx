import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const uomListPage = () => lazyNamed(() => import('../../modules/uom/pages/UomListPage'), 'UomListPage');
const uomCreatePage = () => lazyNamed(() => import('../../modules/uom/pages/UomCreatePage'), 'UomCreatePage');

export const uomRoutes: RouteObject[] = [
    { element: uomListPage(), path: 'uom' },
    { element: uomCreatePage(), path: 'uom/units' },
    { element: uomListPage(), path: 'uom/conversions' },
];
