import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Zap } from 'lucide-react';
import { webhooksApi } from '../../api/webhooks';
import { Webhook } from '../../types';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Table, { Column } from '../../components/ui/Table';
import Badge from '../../components/ui/Badge';
import WebhookModal from './WebhookModal';
import toast from 'react-hot-toast';
import { formatDate } from '../../utils/formatters';

const WebhooksPage: React.FC = () => {
  const queryClient = useQueryClient();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingWebhook, setEditingWebhook] = useState<Webhook | null>(null);
  const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null);

  const { data = [], isLoading } = useQuery({ queryKey: ['webhooks'], queryFn: webhooksApi.list });

  const deleteMutation = useMutation({ mutationFn: webhooksApi.delete, onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['webhooks'] }); toast.success('Webhook deleted'); setDeleteConfirm(null); }, onError: (e: any) => toast.error(e.response?.data?.message || 'Failed') });
  const testMutation = useMutation({ mutationFn: webhooksApi.test, onSuccess: (r) => toast.success(r.message || 'Test sent'), onError: (e: any) => toast.error(e.response?.data?.message || 'Test failed') });

  const columns: Column<Webhook>[] = [
    { key: 'name', header: 'Name', render: (row) => <div><p className="font-medium text-gray-900">{row.name}</p><p className="text-xs text-gray-500 font-mono truncate max-w-xs">{row.url}</p></div> },
    { key: 'events', header: 'Events', render: (row) => <div className="flex flex-wrap gap-1">{row.events.slice(0, 3).map(e => <span key={e} className="text-xs bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded">{e}</span>)}{row.events.length > 3 && <span className="text-xs text-gray-400">+{row.events.length - 3}</span>}</div> },
    { key: 'is_active', header: 'Status', render: (row) => <Badge variant={row.is_active ? 'success' : 'gray'}>{row.is_active ? 'Active' : 'Inactive'}</Badge> },
    { key: 'created_at', header: 'Created', render: (row) => <span className="text-sm text-gray-500">{row.created_at ? formatDate(row.created_at) : '—'}</span> },
    { key: 'actions', header: 'Actions', className: 'text-right', render: (row) => (
      <div className="flex items-center justify-end gap-2">
        <Button variant="ghost" size="sm" leftIcon={<Zap className="h-3.5 w-3.5" />} isLoading={testMutation.isPending} onClick={() => testMutation.mutate(row.id)}>Test</Button>
        <Button variant="ghost" size="sm" leftIcon={<Pencil className="h-3.5 w-3.5" />} onClick={() => { setEditingWebhook(row); setIsModalOpen(true); }}>Edit</Button>
        {deleteConfirm === row.id ? (
          <><Button variant="danger" size="sm" isLoading={deleteMutation.isPending} onClick={() => deleteMutation.mutate(row.id)}>Confirm</Button><Button variant="secondary" size="sm" onClick={() => setDeleteConfirm(null)}>Cancel</Button></>
        ) : (
          <Button variant="ghost" size="sm" className="text-red-600 hover:bg-red-50" leftIcon={<Trash2 className="h-3.5 w-3.5" />} onClick={() => setDeleteConfirm(row.id)}>Delete</Button>
        )}
      </div>
    )},
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold text-gray-900">Webhooks</h1><p className="text-sm text-gray-500 mt-1">Manage event notifications</p></div>
        <Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => setIsModalOpen(true)}>Add Webhook</Button>
      </div>
      <Card noPadding>
        <Table columns={columns} data={data} isLoading={isLoading} rowKey={(row) => row.id} emptyMessage="No webhooks configured." />
      </Card>
      <WebhookModal isOpen={isModalOpen} onClose={() => { setIsModalOpen(false); setEditingWebhook(null); }} editingWebhook={editingWebhook} />
    </div>
  );
};
export default WebhooksPage;
