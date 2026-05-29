import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const itemListPage = () => lazyNamed(() => import('../../modules/item/pages/ItemListPage'), 'ItemListPage');
const itemCreatePage = () => lazyNamed(() => import('../../modules/item/pages/ItemCreatePage'), 'ItemCreatePage');
const itemDetailPage = () => lazyNamed(() => import('../../modules/item/pages/ItemDetailPage'), 'ItemDetailPage');
const itemEditPage = () => lazyNamed(() => import('../../modules/item/pages/ItemEditPage'), 'ItemEditPage');
const itemCategoryListPage = () => lazyNamed(() => import('../../modules/item/pages/ItemReferencePages'), 'ItemCategoryListPage');
const itemAttributeListPage = () => lazyNamed(() => import('../../modules/item/pages/ItemReferencePages'), 'ItemAttributeListPage');
const itemVariantListPage = () => lazyNamed(() => import('../../modules/item/pages/ItemReferencePages'), 'ItemVariantListPage');
const itemComboListPage = () => lazyNamed(() => import('../../modules/item/pages/ItemReferencePages'), 'ItemComboListPage');
const itemIdentifierListPage = () => lazyNamed(() => import('../../modules/item/pages/ItemReferencePages'), 'ItemIdentifierListPage');

export const itemRoutes: RouteObject[] = [
    { element: itemListPage(), path: 'items' },
    { element: itemCreatePage(), path: 'items/new' },
    { element: itemCategoryListPage(), path: 'items/categories' },
    { element: itemAttributeListPage(), path: 'items/attributes' },
    { element: itemVariantListPage(), path: 'items/variants' },
    { element: itemComboListPage(), path: 'items/combos' },
    { element: itemIdentifierListPage(), path: 'items/identifiers' },
    { element: itemDetailPage(), path: 'items/:id' },
    { element: itemEditPage(), path: 'items/:id/edit' },
];
