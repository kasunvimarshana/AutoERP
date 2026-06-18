import { cleanup, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactElement } from 'react';
import { useState } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import WarehouseCreatePage from './WarehouseCreatePage';
import WarehouseDetailPage from './WarehouseDetailPage';
import WarehouseEditPage from './WarehouseEditPage';
import WarehouseListPage from './WarehouseListPage';
import WarehouseLocationCreatePage from './WarehouseLocationCreatePage';
import WarehouseLocationDetailPage from './WarehouseLocationDetailPage';
import WarehouseLocationEditPage from './WarehouseLocationEditPage';
import WarehouseLocationListPage from './WarehouseLocationListPage';
import { WarehouseLocationForm } from './components/WarehouseLocationForm';
import { warehousePermissions } from './warehousePermissions';
import type { WarehouseLocationPayload, WarehouseLocationSummary, WarehouseSummary } from './warehouseTypes';

const apiMocks = vi.hoisted(() => ({
    createWarehouse: vi.fn(),
    createWarehouseLocation: vi.fn(),
    getDefaultWarehouse: vi.fn(),
    getDefaultWarehouseLocation: vi.fn(),
    getWarehouse: vi.fn(),
    getWarehouseLocation: vi.fn(),
    listWarehouses: vi.fn(),
    listWarehouseLocations: vi.fn(),
    searchWarehouseOptions: vi.fn(),
    searchWarehouseLocationOptions: vi.fn(),
    setWarehouseActive: vi.fn(),
    setWarehouseLocationActive: vi.fn(),
    updateWarehouse: vi.fn(),
    updateWarehouseLocation: vi.fn(),
}));

const authState = vi.hoisted(() => ({
    permissions: [] as string[],
}));

vi.mock('./warehouseApi', () => apiMocks);

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({
        user: { id: 1, name: 'Warehouse User', email: 'warehouse@example.test' },
        token: 'token',
        tenant: { id: 1, name: 'Tenant' },
        organizationUnit: { id: 1, name: 'Head Office' },
        roles: [],
        permissions: authState.permissions,
        enabledModules: ['warehouse'],
        isAuthenticated: true,
        isLoading: false,
        login: vi.fn(),
        logout: vi.fn(),
        loadCurrentUser: vi.fn(),
    }),
}));

describe('Warehouse pages', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        authState.permissions = Object.values(warehousePermissions);
        apiMocks.listWarehouses.mockResolvedValue(collection([warehouse()]));
        apiMocks.getWarehouse.mockResolvedValue(warehouse({ locations: [location()] }));
        apiMocks.createWarehouse.mockResolvedValue(warehouse());
        apiMocks.updateWarehouse.mockResolvedValue(warehouse());
        apiMocks.getDefaultWarehouse.mockResolvedValue(null);
        apiMocks.getDefaultWarehouseLocation.mockResolvedValue(null);
        apiMocks.listWarehouseLocations.mockResolvedValue(collection([location()]));
        apiMocks.getWarehouseLocation.mockResolvedValue(location());
        apiMocks.createWarehouseLocation.mockResolvedValue(location());
        apiMocks.updateWarehouseLocation.mockResolvedValue(location());
        apiMocks.searchWarehouseOptions.mockResolvedValue(collection([warehouse(), warehouse({ id: 2, code: 'WH2', name: 'Warehouse Two' })]));
        apiMocks.searchWarehouseLocationOptions.mockResolvedValue(collection([location({ id: 20, code: 'PA', name: 'Parent A', path: '/pa' })]));
    });

    it('renders the requested route-level pages', async () => {
        const cases: Array<[ReactElement, string, string]> = [
            [<WarehouseListPage />, '/warehouses', 'Warehouses'],
            [<WarehouseCreatePage />, '/warehouses/create', 'Create Warehouse'],
            [<RoutePage path="/warehouses/:id/edit" page={<WarehouseEditPage />} />, '/warehouses/1/edit', 'Edit Main Warehouse'],
            [<RoutePage path="/warehouses/:id" page={<WarehouseDetailPage />} />, '/warehouses/1', 'WH1 - Main Warehouse'],
            [<WarehouseLocationListPage />, '/warehouse-locations', 'Warehouse Locations'],
            [<WarehouseLocationCreatePage />, '/warehouse-locations/create', 'Create Warehouse Location'],
            [<RoutePage path="/warehouse-locations/:id/edit" page={<WarehouseLocationEditPage />} />, '/warehouse-locations/10/edit', 'Edit Bin A'],
            [<RoutePage path="/warehouse-locations/:id" page={<WarehouseLocationDetailPage />} />, '/warehouse-locations/10', 'BIN-A - Bin A'],
        ];

        for (const [page, path, heading] of cases) {
            cleanup();
            renderPage(page, [path]);
            expect(await screen.findByRole('heading', { name: heading })).toBeInTheDocument();
        }
    });

    it('keeps detail pages read-only', async () => {
        renderPage(<RoutePage path="/warehouses/:id" page={<WarehouseDetailPage />} />, ['/warehouses/1']);

        expect(await screen.findByRole('heading', { name: 'WH1 - Main Warehouse' })).toBeInTheDocument();
        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /save|create|deactivate|activate/i })).not.toBeInTheDocument();

        cleanup();
        renderPage(<RoutePage path="/warehouse-locations/:id" page={<WarehouseLocationDetailPage />} />, ['/warehouse-locations/10']);

        expect(await screen.findByRole('heading', { name: 'BIN-A - Bin A' })).toBeInTheDocument();
        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /save|create|deactivate|activate/i })).not.toBeInTheDocument();
    });

    it('uses permission-aware list actions and server filters', async () => {
        authState.permissions = [warehousePermissions.warehousesView];
        const user = userEvent.setup();
        renderPage(<WarehouseListPage />, ['/warehouses']);

        expect(await screen.findAllByText('Main Warehouse')).not.toHaveLength(0);
        expect(screen.queryByRole('link', { name: 'Create Warehouse' })).not.toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Edit' })).not.toBeInTheDocument();

        await user.selectOptions(screen.getByLabelText('Status'), 'false');
        await waitFor(() => {
            expect(apiMocks.listWarehouses).toHaveBeenLastCalledWith(
                expect.objectContaining({ is_active: false, page: 1, per_page: 25 }),
                expect.any(AbortSignal),
            );
        });
    });

    it('normalizes default checkbox UX on Warehouse create', async () => {
        const user = userEvent.setup();
        renderPage(<WarehouseCreatePage />, ['/warehouses/create']);

        const active = screen.getByLabelText('Active');
        const defaultWarehouse = screen.getByLabelText('Default Warehouse');

        await user.click(defaultWarehouse);
        expect(active).toBeChecked();
        expect(defaultWarehouse).toBeChecked();

        await user.click(active);
        expect(active).not.toBeChecked();
        expect(defaultWarehouse).not.toBeChecked();
    });

    it('normalizes Location default UX and clears Parent Location when Warehouse changes', async () => {
        const user = userEvent.setup();
        renderPage(<LocationFormHarness />, ['/warehouse-location-form']);

        const defaultLocation = screen.getByLabelText('Default Location');
        const active = screen.getByLabelText('Active');
        await user.click(defaultLocation);
        expect(active).toBeChecked();
        await user.click(active);
        expect(defaultLocation).not.toBeChecked();

        await user.click(screen.getByLabelText('Warehouse'));
        await user.click(await screen.findByRole('option', { name: 'WH1 - Main Warehouse' }));
        await user.click(screen.getByLabelText('Parent Location'));
        await user.click(await screen.findByRole('option', { name: 'PA - Parent A (/pa)' }));
        expect(screen.getByLabelText('Parent Location')).toHaveValue('PA - Parent A (/pa)');

        await user.click(screen.getByLabelText('Warehouse'));
        await user.click(await screen.findByRole('option', { name: 'WH2 - Warehouse Two' }));
        expect(screen.getByLabelText('Parent Location')).toHaveValue('');
    });

    it('preselects default Warehouse only for an empty Location create form', async () => {
        apiMocks.getDefaultWarehouse.mockResolvedValue(warehouse());

        renderPage(<WarehouseLocationCreatePage />, ['/warehouse-locations/create']);

        await waitFor(() => {
            expect(screen.getByLabelText('Warehouse')).toHaveValue('WH1 - Main Warehouse');
        });
    });
});

function LocationFormHarness() {
    const [form, setForm] = useState<WarehouseLocationPayload>({
        warehouse_id: null,
        parent_id: null,
        name: '',
        code: '',
        type: 'bin',
        capacity: '',
        is_pickable: true,
        is_receivable: true,
        is_active: true,
        is_default: false,
    });
    const [selectedWarehouse, setSelectedWarehouse] = useState<WarehouseSummary | null>(null);
    const [parent, setParent] = useState<WarehouseLocationSummary | null>(null);

    return (
        <WarehouseLocationForm
            value={form}
            onChange={setForm}
            warehouse={selectedWarehouse}
            onWarehouseChange={setSelectedWarehouse}
            parent={parent}
            onParentChange={setParent}
            error={null}
        />
    );
}

function RoutePage({ page, path }: { page: ReactElement; path: string }) {
    return (
        <Routes>
            <Route path={path} element={page} />
        </Routes>
    );
}

function renderPage(page: ReactElement, initialEntries: string[]) {
    return render(
        <MemoryRouter initialEntries={initialEntries}>
            {page}
        </MemoryRouter>,
    );
}

function warehouse(overrides: Partial<WarehouseSummary> & { locations?: WarehouseLocationSummary[] } = {}): WarehouseSummary & { locations?: WarehouseLocationSummary[] } {
    return {
        id: 1,
        row_version: 1,
        code: 'WH1',
        name: 'Main Warehouse',
        type: 'standard',
        type_label: 'Standard',
        organization_unit: { id: 1, name: 'Head Office' },
        is_default: true,
        is_active: true,
        locations_count: overrides.locations?.length ?? 1,
        default_location: null,
        ...overrides,
    };
}

function location(overrides: Partial<WarehouseLocationSummary> = {}): WarehouseLocationSummary {
    return {
        id: 10,
        row_version: 1,
        code: 'BIN-A',
        name: 'Bin A',
        warehouse: warehouse(),
        parent: null,
        organization_unit: { id: 1, name: 'Head Office' },
        path: '/bin-a',
        depth: 0,
        type: 'bin',
        type_label: 'Bin',
        capacity: '10.000000',
        is_default: true,
        is_pickable: true,
        is_receivable: true,
        is_active: true,
        ...overrides,
    };
}

function collection<T>(data: T[]) {
    return {
        data,
        meta: { current_page: 1, from: data.length ? 1 : null, last_page: 1, per_page: 25, to: data.length, total: data.length },
    };
}
