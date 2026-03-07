import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuthStore } from './store/authStore';
import Layout from './components/Layout';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import ProductsPage from './pages/ProductsPage';
import InventoryPage from './pages/InventoryPage';
import OrdersPage from './pages/OrdersPage';

/**
 * Root application component.
 * Handles route-level authentication guard.
 */
function App() {
  const { isAuthenticated } = useAuthStore();

  return (
    <Routes>
      {/* Public routes */}
      <Route path="/login" element={!isAuthenticated ? <LoginPage /> : <Navigate to="/" replace />} />

      {/* Protected routes */}
      <Route
        path="/"
        element={isAuthenticated ? <Layout /> : <Navigate to="/login" replace />}
      >
        <Route index element={<DashboardPage />} />
        <Route path="products"  element={<ProductsPage />} />
        <Route path="inventory" element={<InventoryPage />} />
        <Route path="orders"    element={<OrdersPage />} />
      </Route>
    </Routes>
  );
}

export default App;
