import React from 'react';
import ReactDOM from 'react-dom/client';
import { ReactKeycloakProvider } from '@react-keycloak/web';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import keycloak from './keycloak';
import App from './App';
import './index.css';

const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, staleTime: 30000 } },
});

const keycloakProviderInitConfig = {
  onLoad: 'login-required',
  checkLoginIframe: false,
};

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <ReactKeycloakProvider authClient={keycloak} initOptions={keycloakProviderInitConfig}>
      <QueryClientProvider client={queryClient}>
        <App />
        <ToastContainer position="top-right" autoClose={4000} />
      </QueryClientProvider>
    </ReactKeycloakProvider>
  </React.StrictMode>
);
