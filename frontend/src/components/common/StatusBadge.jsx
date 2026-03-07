import { clsx } from 'clsx';
import { STATUS_COLORS } from '../../utils/constants';

export default function StatusBadge({ status }) {
  return (
    <span className={clsx('inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', STATUS_COLORS[status] || 'bg-gray-100 text-gray-800')}>
      {status}
    </span>
  );
}
