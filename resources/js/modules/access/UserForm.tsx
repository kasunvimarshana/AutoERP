import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';

export interface UserFormState {
    first_name: string;
    last_name: string;
    username: string;
    email: string;
    phone: string;
    row_version?: number;
}

export function emptyUserForm(): UserFormState {
    return {
        first_name: '',
        last_name: '',
        username: '',
        email: '',
        phone: '',
    };
}

export function UserForm({
    value,
    error,
    emailReadOnly = false,
    onChange,
}: {
    value: UserFormState;
    error: ApiError | null;
    emailReadOnly?: boolean;
    onChange: (value: UserFormState) => void;
}) {
    const set = (patch: Partial<UserFormState>) => onChange({ ...value, ...patch });

    return (
        <Panel title="User profile">
            <div className="grid gap-4 md:grid-cols-2">
                <Input label="First name" required value={value.first_name} error={fieldError(error, 'first_name')} onChange={(event) => set({ first_name: event.target.value })} />
                <Input label="Last name" value={value.last_name} error={fieldError(error, 'last_name')} onChange={(event) => set({ last_name: event.target.value })} />
                <Input label="Username" value={value.username} hint="Optional sign-in name. Use lowercase letters, numbers, dots, underscores, or hyphens." error={fieldError(error, 'username')} onChange={(event) => set({ username: event.target.value.toLowerCase() })} />
                <Input
                    label="Email"
                    type="email"
                    required
                    readOnly={emailReadOnly}
                    className={emailReadOnly ? 'bg-slate-50 text-slate-600' : ''}
                    hint={emailReadOnly ? 'Email changes require a dedicated verified-email workflow and cannot be changed here.' : 'Used for sign-in and account communication.'}
                    value={value.email}
                    error={fieldError(error, 'email')}
                    onChange={(event) => set({ email: event.target.value })}
                />
                <Input label="Phone" value={value.phone} error={fieldError(error, 'phone')} onChange={(event) => set({ phone: event.target.value })} />
            </div>
        </Panel>
    );
}
