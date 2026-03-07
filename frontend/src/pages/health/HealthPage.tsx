import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { healthApi } from '@/api/health';
import { CheckCircle2, XCircle, AlertTriangle, RefreshCw, Activity } from 'lucide-react';
import clsx from 'clsx';
import LoadingSpinner from '@/components/common/LoadingSpinner';

const statusIcon = {
  up: <CheckCircle2 size={18} className="text-green-500" />,
  down: <XCircle size={18} className="text-red-500" />,
  degraded: <AlertTriangle size={18} className="text-yellow-500" />,
};

const statusClasses = {
  healthy: 'bg-green-100 text-green-700 border-green-200',
  degraded: 'bg-yellow-100 text-yellow-700 border-yellow-200',
  down: 'bg-red-100 text-red-700 border-red-200',
};

const HealthPage: React.FC = () => {
  const { data, isLoading, refetch, isFetching, dataUpdatedAt } = useQuery({
    queryKey: ['health'],
    queryFn: () => healthApi.detailed(),
    refetchInterval: 30_000,
  });

  return (
    <div className="space-y-5 max-w-3xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">System Health</h1>
          <p className="text-sm text-gray-500 mt-0.5">
            {dataUpdatedAt > 0
              ? `Last updated: ${new Date(dataUpdatedAt).toLocaleTimeString()}`
              : 'Checking services…'}
          </p>
        </div>
        <button
          onClick={() => refetch()}
          disabled={isFetching}
          className="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
        >
          <RefreshCw size={15} className={isFetching ? 'animate-spin' : ''} />
          Refresh
        </button>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-16">
          <LoadingSpinner size="lg" />
        </div>
      ) : !data ? (
        <div className="text-center py-16 text-gray-400">
          <Activity className="mx-auto w-10 h-10 mb-3 opacity-50" />
          <p>Unable to reach health endpoint</p>
        </div>
      ) : (
        <>
          {/* Overall status */}
          <div
            className={clsx(
              'flex items-center justify-between p-5 rounded-2xl border',
              statusClasses[data.status],
            )}
          >
            <div className="flex items-center gap-3">
              {data.status === 'healthy' ? (
                <CheckCircle2 size={28} />
              ) : data.status === 'degraded' ? (
                <AlertTriangle size={28} />
              ) : (
                <XCircle size={28} />
              )}
              <div>
                <p className="font-semibold text-lg capitalize">{data.status}</p>
                <p className="text-sm opacity-80">
                  {data.services.filter((s) => s.status === 'up').length} of{' '}
                  {data.services.length} services operational
                </p>
              </div>
            </div>
            <p className="text-sm opacity-70">
              {new Date(data.timestamp).toLocaleString()}
            </p>
          </div>

          {/* Services */}
          <div className="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h2 className="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-4">
              Services
            </h2>
            <div className="space-y-3">
              {data.services.map((service) => (
                <div
                  key={service.name}
                  className="flex items-center justify-between p-3.5 rounded-xl border border-gray-100 bg-gray-50"
                >
                  <div className="flex items-center gap-3">
                    {statusIcon[service.status]}
                    <div>
                      <p className="text-sm font-semibold text-gray-800 capitalize">
                        {service.name.replace(/_/g, ' ')}
                      </p>
                      {service.message && (
                        <p className="text-xs text-gray-500 mt-0.5">{service.message}</p>
                      )}
                    </div>
                  </div>
                  <div className="text-right">
                    <span
                      className={clsx(
                        'px-2.5 py-0.5 rounded-full text-xs font-medium border',
                        service.status === 'up'
                          ? 'bg-green-100 text-green-700 border-green-200'
                          : service.status === 'degraded'
                          ? 'bg-yellow-100 text-yellow-700 border-yellow-200'
                          : 'bg-red-100 text-red-700 border-red-200',
                      )}
                    >
                      {service.status}
                    </span>
                    {service.response_time_ms !== null && (
                      <p className="text-xs text-gray-400 mt-1">
                        {service.response_time_ms}ms
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </>
      )}
    </div>
  );
};

export default HealthPage;
