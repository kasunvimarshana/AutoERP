import { Suspense } from 'react';
import { AppRouter } from './router';
import { LoadingState } from '@/shared/components/LoadingState';

export function App() {
    return (
        <Suspense fallback={<LoadingState label="Loading AutoERP..." fullPage />}>
            <AppRouter />
        </Suspense>
    );
}
