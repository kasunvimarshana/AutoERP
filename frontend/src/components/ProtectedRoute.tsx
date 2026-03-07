import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

interface ProtectedRouteProps {
  children: React.ReactNode;
  requiredPermission?: string;
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ children, requiredPermission }) => {
  const { isAuthenticated, hasPermission } = useAuth();

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (requiredPermission && !hasPermission(requiredPermission)) {
    return <div style={{ padding: '2rem', color: '#dc2626' }}>
      <h2>Access Denied</h2>
      <p>You don't have permission to view this page.</p>
    </div>;
  }

  return <>{children}</>;
};

export default ProtectedRoute;
