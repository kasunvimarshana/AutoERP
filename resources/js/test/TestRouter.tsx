import type { ReactNode } from 'react';
import { RouterProvider, createMemoryRouter } from 'react-router-dom';

interface TestRouterProps {
    children: ReactNode;
    initialEntries?: string[];
}

export function TestRouter({ children, initialEntries = ['/'] }: TestRouterProps) {
    const router = createMemoryRouter(
        [{ path: '*', element: children }],
        { initialEntries },
    );

    return <RouterProvider router={router} />;
}
