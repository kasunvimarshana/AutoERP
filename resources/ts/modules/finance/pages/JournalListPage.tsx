import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { journalApi } from '../services/journalApi';
import type { JournalPage } from '../types/journal.types';

export function JournalListPage() {
    const [pageData, setPageData] = useState<JournalPage | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    useEffect(() => { const timer = window.setTimeout(() => { setPage(1); setSearch(searchInput.trim()); }, 350); return () => window.clearTimeout(timer); }, [searchInput]);
    useEffect(() => { let active = true; setLoading(true); setError(''); void journalApi.list({ page, perPage: 20, search: search || undefined }).then((response) => { if (active) setPageData(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load journals.'); }).finally(() => { if (active) setLoading(false); }); return () => { active = false; }; }, [page, search]);
    return <div className="space-y-5"><header><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Finance</p><h1 className="text-3xl font-bold">Journal entries</h1></header><div className="rounded-xl border bg-white p-4"><Input placeholder="Search journal number or source reference" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} /></div>{error ? <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div> : null}<div className="overflow-hidden rounded-xl border bg-white">{loading ? <div className="flex justify-center p-12"><Spinner /></div> : pageData?.entries.length ? <table className="w-full text-left text-sm"><thead className="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Number</th><th>Date</th><th>Source</th><th>Debit</th><th>Credit</th><th>Status</th><th></th></tr></thead><tbody className="divide-y">{pageData.entries.map((entry) => <tr key={entry.id}><td className="px-4 py-4 font-semibold">{entry.entryNumber}</td><td>{entry.entryDate}</td><td>{entry.sourceReference || entry.sourceModule || 'Manual'}</td><td>{Number(entry.totalDebit).toLocaleString()}</td><td>{Number(entry.totalCredit).toLocaleString()}</td><td>{entry.status}</td><td><Link className="font-semibold text-blue-700" to={`/finance/journals/${entry.id}`}>View</Link></td></tr>)}</tbody></table> : <div className="p-12 text-center text-sm text-slate-500">No journal entries found.</div>}</div>{pageData ? <div className="flex justify-end gap-2"><Button disabled={pageData.meta.currentPage <= 1 || loading} onClick={() => setPage((value) => value - 1)} variant="secondary">Previous</Button><Button disabled={pageData.meta.currentPage >= pageData.meta.lastPage || loading} onClick={() => setPage((value) => value + 1)} variant="secondary">Next</Button></div> : null}</div>;
}
