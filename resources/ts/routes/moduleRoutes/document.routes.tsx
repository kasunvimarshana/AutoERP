import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const documentDashboardPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentDashboardPage'), 'DocumentDashboardPage');
const documentRecordListPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentRecordListPage'), 'DocumentRecordListPage');
const documentRecordDetailPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentRecordDetailPage'), 'DocumentRecordDetailPage');
const documentTypeListPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentTypePages'), 'DocumentTypeListPage');
const documentTypeCreatePage = () => lazyNamed(() => import('../../modules/document/pages/DocumentTypePages'), 'DocumentTypeCreatePage');
const documentTypeEditPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentTypePages'), 'DocumentTypeEditPage');
const documentDefinitionListPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentDefinitionPages'), 'DocumentDefinitionListPage');
const documentDefinitionCreatePage = () => lazyNamed(() => import('../../modules/document/pages/DocumentDefinitionPages'), 'DocumentDefinitionCreatePage');
const documentDefinitionDetailPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentDefinitionPages'), 'DocumentDefinitionDetailPage');
const documentDefinitionEditPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentDefinitionPages'), 'DocumentDefinitionEditPage');
const documentTemplateListPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentTemplatePages'), 'DocumentTemplateListPage');
const documentTemplateCreatePage = () => lazyNamed(() => import('../../modules/document/pages/DocumentTemplatePages'), 'DocumentTemplateCreatePage');
const documentTemplateEditPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentTemplatePages'), 'DocumentTemplateEditPage');
const documentWorkflowListPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentOperationalPages'), 'DocumentWorkflowListPage');
const documentSequenceListPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentOperationalPages'), 'DocumentSequenceListPage');
const documentPreviewPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentOperationalPages'), 'DocumentPreviewPage');

export const documentRoutes: RouteObject[] = [
    { element: documentDashboardPage(), path: 'documents' },
    { element: documentRecordListPage(), path: 'documents/records' },
    { element: documentRecordDetailPage(), path: 'documents/records/:id' },
    { element: documentTypeListPage(), path: 'documents/types' },
    { element: documentTypeCreatePage(), path: 'documents/types/new' },
    { element: documentTypeEditPage(), path: 'documents/types/:id/edit' },
    { element: documentDefinitionListPage(), path: 'documents/definitions' },
    { element: documentDefinitionCreatePage(), path: 'documents/definitions/new' },
    { element: documentDefinitionDetailPage(), path: 'documents/definitions/:id' },
    { element: documentDefinitionEditPage(), path: 'documents/definitions/:id/edit' },
    { element: documentTemplateListPage(), path: 'documents/templates' },
    { element: documentTemplateCreatePage(), path: 'documents/templates/new' },
    { element: documentTemplateEditPage(), path: 'documents/templates/:id/edit' },
    { element: documentWorkflowListPage(), path: 'documents/workflows' },
    { element: documentSequenceListPage(), path: 'documents/sequences' },
    { element: documentPreviewPage(), path: 'documents/preview' },
];
