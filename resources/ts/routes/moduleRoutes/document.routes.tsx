import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const documentPage = () => lazyNamed(() => import('../../modules/document/pages/DocumentPage'), 'DocumentPage');

export const documentRoutes: RouteObject[] = [{ element: documentPage(), path: 'documents' }];
