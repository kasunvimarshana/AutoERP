import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { webhooksApi } from '../../api/webhooks';
import { Webhook } from '../../types';
import Modal from '../../components/ui/Modal';
import Input from '../../components/ui/Input';
import Button from '../../components/ui/Button';
import toast from 'react-hot-toast';

const AVAILABLE_EVENTS = ['order.created','order.updated','order.cancelled','inventory.low_stock','inventory.adjusted','product.created','product.updated','user.created'];

const schema = z.object({
  name: z.string().min(1, 'Required'),
  url: z.string().url('Must be a valid URL'),
  events: z.array(z.string()).min(1, 'Select at least one event'),
  is_active: z.boolean(),
});
type FormData = z.infer<typeof schema>;

interface Props { isOpen: boolean; onClose: () => void; editingWebhook: Webhook | null; }

const WebhookModal: React.FC<Props> = ({ isOpen, onClose, editingWebhook }) => {
  const queryClient = useQueryClient();
  const isEdit = !!editingWebhook;
  const { register, handleSubmit, reset, watch, setValue, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', url: '', events: [], is_active: true },
  });
  const selectedEvents = watch('events');

  useEffect(() => {
    if (editingWebhook) reset({ name: editingWebhook.name, url: editingWebhook.url, events: editingWebhook.events, is_active: editingWebhook.is_active });
    else reset({ name: '', url: '', events: [], is_active: true });
  }, [editingWebhook, reset]);

  const toggleEvent = (event: string) => {
    const current = selectedEvents || [];
    setValue('events', current.includes(event) ? current.filter(e => e !== event) : [...current, event]);
  };

  const createMutation = useMutation({ mutationFn: webhooksApi.create, onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['webhooks'] }); toast.success('Webhook created'); onClose(); }, onError: (e: any) => toast.error(e.response?.data?.message || 'Failed') });
  const updateMutation = useMutation({ mutationFn: (d: FormData) => webhooksApi.update(editingWebhook!.id, d), onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['webhooks'] }); toast.success('Webhook updated'); onClose(); }, onError: (e: any) => toast.error(e.response?.data?.message || 'Failed') });
  const onSubmit = (d: FormData) => isEdit ? updateMutation.mutate(d) : createMutation.mutate(d);
  const isPending = createMutation.isPending || updateMutation.isPending;

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={isEdit ? 'Edit Webhook' : 'Add Webhook'} size="lg"
      footer={<><Button variant="outline" onClick={onClose} disabled={isPending}>Cancel</Button><Button onClick={handleSubmit(onSubmit)} isLoading={isPending}>{isEdit ? 'Save' : 'Create'}</Button></>}>
      <form className="space-y-4" onSubmit={handleSubmit(onSubmit)}>
        <Input label="Name" required error={errors.name?.message} {...register('name')} />
        <Input label="Endpoint URL" type="url" placeholder="https://your-server.com/webhook" required error={errors.url?.message} {...register('url')} />
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">Events <span className="text-red-500">*</span></label>
          <div className="grid grid-cols-2 gap-2">
            {AVAILABLE_EVENTS.map(event => (
              <label key={event} className="flex items-center gap-2 cursor-pointer p-2 rounded-lg hover:bg-gray-50 border border-gray-100">
                <input type="checkbox" checked={selectedEvents?.includes(event) || false} onChange={() => toggleEvent(event)} className="rounded text-blue-600 focus:ring-blue-500" />
                <span className="text-sm text-gray-700 font-mono">{event}</span>
              </label>
            ))}
          </div>
          {errors.events?.message && <p className="mt-1 text-xs text-red-600">{errors.events.message}</p>}
        </div>
        <label className="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" {...register('is_active')} className="rounded text-blue-600 focus:ring-blue-500" />
          <span className="text-sm font-medium text-gray-700">Active</span>
        </label>
      </form>
    </Modal>
  );
};
export default WebhookModal;
