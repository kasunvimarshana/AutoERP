import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const uomDashboardPage = () => lazyNamed(() => import('../../modules/uom/pages/UomDashboardPage'), 'UomDashboardPage');
const uomUnitListPage = () => lazyNamed(() => import('../../modules/uom/pages/UomUnitListPage'), 'UomUnitListPage');
const uomUnitCreatePage = () => lazyNamed(() => import('../../modules/uom/pages/UomUnitCreatePage'), 'UomUnitCreatePage');
const uomUnitDetailPage = () => lazyNamed(() => import('../../modules/uom/pages/UomUnitDetailPage'), 'UomUnitDetailPage');
const uomUnitEditPage = () => lazyNamed(() => import('../../modules/uom/pages/UomUnitEditPage'), 'UomUnitEditPage');
const uomConversionListPage = () => lazyNamed(() => import('../../modules/uom/pages/UomConversionListPage'), 'UomConversionListPage');
const uomConversionCreatePage = () => lazyNamed(() => import('../../modules/uom/pages/UomConversionCreatePage'), 'UomConversionCreatePage');
const uomConversionEditPage = () => lazyNamed(() => import('../../modules/uom/pages/UomConversionEditPage'), 'UomConversionEditPage');
const uomConversionPreviewPage = () => lazyNamed(() => import('../../modules/uom/pages/UomConversionPreviewPage'), 'UomConversionPreviewPage');

export const uomRoutes: RouteObject[] = [
    { element: uomDashboardPage(), path: 'uom' },
    { element: uomUnitListPage(), path: 'uom/units' },
    { element: uomUnitCreatePage(), path: 'uom/units/new' },
    { element: uomUnitDetailPage(), path: 'uom/units/:id' },
    { element: uomUnitEditPage(), path: 'uom/units/:id/edit' },
    { element: uomConversionListPage(), path: 'uom/conversions' },
    { element: uomConversionCreatePage(), path: 'uom/conversions/new' },
    { element: uomConversionEditPage(), path: 'uom/conversions/:id/edit' },
    { element: uomConversionPreviewPage(), path: 'uom/convert' },
];
