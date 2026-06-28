import { useState, type ComponentType } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { ApiCollection, ListParams } from '@/shared/types/api';
import type { PartyVehicleRelationship } from '@/shared/types/partyVehicle';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';

interface LookupProps<T> { value: T | null; onChange: (value: T | null) => void }
export function PartyVehicleListPage<P extends { id: number }, V extends { id: number }>({ partyKey, title, createPath, editPath, permissions, PartyLookup, VehicleLookup, list, setCurrent, clearCurrent, end }: {
    partyKey: 'customer' | 'supplier'; title: string; createPath: string; editPath: (id: number) => string;
    permissions: { create: string; update: string; setCurrent: string; clearCurrent: string; delete: string };
    PartyLookup: ComponentType<LookupProps<P>>; VehicleLookup: ComponentType<LookupProps<V>>;
    list: (params: ListParams, signal?: AbortSignal) => Promise<ApiCollection<PartyVehicleRelationship>>;
    setCurrent: (id: number, expectedVersion: number) => Promise<PartyVehicleRelationship>; clearCurrent: (id: number, expectedVersion: number) => Promise<PartyVehicleRelationship>; end: (id: number, expectedVersion: number) => Promise<PartyVehicleRelationship>;
}) {
    const auth = useAuth(); const can = (permission: string) => hasPermission(auth, permission);
    const [search,setSearch]=useState(''); const [party,setParty]=useState<P|null>(null); const [vehicle,setVehicle]=useState<V|null>(null); const [current,setCurrentFilter]=useState(''); const [status,setStatus]=useState(''); const [sort,setSort]=useState('started_at'); const [direction,setDirection]=useState('desc'); const [page,setPage]=useState(1); const [actionError,setActionError]=useState<ApiError|null>(null); const [busy,setBusy]=useState<number|null>(null); const debounced=useDebounce(search); const {confirm,confirmDialog}=useConfirmDialog();
    const result=useApi((signal)=>list({search:debounced,[`${partyKey}_id`]:party?.id,vehicle_id:vehicle?.id,is_current:current===''?undefined:current==='true',status:status||undefined,sort,direction,page,per_page:25},signal),[debounced,party?.id,vehicle?.id,current,status,sort,direction,page]);
    const mutate=async(row:PartyVehicleRelationship,action:'set'|'clear'|'end')=>{const labels={set:'set this relationship as current',clear:'clear the current relationship',end:'end this relationship'};if(!await confirm({title:`Confirm ${action}`,message:`Are you sure you want to ${labels[action]}?`,confirmLabel:action==='end'?'End relationship':'Confirm',danger:action!=='set'}))return;setBusy(row.id);setActionError(null);try{if(action==='set')await setCurrent(row.id,row.row_version);else if(action==='clear')await clearCurrent(row.id,row.row_version);else await end(row.id,row.row_version);result.reload();}catch(error){setActionError(toApiError(error));}finally{setBusy(null);}};
    const columns:DataColumn<PartyVehicleRelationship>[]=[
        {key:'party',header:partyKey==='customer'?'Customer':'Supplier',render:(row)=>{const p=row[partyKey];return p?<><div className="font-semibold text-slate-900">{p.name}</div><div className="text-xs text-slate-500">{p.code}</div></>:'-';}},
        {key:'vehicle',header:'Vehicle',render:(row)=><><div className="font-semibold text-slate-900">{row.vehicle.registration_number??row.vehicle.number}</div><div className="text-xs text-slate-500">{[row.vehicle.make,row.vehicle.model,row.vehicle.chassis_number].filter(Boolean).join(' · ')}</div></>},
        {key:'current',header:'Current',render:(row)=><StatusBadge status={row.is_current?'current':row.ended_at?'ended':'active'} />},
        {key:'dates',header:'Dates',render:(row)=><span>{row.started_at.slice(0,10)} — {row.ended_at?.slice(0,10)??'Open'}</span>},
        {key:'organization',header:'Organization',render:(row)=>row.organization?.name??'Global'},
        {key:'actions',header:'',className:'text-right',render:(row)=><div className="flex justify-end gap-2">{can(permissions.update)&&<Link className="font-semibold text-sky-700" to={editPath(row.id)}>Edit</Link>}{!row.is_current&&can(permissions.setCurrent)&&!row.ended_at&&<Button variant="ghost" disabled={busy===row.id} onClick={()=>void mutate(row,'set')}>Set Current</Button>}{row.is_current&&can(permissions.clearCurrent)&&<Button variant="ghost" disabled={busy===row.id} onClick={()=>void mutate(row,'clear')}>Clear</Button>}{!row.ended_at&&can(permissions.delete)&&<Button variant="ghost" disabled={busy===row.id} onClick={()=>void mutate(row,'end')}>End</Button>}</div>},
    ];
    return <><ContentHeader title={title} description={`Manage current and historical ${partyKey} vehicle relationships.`} actions={can(permissions.create)?<LinkButton to={createPath}>Create relationship</LinkButton>:undefined}/><div className="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4"><Input type="search" label="Search" value={search} onChange={(e)=>{setSearch(e.target.value);setPage(1)}} placeholder="Party, registration, chassis"/><PartyLookup value={party} onChange={(v)=>{setParty(v);setPage(1)}}/><VehicleLookup value={vehicle} onChange={(v)=>{setVehicle(v);setPage(1)}}/><Select label="Current status" value={current} onChange={(e)=>{setCurrentFilter(e.target.value);setPage(1)}} options={[{value:'true',label:'Current'},{value:'false',label:'Not current'}]}/><Select label="Relationship status" value={status} onChange={(e)=>{setStatus(e.target.value);setPage(1)}} options={[{value:'active',label:'Active'},{value:'ended',label:'Ended'}]}/><Select label="Sort" value={sort} onChange={(e)=>setSort(e.target.value)} options={[{value:'started_at',label:'Start date'},{value:'ended_at',label:'End date'},{value:'created_at',label:'Created'}]}/><Select label="Direction" value={direction} onChange={(e)=>setDirection(e.target.value)} options={[{value:'desc',label:'Newest first'},{value:'asc',label:'Oldest first'}]}/></div><ErrorAlert error={actionError??result.error}/>{result.loading?<LoadingState/>:<DataTable rows={result.data?.data??[]} columns={columns} rowKey={(row)=>row.id} emptyMessage={`No ${partyKey} vehicle relationships found.`}/>}<Pagination meta={result.data?.meta} onPageChange={setPage}/>{confirmDialog}</>;
}
