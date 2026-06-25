import React from 'react';
import ReactDOM from 'react-dom/client';
import { App } from '@/app/App';
import { AppProviders } from '@/app/providers/AppProviders';
import { AppErrorBoundary } from '@/app/errors/AppErrorBoundary';
import '../css/app.css';

const root = document.getElementById('app');

if (!root) {
    throw new Error('AutoERP root element was not found.');
}

ReactDOM.createRoot(root).render(
    <React.StrictMode>
        <AppErrorBoundary>
            <AppProviders>
                <App />
            </AppProviders>
        </AppErrorBoundary>
    </React.StrictMode>,
);
