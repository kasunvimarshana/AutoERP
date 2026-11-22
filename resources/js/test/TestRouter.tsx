/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { ReactNode } from 'react';
import { RouterProvider, createMemoryRouter } from 'react-router-dom';
import { AppToastContainer } from '@/shared/notifications/appToast';

interface TestRouterProps {
    children: ReactNode;
    initialEntries?: string[];
}

export function TestRouter({ children, initialEntries = ['/'] }: TestRouterProps) {
    const router = createMemoryRouter(
        [{ path: '*', element: children }],
        { initialEntries },
    );

    return (
        <>
            <RouterProvider router={router} />
            <AppToastContainer />
        </>
    );
}
