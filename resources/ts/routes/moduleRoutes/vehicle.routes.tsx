import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const vehiclePage = () => lazyNamed(() => import('../../modules/vehicle'), 'VehiclePage');

export const vehicleRoutes: RouteObject[] = [
    { element: vehiclePage(), path: 'vehicles' },
    { element: vehiclePage(), path: 'vehicles/new' },
    { element: vehiclePage(), path: 'vehicles/:id' },
    { element: vehiclePage(), path: 'vehicles/:id/edit' },
];
