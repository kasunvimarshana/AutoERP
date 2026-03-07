import Modal from './Modal';
import { AlertTriangle, Info, AlertCircle } from 'lucide-react';

const variants = {
  danger:  { icon: AlertCircle,  btn: 'bg-red-600 hover:bg-red-700 text-white', icon_color: 'text-red-500' },
  warning: { icon: AlertTriangle, btn: 'bg-yellow-500 hover:bg-yellow-600 text-white', icon_color: 'text-yellow-500' },
  info:    { icon: Info,          btn: 'bg-indigo-600 hover:bg-indigo-700 text-white', icon_color: 'text-indigo-500' },
};

export default function ConfirmDialog({ isOpen, onClose, onConfirm, title, message, confirmLabel = 'Confirm', variant = 'danger' }) {
  const v = variants[variant];
  const Icon = v.icon;
  return (
    <Modal isOpen={isOpen} onClose={onClose} title={title} size="sm">
      <div className="flex gap-3 mb-4">
        <Icon size={22} className={`shrink-0 mt-0.5 ${v.icon_color}`} />
        <p className="text-sm text-gray-600">{message}</p>
      </div>
      <div className="flex justify-end gap-2 pt-2">
        <button onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
        <button onClick={() => { onConfirm(); onClose(); }} className={`px-4 py-2 text-sm rounded-lg font-medium ${v.btn}`}>{confirmLabel}</button>
      </div>
    </Modal>
  );
}
