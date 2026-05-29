import { lazy, Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { AppLayout } from '../layouts/AppLayout';
import { DashboardPage } from '../pages/DashboardPage';
import { ModulesIndexPage } from '../pages/ModulesIndexPage';
import { NotFoundPage } from '../pages/NotFoundPage';
import { moduleCatalog } from '../modules/moduleCatalog';

const modulePageLoaders = import.meta.glob('../modules/*/pages/*Page.tsx');

const moduleRoutes = moduleCatalog.map((module) => {
    const loader = modulePageLoaders[module.pagePath];

    if (!loader) {
        throw new Error(`Missing module page for ${module.key}: ${module.pagePath}`);
    }

    const LazyModulePage = lazy(async () => {
        const loaded = await loader();
        return { default: loaded.default };
    });

    return {
        path: module.path.replace(/^\//, ''),
        element: <LazyModulePage />,
    };
});

export function AppRouter() {
    return (
        <Suspense
            fallback={
                <div className="flex min-h-screen items-center justify-center text-sm font-medium text-slate-600">
                    Loading workspace...
                </div>
            }
        >
            <Routes>
                <Route element={<AppLayout />}>
                    <Route index element={<Navigate to="/dashboard" replace />} />
                    <Route path="/dashboard" element={<DashboardPage />} />
                    <Route path="/modules" element={<ModulesIndexPage />} />
                    {moduleRoutes.map((route) => (
                        <Route key={route.path} path={route.path} element={route.element} />
                    ))}
                    <Route path="*" element={<NotFoundPage />} />
                </Route>
            </Routes>
        </Suspense>
    );
}
