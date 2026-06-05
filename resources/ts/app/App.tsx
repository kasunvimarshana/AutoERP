import { AppProviders } from './providers/AppProviders';
import { RouterProvider } from './providers/RouterProvider';

export function App() {
    return (
        <AppProviders>
            <RouterProvider />
        </AppProviders>
    );
}
