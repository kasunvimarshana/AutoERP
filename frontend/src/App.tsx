import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { TenantProvider } from './contexts/TenantContext';
import Layout from './components/Layout';
import ProtectedRoute from './components/ProtectedRoute';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import UsersPage from './pages/UsersPage';
import ProductsPage from './pages/ProductsPage';
import InventoryPage from './pages/InventoryPage';
import OrdersPage from './pages/OrdersPage';
import TenantConfigPage from './pages/TenantConfigPage';

const App: React.FC = () => (
  <BrowserRouter>
    <AuthProvider>
      <TenantProvider>
        <Layout>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route path="/dashboard" element={<ProtectedRoute><DashboardPage /></ProtectedRoute>} />
            <Route path="/users" element={<ProtectedRoute requiredPermission="user.view"><UsersPage /></ProtectedRoute>} />
            <Route path="/products" element={<ProtectedRoute requiredPermission="product.view"><ProductsPage /></ProtectedRoute>} />
            <Route path="/inventory" element={<ProtectedRoute requiredPermission="inventory.view"><InventoryPage /></ProtectedRoute>} />
            <Route path="/orders" element={<ProtectedRoute requiredPermission="order.view"><OrdersPage /></ProtectedRoute>} />
            <Route path="/tenant-config" element={<ProtectedRoute><TenantConfigPage /></ProtectedRoute>} />
          </Routes>
        </Layout>
      </TenantProvider>
    </AuthProvider>
  </BrowserRouter>
);

export default App;
