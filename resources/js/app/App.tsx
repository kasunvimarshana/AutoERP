import { Suspense } from 'react';
import { AppRouter } from './router';
import { LoadingState } from '@/shared/components/LoadingState';
import { AppToastContainer } from '@/shared/notifications/appToast';

export function App() {
    return (
        <Suspense fallback={<LoadingState label="Loading AutoERP..." fullPage />}>
            <AppRouter />
            <AppToastContainer />
        </Suspense>
    );
}
