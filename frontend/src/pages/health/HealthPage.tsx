import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { healthApi } from '../../api/health';
import Card from '../../components/ui/Card';
import Spinner from '../../components/ui/Spinner';
import Badge from '../../components/ui/Badge';
import { Activity, CheckCircle, XCircle, RefreshCw } from 'lucide-react';
import { formatDateTime } from '../../utils/formatters';

const HealthPage: React.FC = () => {
  const { data, isLoading, refetch, isFetching } = useQuery({
    queryKey: ['health'],
    queryFn: healthApi.detailed,
    refetchInterval: 30000,
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">System Health</h1>
          <p className="text-gray-500 text-sm mt-1">Real-time status of all services</p>
        </div>
        <button
          onClick={() => refetch()}
          className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <RefreshCw className={`h-4 w-4 ${isFetching ? 'animate-spin' : ''}`} />
          Refresh
        </button>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-20"><Spinner size="lg" /></div>
      ) : data ? (
        <>
          <Card>
            <div className="flex items-center gap-4">
              <div className={`w-12 h-12 rounded-full flex items-center justify-center ${data.status === 'healthy' ? 'bg-green-100' : 'bg-red-100'}`}>
                <Activity className={`h-6 w-6 ${data.status === 'healthy' ? 'text-green-600' : 'text-red-600'}`} />
              </div>
              <div>
                <p className="text-sm text-gray-500">Overall Status</p>
                <div className="flex items-center gap-2 mt-0.5">
                  <Badge variant={data.status === 'healthy' ? 'success' : 'danger'} className="capitalize text-sm">
                    {data.status}
                  </Badge>
                  {data.timestamp && (
                    <span className="text-xs text-gray-400">Last checked: {formatDateTime(data.timestamp)}</span>
                  )}
                </div>
              </div>
            </div>
          </Card>

          {data.services && Object.keys(data.services).length > 0 && (
            <Card title="Services">
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {Object.entries(data.services).map(([name, svc]) => (
                  <div key={name} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-sm font-medium text-gray-700 capitalize">{name}</span>
                      {svc.status === 'healthy' || svc.status === 'ok' || svc.status === 'up'
                        ? <CheckCircle className="h-5 w-5 text-green-500" />
                        : <XCircle className="h-5 w-5 text-red-500" />}
                    </div>
                    <Badge variant={svc.status === 'healthy' || svc.status === 'ok' || svc.status === 'up' ? 'success' : 'danger'} className="capitalize">
                      {svc.status}
                    </Badge>
                    {svc.latency !== undefined && (
                      <p className="text-xs text-gray-400 mt-1">{svc.latency}ms</p>
                    )}
                    {svc.message && <p className="text-xs text-gray-500 mt-1">{svc.message}</p>}
                  </div>
                ))}
              </div>
            </Card>
          )}
        </>
      ) : (
        <Card>
          <div className="text-center py-12 text-gray-400">
            <Activity className="h-10 w-10 mx-auto mb-3 opacity-40" />
            <p>Health data unavailable</p>
          </div>
        </Card>
      )}
    </div>
  );
};

export default HealthPage;
