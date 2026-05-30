import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboard = () => lazyNamed(() => import('../../modules/vehicle-service/pages/VehicleServiceDashboardPage'), 'VehicleServiceDashboardPage');
const jobCards = () => import('../../modules/vehicle-service/pages/JobCardListPage');
const createJobCard = () => import('../../modules/vehicle-service/pages/JobCardCreatePage');
const editJobCard = () => import('../../modules/vehicle-service/pages/JobCardEditPage');
const jobCardDetail = () => import('../../modules/vehicle-service/pages/JobCardDetailPage');
const diagnostics = () => import('../../modules/vehicle-service/pages/JobCardDiagnosticsPage');
const inspections = () => import('../../modules/vehicle-service/pages/JobCardInspectionPage');
const serviceTypes = () => import('../../modules/vehicle-service/pages/ServiceTypeListPage');
const invoices = () => import('../../modules/vehicle-service/pages/ServiceInvoiceListPage');
const invoiceDetail = () => import('../../modules/vehicle-service/pages/ServiceInvoiceDetailPage');
const payments = () => import('../../modules/vehicle-service/pages/ServicePaymentListPage');
const createPayment = () => import('../../modules/vehicle-service/pages/ServicePaymentCreatePage');
const history = () => import('../../modules/vehicle-service/pages/ServiceHistoryPage');
const settings = () => import('../../modules/vehicle-service/pages/VehicleServiceSettingsPage');

export const vehicleServiceRoutes: RouteObject[] = [
    { element: dashboard(), path: 'vehicle-service' },
    { element: lazyNamed(serviceTypes, 'ServiceTypeListPage'), path: 'vehicle-service/service-types' },
    { element: lazyNamed(jobCards, 'JobCardListPage'), path: 'vehicle-service/job-cards' },
    { element: lazyNamed(createJobCard, 'JobCardCreatePage'), path: 'vehicle-service/job-cards/new' },
    { element: lazyNamed(createJobCard, 'JobCardCreatePage'), path: 'vehicle-service/job-cards/create' },
    { element: lazyNamed(createJobCard, 'JobCardCreatePage'), path: 'vehicle-service/job-cards/create/crew-members' },
    { element: lazyNamed(jobCardDetail, 'JobCardDetailPage'), path: 'vehicle-service/job-cards/:id' },
    { element: lazyNamed(editJobCard, 'JobCardEditPage'), path: 'vehicle-service/job-cards/:id/edit' },
    { element: lazyNamed(diagnostics, 'JobCardDiagnosticsPage'), path: 'vehicle-service/job-cards/:id/diagnostics' },
    { element: lazyNamed(inspections, 'JobCardInspectionPage'), path: 'vehicle-service/job-cards/:id/inspections' },
    { element: lazyNamed(invoices, 'ServiceInvoiceListPage'), path: 'vehicle-service/invoices' },
    { element: lazyNamed(invoiceDetail, 'ServiceInvoiceDetailPage'), path: 'vehicle-service/invoices/:id' },
    { element: lazyNamed(payments, 'ServicePaymentListPage'), path: 'vehicle-service/payments' },
    { element: lazyNamed(createPayment, 'ServicePaymentCreatePage'), path: 'vehicle-service/payments/new' },
    { element: lazyNamed(history, 'ServiceHistoryPage'), path: 'vehicle-service/history' },
    { element: lazyNamed(settings, 'VehicleServiceSettingsPage'), path: 'vehicle-service/settings' },
];
