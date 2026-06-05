import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { paymentApi } from '../services/paymentApi';
import type { PaymentDirection, PaymentPage } from '../types/payment.types';

export function PaymentListPage() {
    const [pageData, setPageData] = useState<PaymentPage | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [direction, setDirection] = useState<PaymentDirection | ''>('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    useEffect(() => { const timer = window.setTimeout(() => { setPage(1); setSearch(searchInput.trim()); }, 350); return () => window.clearTimeout(timer); }, [searchInput]);
    useEffect(() => { let active = true; setLoading(true); setError(''); void paymentApi.list({ direction: direction || undefined, page, perPage: 20, search: search || undefined }).then((response) => { if (active) setPageData(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load payments.'); }).finally(() => { if (active) setLoading(false); }); return () => { active = false; }; }, [direction, page, search]);
    return <div className="space-y-5"><header className="flex justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Settlement</p><h1 className="text-3xl font-bold">Payments</h1></div><Link className="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white" to="/payments/new">Create payment</Link></header><div className="grid gap-3 rounded-xl border bg-white p-4 sm:grid-cols-[1fr_180px]"><Input placeholder="Search payment number" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} /><select className="h-11 rounded-lg border px-3 text-sm" value={direction} onChange={(event) => { setPage(1); setDirection(event.target.value as PaymentDirection | ''); }}><option value="">All directions</option><option value="inbound">Inbound</option><option value="outbound">Outbound</option></select></div>{error ? <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div> : null}<div className="overflow-hidden rounded-xl border bg-white">{loading ? <div className="flex justify-center p-12"><Spinner /></div> : pageData?.payments.length ? <table className="w-full text-left text-sm"><thead className="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Number</th><th>Date</th><th>Direction</th><th>Amount</th><th>Allocated</th><th>Status</th><th></th></tr></thead><tbody className="divide-y">{pageData.payments.map((payment) => <tr key={payment.id}><td className="px-4 py-4 font-semibold">{payment.paymentNumber}</td><td>{payment.paymentDate}</td><td>{payment.direction}</td><td>{Number(payment.amount).toLocaleString()}</td><td>{Number(payment.allocatedAmount).toLocaleString()}</td><td>{payment.status}</td><td><Link className="font-semibold text-blue-700" to={`/payments/${payment.id}`}>View</Link></td></tr>)}</tbody></table> : <div className="p-12 text-center text-sm text-slate-500">No payments found.</div>}</div>{pageData ? <div className="flex justify-end gap-2"><Button disabled={pageData.meta.currentPage <= 1 || loading} onClick={() => setPage((value) => value - 1)} variant="secondary">Previous</Button><Button disabled={pageData.meta.currentPage >= pageData.meta.lastPage || loading} onClick={() => setPage((value) => value + 1)} variant="secondary">Next</Button></div> : null}</div>;
}
