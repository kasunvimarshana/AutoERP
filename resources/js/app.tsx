import React from 'react';
import ReactDOM from 'react-dom/client';
import '../css/app.css';
import { AppBootstrap } from './src/app/bootstrap/AppBootstrap';

ReactDOM.createRoot(document.getElementById('app')!).render(
    <React.StrictMode>
        <AppBootstrap />
    </React.StrictMode>,
);
