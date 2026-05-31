import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const vehicleListPage = () => lazyNamed(() => import('../../modules/vehicle/pages/VehicleListPage'), 'VehicleListPage');
const vehicleCreatePage = () => lazyNamed(() => import('../../modules/vehicle/pages/VehicleCreatePage'), 'VehicleCreatePage');
const vehicleDetailPage = () => lazyNamed(() => import('../../modules/vehicle/pages/VehicleDetailPage'), 'VehicleDetailPage');
const vehicleEditPage = () => lazyNamed(() => import('../../modules/vehicle/pages/VehicleEditPage'), 'VehicleEditPage');
const vehicleMasterDataPages = () => import('../../modules/vehicle/pages/VehicleMasterDataPages');

export const vehicleRoutes: RouteObject[] = [
    { element: vehicleListPage(), path: 'vehicles' },
    { element: vehicleCreatePage(), path: 'vehicles/new' },
    { element: lazyNamed(vehicleMasterDataPages, 'VehicleTypeListPage'), path: 'vehicles/types' },
    { element: lazyNamed(vehicleMasterDataPages, 'VehicleCategoryListPage'), path: 'vehicles/categories' },
    { element: lazyNamed(vehicleMasterDataPages, 'VehicleBrandListPage'), path: 'vehicles/brands' },
    { element: lazyNamed(vehicleMasterDataPages, 'VehicleModelListPage'), path: 'vehicles/models' },
    { element: lazyNamed(vehicleMasterDataPages, 'VehicleHistoryPage'), path: 'vehicles/history' },
    { element: vehicleDetailPage(), path: 'vehicles/:id' },
    { element: vehicleEditPage(), path: 'vehicles/:id/edit' },
];
