import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const vehicleRentalPage = () => lazyNamed(() => import('../../modules/vehicle-rental/pages/VehicleRentalPage'), 'VehicleRentalPage');

export const vehicleRentalRoutes: RouteObject[] = [
    { element: vehicleRentalPage(), path: 'vehicle-rental' },
    { element: vehicleRentalPage(), path: 'vehicle-rental/availability' },
    { element: vehicleRentalPage(), path: 'vehicle-rental/agreements' },
    { element: vehicleRentalPage(), path: 'vehicle-rental/agreements/new' },
    { element: vehicleRentalPage(), path: 'vehicle-rental/agreements/:id' },
    { element: vehicleRentalPage(), path: 'vehicle-rental/running-charts' },
    { element: vehicleRentalPage(), path: 'vehicle-rental/invoices' },
    { element: vehicleRentalPage(), path: 'vehicle-rental/payments' },
    { element: vehicleRentalPage(), path: 'vehicle-rental/provider-payables' },
];
