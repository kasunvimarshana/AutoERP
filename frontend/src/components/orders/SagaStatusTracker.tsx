import React from 'react';
import {
  CheckCircle2,
  XCircle,
  Loader2,
  Clock,
  AlertTriangle,
  RotateCcw,
} from 'lucide-react';
import clsx from 'clsx';
import type { SagaExecution, SagaStepStatus } from '@/types/saga';
import { ORDER_SAGA_STEPS } from '@/types/saga';
import LoadingSpinner from '@/components/common/LoadingSpinner';

interface SagaStatusTrackerProps {
  saga: SagaExecution | undefined;
  isLoading?: boolean;
}

const stepStatusIcon: Record<SagaStepStatus, React.ReactNode> = {
  pending: <Clock size={18} className="text-gray-400" />,
  running: <Loader2 size={18} className="text-blue-500 animate-spin" />,
  completed: <CheckCircle2 size={18} className="text-green-500" />,
  failed: <XCircle size={18} className="text-red-500" />,
  compensating: <RotateCcw size={18} className="text-orange-400 animate-spin" />,
  compensated: <RotateCcw size={18} className="text-orange-500" />,
  skipped: <AlertTriangle size={18} className="text-gray-300" />,
};

const stepStatusClasses: Record<SagaStepStatus, string> = {
  pending: 'border-gray-200 bg-white text-gray-400',
  running: 'border-blue-300 bg-blue-50 text-blue-700',
  completed: 'border-green-300 bg-green-50 text-green-700',
  failed: 'border-red-300 bg-red-50 text-red-700',
  compensating: 'border-orange-300 bg-orange-50 text-orange-700',
  compensated: 'border-orange-200 bg-orange-50 text-orange-600',
  skipped: 'border-gray-100 bg-gray-50 text-gray-400',
};

const sagaStatusLabel: Record<string, { label: string; classes: string }> = {
  pending: { label: 'Pending', classes: 'bg-gray-100 text-gray-600' },
  running: { label: 'Running', classes: 'bg-blue-100 text-blue-700' },
  completed: { label: 'Completed', classes: 'bg-green-100 text-green-700' },
  failed: { label: 'Failed', classes: 'bg-red-100 text-red-700' },
  compensating: { label: 'Compensating', classes: 'bg-orange-100 text-orange-700' },
  compensated: { label: 'Compensated', classes: 'bg-orange-100 text-orange-600' },
};

const formatTime = (iso: string | null): string => {
  if (!iso) return '—';
  return new Date(iso).toLocaleTimeString();
};

const SagaStatusTracker: React.FC<SagaStatusTrackerProps> = ({ saga, isLoading = false }) => {
  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  if (!saga) {
    return (
      <div className="text-center py-10 text-gray-400 text-sm">
        No saga execution found for this order.
      </div>
    );
  }

  const overallConfig = sagaStatusLabel[saga.status] ?? sagaStatusLabel.pending;

  // Build a map of step statuses
  const stepMap = new Map(saga.steps.map((s) => [s.name, s]));

  return (
    <div className="space-y-5">
      {/* Overall status */}
      <div className="flex items-center justify-between bg-gray-50 rounded-xl p-4 border border-gray-100">
        <div>
          <p className="text-xs text-gray-500 uppercase tracking-wide font-medium">Saga ID</p>
          <p className="text-sm font-mono text-gray-700 mt-0.5">{saga.id}</p>
        </div>
        <span
          className={clsx(
            'px-3 py-1 rounded-full text-sm font-semibold',
            overallConfig.classes,
          )}
        >
          {overallConfig.label}
        </span>
      </div>

      {/* Error banner */}
      {saga.error && (
        <div className="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
          <XCircle size={18} className="mt-0.5 shrink-0" />
          <div>
            <p className="font-medium">Saga failed</p>
            <p className="mt-0.5 text-red-600">{saga.error}</p>
          </div>
        </div>
      )}

      {/* Steps */}
      <div className="relative">
        {/* Connector line */}
        <div className="absolute left-[22px] top-8 bottom-8 w-0.5 bg-gray-200 z-0" />

        <div className="space-y-3 relative z-10">
          {ORDER_SAGA_STEPS.map((definition, index) => {
            const step = stepMap.get(definition.name);
            const status: SagaStepStatus = step?.status ?? 'pending';
            const classes = stepStatusClasses[status];

            return (
              <div
                key={definition.name}
                className={clsx(
                  'flex items-start gap-4 p-4 rounded-xl border transition-all',
                  classes,
                )}
              >
                <div className="shrink-0 mt-0.5">{stepStatusIcon[status]}</div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center justify-between gap-2">
                    <p className="text-sm font-semibold">
                      <span className="text-xs text-gray-400 mr-1">Step {index + 1}.</span>
                      {definition.label}
                    </p>
                    <span className="text-xs uppercase font-medium opacity-80 shrink-0">
                      {status}
                    </span>
                  </div>
                  {step && (
                    <div className="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs opacity-70">
                      {step.started_at && (
                        <span>Started: {formatTime(step.started_at)}</span>
                      )}
                      {step.completed_at && (
                        <span>Completed: {formatTime(step.completed_at)}</span>
                      )}
                    </div>
                  )}
                  {step?.error && (
                    <p className="mt-1 text-xs text-red-600 font-medium">{step.error}</p>
                  )}
                  {step?.compensation_error && (
                    <p className="mt-1 text-xs text-orange-600 font-medium">
                      Compensation error: {step.compensation_error}
                    </p>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Timing footer */}
      <div className="grid grid-cols-2 gap-3 text-xs text-gray-500 bg-gray-50 rounded-xl p-3 border border-gray-100">
        <div>
          <p className="font-medium text-gray-600">Started</p>
          <p>{formatTime(saga.started_at)}</p>
        </div>
        {saga.completed_at && (
          <div>
            <p className="font-medium text-gray-600">Completed</p>
            <p>{formatTime(saga.completed_at)}</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default SagaStatusTracker;
