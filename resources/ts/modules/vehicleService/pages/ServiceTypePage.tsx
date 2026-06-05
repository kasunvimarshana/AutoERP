import { useEffect, useState, type FormEvent } from 'react';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { ServiceType } from '../types/vehicleService.types';

const empty = { code: '', description: '', isActive: true, name: '', standardHours: '' };

export function ServiceTypePage() {
    const [items, setItems] = useState<ServiceType[]>([]);
    const [form, setForm] = useState(empty);
    const [editingId, setEditingId] = useState<number>();
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => { void load(); }, []);
    async function load() { try { setItems((await vehicleServiceApi.listServiceTypes({ page: 1, perPage: 100 })).items); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to load service types.'); } }
    async function submit(event: FormEvent) {
        event.preventDefault(); setSaving(true); setError('');
        try {
            const input = { code: form.code || undefined, description: form.description || undefined, isActive: form.isActive, name: form.name, standardHours: form.standardHours || undefined };
            const saved = editingId ? await vehicleServiceApi.updateServiceType(editingId, input) : await vehicleServiceApi.createServiceType(input);
            setItems((current) => editingId ? current.map((item) => item.id === saved.id ? saved : item) : [...current, saved].sort((a, b) => a.name.localeCompare(b.name)));
            setForm(empty); setEditingId(undefined);
        } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to save service type.'); } finally { setSaving(false); }
    }
    async function remove(item: ServiceType) {
        if (!window.confirm(`Delete ${item.name}?`)) return;
        try { await vehicleServiceApi.removeServiceType(item.id); setItems((current) => current.filter((candidate) => candidate.id !== item.id)); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to delete service type.'); }
    }
    function edit(item: ServiceType) { setEditingId(item.id); setForm({ code: item.code || '', description: item.description || '', isActive: item.isActive, name: item.name, standardHours: item.standardHours || '' }); }

    return <div className="grid gap-5 lg:grid-cols-[360px_1fr]"><form className="space-y-4 rounded-xl border bg-white p-5 shadow-sm" onSubmit={(event) => void submit(event)}><div><p className="text-xs font-bold uppercase tracking-widest text-blue-600">Vehicle service</p><h1 className="mt-1 text-2xl font-bold">{editingId ? 'Edit service type' : 'New service type'}</h1></div>{error ? <Alert message={error} /> : null}<Field label="Name"><Input required value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} /></Field><Field label="Code"><Input value={form.code} onChange={(event) => setForm({ ...form, code: event.target.value })} /></Field><Field label="Standard hours"><Input min="0" step="0.0001" type="number" value={form.standardHours} onChange={(event) => setForm({ ...form, standardHours: event.target.value })} /></Field><Field label="Description"><textarea className="min-h-24 rounded-lg border p-3 text-sm" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} /></Field><label className="flex items-center gap-2 text-sm font-semibold"><input checked={form.isActive} onChange={(event) => setForm({ ...form, isActive: event.target.checked })} type="checkbox" /> Active</label><div className="flex gap-2"><Button disabled={saving} type="submit">{saving ? 'Saving...' : 'Save'}</Button>{editingId ? <Button onClick={() => { setEditingId(undefined); setForm(empty); }} variant="secondary">Cancel</Button> : null}</div></form><section className="overflow-hidden rounded-xl border bg-white shadow-sm"><div className="border-b p-5"><h2 className="text-xl font-bold">Service types</h2></div>{items.length ? <table className="w-full text-left text-sm"><thead className="bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Name</th><th className="px-4 py-3">Code</th><th className="px-4 py-3">Hours</th><th className="px-4 py-3">Status</th><th /></tr></thead><tbody>{items.map((item) => <tr className="border-t" key={item.id}><td className="px-4 py-3 font-semibold">{item.name}</td><td className="px-4 py-3">{item.code || '-'}</td><td className="px-4 py-3">{item.standardHours || '-'}</td><td className="px-4 py-3">{item.isActive ? 'Active' : 'Inactive'}</td><td className="px-4 py-3 text-right"><button className="mr-3 font-semibold text-blue-700" onClick={() => edit(item)} type="button">Edit</button><button className="font-semibold text-red-600" onClick={() => void remove(item)} type="button">Delete</button></td></tr>)}</tbody></table> : <p className="p-10 text-center text-sm text-slate-500">No service types yet.</p>}</section></div>;
}

function Field({ children, label }: { children: React.ReactNode; label: string }) { return <label className="grid gap-1 text-sm font-semibold text-slate-700"><span>{label}</span>{children}</label>; }
function Alert({ message }: { message: string }) { return <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{message}</div>; }
