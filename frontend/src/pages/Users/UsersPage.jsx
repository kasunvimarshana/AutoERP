import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate, useSearchParams, Link } from 'react-router-dom'
import { format } from 'date-fns'
import toast from 'react-hot-toast'
import { UserPlus, Pencil, Trash2, Users } from 'lucide-react'

import { usersApi } from '../../api/usersApi'
import SearchInput from '../../components/UI/SearchInput'
import Select from '../../components/UI/Select'
import Table from '../../components/UI/Table'
import Badge from '../../components/UI/Badge'
import Button from '../../components/UI/Button'
import Pagination from '../../components/UI/Pagination'

const ROLE_OPTIONS = [
  { value: '', label: 'All Roles' },
  { value: 'admin', label: 'Admin' },
  { value: 'manager', label: 'Manager' },
  { value: 'staff', label: 'Staff' },
  { value: 'viewer', label: 'Viewer' },
]

const ROLE_VARIANT = {
  admin: 'danger',
  manager: 'info',
  staff: 'success',
  viewer: 'default',
}

const SORT_OPTIONS = [
  { value: 'created_at', label: 'Created At' },
  { value: 'name', label: 'Name' },
  { value: 'email', label: 'Email' },
]

export default function UsersPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [searchParams, setSearchParams] = useSearchParams()

  const search = searchParams.get('search') ?? ''
  const role = searchParams.get('role') ?? ''
  const page = Number(searchParams.get('page') ?? 1)
  const sort = searchParams.get('sort') ?? 'created_at'
  const direction = searchParams.get('direction') ?? 'desc'

  const [deletingId, setDeletingId] = useState(null)

  function updateParam(key, value) {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev)
      if (value) {
        next.set(key, value)
      } else {
        next.delete(key)
      }
      if (key !== 'page') next.set('page', '1')
      return next
    })
  }

  const { data, isLoading } = useQuery({
    queryKey: ['users', { search, role, page, per_page: 15, sort, direction }],
    queryFn: () =>
      usersApi.getUsers({ search, role, page, per_page: 15, sort, direction }),
    keepPreviousData: true,
  })

  const users = data?.data?.data ?? data?.data ?? []
  const meta = data?.data?.meta ?? {}
  const totalPages = meta.last_page ?? meta.total_pages ?? 1
  const totalItems = meta.total ?? users.length

  const deleteMutation = useMutation({
    mutationFn: (id) => usersApi.deleteUser(id),
    onSuccess: () => {
      toast.success('User deleted successfully')
      queryClient.invalidateQueries({ queryKey: ['users'] })
      setDeletingId(null)
    },
    onError: (err) => {
      toast.error(err?.response?.data?.message ?? 'Failed to delete user')
      setDeletingId(null)
    },
  })

  function handleDelete(user) {
    if (!window.confirm(`Delete user "${user.name}"? This action cannot be undone.`)) return
    setDeletingId(user.id)
    deleteMutation.mutate(user.id)
  }

  const columns = [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    {
      key: 'role',
      label: 'Role',
      render: (val) => (
        <Badge variant={ROLE_VARIANT[val] ?? 'default'}>{val ?? '—'}</Badge>
      ),
    },
    {
      key: 'created_at',
      label: 'Created At',
      sortable: true,
      render: (val) => (val ? format(new Date(val), 'MMM d, yyyy') : '—'),
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (_, row) => (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => navigate(`/users/${row.id}/edit`)}
            title="Edit user"
          >
            <Pencil className="w-4 h-4" />
          </Button>
          <Button
            variant="danger"
            size="sm"
            loading={deletingId === row.id}
            onClick={() => handleDelete(row)}
            title="Delete user"
          >
            <Trash2 className="w-4 h-4" />
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <Users className="w-6 h-6 text-blue-600" />
            Users
          </h1>
          <p className="mt-1 text-sm text-gray-500">Manage system users and their roles</p>
        </div>
        <Button variant="primary" onClick={() => navigate('/users/create')}>
          <UserPlus className="w-4 h-4" />
          Create User
        </Button>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div className="flex flex-wrap gap-3">
          <SearchInput
            value={search}
            onChange={(v) => updateParam('search', v)}
            placeholder="Search by name or email…"
            className="flex-1 min-w-[200px]"
          />
          <Select
            name="role"
            value={role}
            options={ROLE_OPTIONS}
            onChange={(e) => updateParam('role', e.target.value)}
            className="w-44"
          />
          <Select
            name="sort"
            value={sort}
            options={SORT_OPTIONS}
            onChange={(e) => updateParam('sort', e.target.value)}
            className="w-40"
          />
          <Button
            variant="ghost"
            size="sm"
            onClick={() =>
              updateParam('direction', direction === 'asc' ? 'desc' : 'asc')
            }
            title={`Sort ${direction === 'asc' ? 'descending' : 'ascending'}`}
          >
            {direction === 'asc' ? '↑ Asc' : '↓ Desc'}
          </Button>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <Table
          columns={columns}
          data={users}
          loading={isLoading}
          sortKey={sort}
          sortDir={direction}
          onSort={(key, dir) => {
            updateParam('sort', key)
            updateParam('direction', dir)
          }}
          emptyMessage="No users found. Try adjusting your filters."
        />
        {!isLoading && (
          <div className="border-t border-gray-100 px-4">
            <Pagination
              currentPage={page}
              totalPages={totalPages}
              totalItems={totalItems}
              perPage={15}
              onPageChange={(p) => updateParam('page', String(p))}
            />
          </div>
        )}
      </div>
    </div>
  )
}
