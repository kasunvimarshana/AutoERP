import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { useTenant } from '../../contexts/TenantContext';
import Card from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Spinner from '../../components/ui/Spinner';

const SettingsPage: React.FC = () => {
  const { currentTenant, tenantId } = useTenant();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
        <p className="text-gray-500 text-sm mt-1">Manage your tenant configuration</p>
      </div>
      <Card title="Tenant Information">
        {currentTenant ? (
          <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {[
              { label: 'Tenant ID', value: currentTenant.id },
              { label: 'Name', value: currentTenant.name },
              { label: 'Slug', value: currentTenant.slug },
              { label: 'Domain', value: currentTenant.domain },
              { label: 'Plan', value: <Badge variant="info" className="capitalize">{currentTenant.plan}</Badge> },
            ].map(({ label, value }) => (
              <div key={label} className="bg-gray-50 rounded-lg p-4">
                <dt className="text-xs font-medium text-gray-500 uppercase tracking-wide">{label}</dt>
                <dd className="mt-1 text-sm font-semibold text-gray-900">{value}</dd>
              </div>
            ))}
          </dl>
        ) : (
          <div className="text-center py-8 text-gray-400">
            <p>No tenant information available.</p>
            <p className="text-xs mt-1">Current Tenant ID: {tenantId}</p>
          </div>
        )}
      </Card>
    </div>
  );
};

export default SettingsPage;
