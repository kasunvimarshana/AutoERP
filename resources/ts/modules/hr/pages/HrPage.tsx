import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

export function HrPage() {
    return (
        <ModulePlaceholderPage
            actions={[{ label: 'New Employee', path: '/hr/employees/new', variant: 'primary' }]}
            description="Employees, departments, designations, contacts, employment details, and optional user access. Employee creation does not automatically create a login user."
            sections={[
                { description: 'Employee profile, contact details, addresses, and employment record.', label: 'Employees', path: '/hr/employees', status: 'Ready' },
                { description: 'Departments and designations validated by backend.', label: 'Organization Setup', path: '/settings/organization-units', status: 'Mocked' },
                { description: 'Optional employee login linking and deactivation.', label: 'User Access', path: '/settings/users', status: 'Optional' },
                { description: 'Assignment and supervisor placeholders for service workflows.', label: 'Work Assignments', path: '/vehicle-service/job-cards', status: 'Mocked' },
            ]}
            title="HR"
        />
    );
}
