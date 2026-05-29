import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const jobCardListPage = () => lazyNamed(() => import('../../modules/vehicle-service/pages/JobCardListPage'), 'JobCardListPage');
const jobCardCreatePage = () => lazyNamed(() => import('../../modules/vehicle-service/pages/JobCardCreatePage'), 'JobCardCreatePage');
const jobCardCrewAssignmentPage = () => lazyNamed(() => import('../../modules/vehicle-service/pages/JobCardCrewAssignmentPage'), 'JobCardCrewAssignmentPage');
const jobCardDetailPage = () => lazyNamed(() => import('../../modules/vehicle-service/pages/JobCardDetailPage'), 'JobCardDetailPage');
const jobCardEditPage = () => lazyNamed(() => import('../../modules/vehicle-service/pages/JobCardEditPage'), 'JobCardEditPage');
const serviceInvoicesPage = () => lazyNamed(() => import('../../modules/vehicle-service/pages/ServiceInvoicesPage'), 'ServiceInvoicesPage');
const servicePaymentsPage = () => lazyNamed(() => import('../../modules/vehicle-service/pages/ServicePaymentsPage'), 'ServicePaymentsPage');
const serviceHistoryPage = () => lazyNamed(() => import('../../modules/vehicle-service/pages/ServiceHistoryPage'), 'ServiceHistoryPage');

export const vehicleServiceRoutes: RouteObject[] = [
    { element: jobCardListPage(), path: 'vehicle-service' },
    { element: jobCardListPage(), path: 'vehicle-service/job-cards' },
    { element: jobCardCreatePage(), path: 'vehicle-service/job-cards/new' },
    { element: jobCardCreatePage(), path: 'vehicle-service/job-cards/create' },
    { element: jobCardCrewAssignmentPage(), path: 'vehicle-service/job-cards/create/crew-members' },
    { element: serviceInvoicesPage(), path: 'vehicle-service/invoices' },
    { element: servicePaymentsPage(), path: 'vehicle-service/payments' },
    { element: serviceHistoryPage(), path: 'vehicle-service/history' },
    { element: jobCardDetailPage(), path: 'vehicle-service/job-cards/:id' },
    { element: jobCardEditPage(), path: 'vehicle-service/job-cards/:id/edit' },
];
