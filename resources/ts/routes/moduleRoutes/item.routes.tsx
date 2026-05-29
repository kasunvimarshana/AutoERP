import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const itemListPage = () => lazyNamed(() => import('../../modules/item/pages/ItemListPage'), 'ItemListPage');
const itemCreatePage = () => lazyNamed(() => import('../../modules/item/pages/ItemCreatePage'), 'ItemCreatePage');
const itemDetailPage = () => lazyNamed(() => import('../../modules/item/pages/ItemDetailPage'), 'ItemDetailPage');

export const itemRoutes: RouteObject[] = [
    { element: itemListPage(), path: 'items' },
    { element: itemCreatePage(), path: 'items/new' },
    { element: itemDetailPage(), path: 'items/:id' },
    { element: itemCreatePage(), path: 'items/:id/edit' },
];
