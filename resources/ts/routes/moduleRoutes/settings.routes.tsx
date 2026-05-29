import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const settingsPage = () => lazyNamed(() => import('../../modules/settings/pages/SettingsPage'), 'SettingsPage');

export const settingsRoutes: RouteObject[] = [
    { element: settingsPage(), path: 'settings' },
    { element: settingsPage(), path: 'settings/users' },
    { element: settingsPage(), path: 'settings/organization-units' },
];
