import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
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
            <BrowserRouter>
                <AppProviders>
                    <App />
                </AppProviders>
            </BrowserRouter>
        </AppErrorBoundary>
    </React.StrictMode>,
);
