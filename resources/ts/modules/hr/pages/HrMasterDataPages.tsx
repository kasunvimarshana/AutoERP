import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import {
    DepartmentForm,
    DepartmentTable,
    DepartmentTreeView,
    DesignationForm,
    DesignationTable,
    EmploymentTypeForm,
    EmploymentTypeTable,
    HrPageHeader,
} from '../components/HrComponents';
import { hrApi } from '../services/hrApi';
import type { Department, Designation, EmploymentType } from '../types/hr.types';

function useDepartments() {
    const [departments, setDepartments] = useState<Department[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;

        hrApi.departments
            .list()
            .then((response) => {
                if (mounted) {
                    setDepartments(response.data);
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load departments.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, []);

    return { departments, error, isLoading };
}

export function DepartmentListPage() {
    const { departments, error, isLoading } = useDepartments();

    return (
        <div className="space-y-6">
            <HrPageHeader actions={<Link to="/hr/departments/new"><Button>New Department</Button></Link>} subtitle="Departments are HR master data used by employee profiles and lookups." title="Departments" />
            <Card className="p-4"><div className="grid gap-3 md:grid-cols-[1fr_160px]"><Input placeholder="Search department..." /><Button variant="secondary">Filter</Button></div></Card>
            {isLoading ? <EmptyState description="Loading departments from the HR API..." title="Loading departments" /> : null}
            {error ? <EmptyState description={error} title="Unable to load departments" /> : null}
            {!isLoading && !error ? <div className="grid gap-5 xl:grid-cols-[1fr_360px]"><DepartmentTable rows={departments} /><DepartmentTreeView rows={departments} /></div> : null}
        </div>
    );
}

export function DepartmentCreatePage() {
    const { departments, error, isLoading } = useDepartments();

    if (isLoading) {
        return <EmptyState description="Loading existing departments..." title="Loading departments" />;
    }

    if (error) {
        return <EmptyState description={error} title="Unable to create department" />;
    }

    return <div className="space-y-6"><HrPageHeader subtitle="Create HR department master data." title="New Department" /><DepartmentForm departments={departments} mode="create" /></div>;
}

export function DepartmentEditPage() {
    const { id = '' } = useParams();
    const { departments, error: departmentsError, isLoading: departmentsLoading } = useDepartments();
    const [department, setDepartment] = useState<Department | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;

        hrApi.departments
            .get(id)
            .then((response) => {
                if (mounted) {
                    setDepartment(response.data);
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load department.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [id]);

    if (isLoading || departmentsLoading) {
        return <EmptyState description="Loading department master data..." title="Loading department" />;
    }

    if (error || departmentsError || !department) {
        return <EmptyState description={error || departmentsError || 'Department was not found.'} title="Unable to edit department" />;
    }

    return <div className="space-y-6"><HrPageHeader subtitle="Edit department master data and manager reference after backend validation." title={`Edit ${department.name}`} /><DepartmentForm department={department} departments={departments} mode="edit" /></div>;
}

export function DesignationListPage() {
    const [rows, setRows] = useState<Designation[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;

        hrApi.designations
            .list()
            .then((response) => {
                if (mounted) {
                    setRows(response.data);
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load designations.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader actions={<Link to="/hr/designations/new"><Button>New Designation</Button></Link>} subtitle="Designations support technician, driver, supervisor, and future staff categories." title="Designations" />
            {isLoading ? <EmptyState description="Loading designations from the HR API..." title="Loading designations" /> : null}
            {error ? <EmptyState description={error} title="Unable to load designations" /> : null}
            {!isLoading && !error ? <DesignationTable rows={rows} /> : null}
        </div>
    );
}

export function DesignationCreatePage() {
    const { departments, error, isLoading } = useDepartments();

    if (isLoading) {
        return <EmptyState description="Loading departments for designation setup..." title="Loading departments" />;
    }

    if (error) {
        return <EmptyState description={error} title="Unable to create designation" />;
    }

    return <div className="space-y-6"><HrPageHeader subtitle="Create designation master data." title="New Designation" /><DesignationForm departments={departments} mode="create" /></div>;
}

export function DesignationEditPage() {
    const { id = '' } = useParams();
    const { departments, error: departmentsError, isLoading: departmentsLoading } = useDepartments();
    const [designation, setDesignation] = useState<Designation | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;

        hrApi.designations
            .get(id)
            .then((response) => {
                if (mounted) {
                    setDesignation(response.data);
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load designation.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [id]);

    if (isLoading || departmentsLoading) {
        return <EmptyState description="Loading designation master data..." title="Loading designation" />;
    }

    if (error || departmentsError || !designation) {
        return <EmptyState description={error || departmentsError || 'Designation was not found.'} title="Unable to edit designation" />;
    }

    return <div className="space-y-6"><HrPageHeader subtitle="Edit designation master data." title={`Edit ${designation.name}`} /><DesignationForm departments={departments} designation={designation} mode="edit" /></div>;
}

export function EmploymentTypeListPage() {
    const [rows, setRows] = useState<EmploymentType[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    function loadEmploymentTypes() {
        setIsLoading(true);
        setError('');
        hrApi.employmentTypes
            .list()
            .then((response) => setRows(response.data))
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load employment types.'))
            .finally(() => setIsLoading(false));
    }

    useEffect(() => {
        loadEmploymentTypes();
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader subtitle="Employment types are reusable HR setup for employee profiles." title="Employment Types" />
            <EmploymentTypeForm onSaved={() => loadEmploymentTypes()} />
            {isLoading ? <EmptyState description="Loading employment types from the HR API..." title="Loading employment types" /> : null}
            {error ? <EmptyState description={error} title="Unable to load employment types" /> : null}
            {!isLoading && !error ? <EmploymentTypeTable rows={rows} /> : null}
        </div>
    );
}
