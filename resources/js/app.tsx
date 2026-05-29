import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import './bootstrap';
import { AppProviders } from './providers/AppProviders';
import { AppRouter } from './router/AppRouter';

const container = document.getElementById('app');

if (!container) {
    throw new Error('App root element not found');
}

createRoot(container).render(
    <React.StrictMode>
        <AppProviders>
            <BrowserRouter>
                <AppRouter />
            </BrowserRouter>
        </AppProviders>
    </React.StrictMode>,
);
