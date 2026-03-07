import { useQuery } from '@tanstack/react-query'
import { format } from 'date-fns'
import { CheckCircle, XCircle, AlertTriangle, RefreshCw, Activity } from 'lucide-react'

import { healthApi } from '../../api/healthApi'
import Badge from '../../components/UI/Badge'
import Button from '../../components/UI/Button'
import LoadingSpinner from '../../components/UI/LoadingSpinner'

function StatusIcon({ status }) {
  if (status === 'healthy') return <CheckCircle className="w-5 h-5 text-green-500" />
  if (status === 'degraded') return <AlertTriangle className="w-5 h-5 text-yellow-500" />
  return <XCircle className="w-5 h-5 text-red-500" />
}

function statusVariant(status) {
  if (status === 'healthy') return 'success'
  if (status === 'degraded') return 'warning'
  return 'danger'
}

const SERVICE_LABELS = { database: 'Database', redis: 'Redis', rabbitmq: 'RabbitMQ' }

export default function HealthPage() {
  const { data, isLoading, isError, refetch, isFetching, dataUpdatedAt } = useQuery({
    queryKey: ['health'],
    queryFn: () => healthApi.getHealthStatus(),
    refetchInterval: 30000,
  })

  const health = data?.data ?? null
  const services = health?.services ?? {}
  const overallStatus = health?.status ?? (isError ? 'unhealthy' : null)

  return (
    <div className="space-y-6 max-w-3xl">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <Activity className="w-6 h-6 text-blue-600" />System Health
          </h1>
          <p className="mt-1 text-sm text-gray-500">Real-time service status (auto-refreshes every 30s)</p>
        </div>
        <Button variant="secondary" size="sm" loading={isFetching} onClick={() => refetch()}>
          <RefreshCw className={`w-4 h-4 ${isFetching ? 'animate-spin' : ''}`} />Refresh
        </Button>
      </div>

      {isLoading ? (
        <div className="flex justify-center py-20"><LoadingSpinner size="lg" text="Checking health…" /></div>
      ) : isError && !health ? (
        <div className="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
          <XCircle className="w-10 h-10 text-red-500 mx-auto mb-2" />
          <p className="font-semibold text-red-700">Health check failed</p>
          <p className="text-sm text-red-500 mt-1">Could not reach the health endpoint.</p>
        </div>
      ) : (
        <>
          {/* Overall status */}
          <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <StatusIcon status={overallStatus} />
              <div>
                <p className="text-sm text-gray-500">Overall Status</p>
                <p className="text-lg font-bold text-gray-900 capitalize">{overallStatus ?? '—'}</p>
              </div>
            </div>
            <div className="text-right text-xs text-gray-400">
              {dataUpdatedAt ? (
                <>Last checked<br />{format(new Date(dataUpdatedAt), 'HH:mm:ss')}</>
              ) : null}
            </div>
          </div>

          {/* Services */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {Object.entries(services).map(([key, svc]) => (
              <div key={key} className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div className="flex items-center justify-between mb-3">
                  <span className="text-sm font-semibold text-gray-700">{SERVICE_LABELS[key] ?? key}</span>
                  <StatusIcon status={svc?.status} />
                </div>
                <Badge variant={statusVariant(svc?.status)} className="capitalize">{svc?.status ?? 'unknown'}</Badge>
                {svc?.latency != null && (
                  <p className="mt-2 text-xs text-gray-500">Latency: <span className="font-medium text-gray-800">{svc.latency}ms</span></p>
                )}
              </div>
            ))}

            {Object.keys(services).length === 0 && (
              <div className="sm:col-span-3 text-center text-gray-400 text-sm py-6">
                No service details available.
              </div>
            )}
          </div>

          {health?.timestamp && (
            <p className="text-xs text-gray-400 text-center">
              Server timestamp: {format(new Date(health.timestamp), 'PPpp')}
            </p>
          )}
        </>
      )}
    </div>
  )
}
