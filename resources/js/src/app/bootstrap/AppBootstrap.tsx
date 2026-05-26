import { AppProviders } from '../providers/AppProviders';
import { AppRouter } from '../router/AppRouter';

export function AppBootstrap() {
    return (
        <AppProviders>
            <AppRouter />
        </AppProviders>
    );
}
