import { Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout/Layout.jsx'
import ProtectedRoute from './components/Layout/ProtectedRoute.jsx'
import ErrorBoundary from './components/UI/ErrorBoundary.jsx'
import LoginPage from './pages/Auth/LoginPage.jsx'
import RegisterPage from './pages/Auth/RegisterPage.jsx'
import DashboardPage from './pages/Dashboard/DashboardPage.jsx'
import UsersPage from './pages/Users/UsersPage.jsx'
import CreateUserPage from './pages/Users/CreateUserPage.jsx'
import EditUserPage from './pages/Users/EditUserPage.jsx'
import ProductsPage from './pages/Products/ProductsPage.jsx'
import CreateProductPage from './pages/Products/CreateProductPage.jsx'
import EditProductPage from './pages/Products/EditProductPage.jsx'
import InventoryPage from './pages/Inventory/InventoryPage.jsx'
import CreateInventoryPage from './pages/Inventory/CreateInventoryPage.jsx'
import EditInventoryPage from './pages/Inventory/EditInventoryPage.jsx'
import OrdersPage from './pages/Orders/OrdersPage.jsx'
import CreateOrderPage from './pages/Orders/CreateOrderPage.jsx'
import OrderDetailPage from './pages/Orders/OrderDetailPage.jsx'
import HealthPage from './pages/Health/HealthPage.jsx'

export default function App() {
  return (
    <ErrorBoundary>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route element={<ProtectedRoute />}>
          <Route element={<Layout />}>
            <Route path="/dashboard" element={<DashboardPage />} />
            <Route path="/users" element={<UsersPage />} />
            <Route path="/users/create" element={<CreateUserPage />} />
            <Route path="/users/:id/edit" element={<EditUserPage />} />
            <Route path="/products" element={<ProductsPage />} />
            <Route path="/products/create" element={<CreateProductPage />} />
            <Route path="/products/:id/edit" element={<EditProductPage />} />
            <Route path="/inventory" element={<InventoryPage />} />
            <Route path="/inventory/create" element={<CreateInventoryPage />} />
            <Route path="/inventory/:id/edit" element={<EditInventoryPage />} />
            <Route path="/orders" element={<OrdersPage />} />
            <Route path="/orders/create" element={<CreateOrderPage />} />
            <Route path="/orders/:id" element={<OrderDetailPage />} />
            <Route path="/health" element={<HealthPage />} />
          </Route>
        </Route>
        <Route path="/" element={<Navigate to="/dashboard" replace />} />
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </ErrorBoundary>
  )
}
