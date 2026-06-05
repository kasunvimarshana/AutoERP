import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const configurationPage = () => lazyNamed(() => import('../../modules/configuration'), 'ConfigurationPage');

export const configurationRoutes: RouteObject[] = [{ element: configurationPage(), path: 'configuration' }];
