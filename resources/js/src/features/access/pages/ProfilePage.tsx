import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { SectionCard } from '../../../components/forms/SectionCard';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { ActionBar } from '../../../components/forms/ActionBar';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { buildAddressPayload, changePasswordSchema, normalizeAddressDefaults, profileFormSchema, type ChangePasswordFormInput, type ChangePasswordFormValues, type ProfileFormInput, type ProfileFormValues } from '../schemas';
import { useChangePassword, useProfile, useUpdateProfile } from '../hooks';

export function ProfilePage() {
    const { showToast } = useToast();
    const [generalFormError, setGeneralFormError] = useState<string | null>(null);
    const [passwordFormError, setPasswordFormError] = useState<string | null>(null);
    const profileQuery = useProfile();
    const updateProfileMutation = useUpdateProfile();
    const changePasswordMutation = useChangePassword();

    const profileForm = useForm<ProfileFormInput, unknown, ProfileFormValues>({
        resolver: zodResolver(profileFormSchema),
        defaultValues: {
            first_name: '',
            last_name: '',
            phone: '',
            address_line1: '',
            address_line2: '',
            city: '',
            state: '',
            postal_code: '',
            country: '',
        },
    });
    const passwordForm = useForm<ChangePasswordFormInput, unknown, ChangePasswordFormValues>({
        resolver: zodResolver(changePasswordSchema),
        defaultValues: {
            current_password: '',
            password: '',
            password_confirmation: '',
        },
    });

    useEffect(() => {
        if (!profileQuery.data) {
            return;
        }

        profileForm.reset({
            first_name: profileQuery.data.first_name ?? '',
            last_name: profileQuery.data.last_name ?? '',
            phone: profileQuery.data.phone ?? '',
            ...normalizeAddressDefaults(profileQuery.data.address),
        });
    }, [profileForm, profileQuery.data]);

    async function handleProfileSubmit(values: ProfileFormValues) {
        setGeneralFormError(null);

        try {
            const profile = await updateProfileMutation.mutateAsync({
                first_name: values.first_name,
                last_name: values.last_name,
                phone: values.phone ?? null,
                address: buildAddressPayload(values),
            });

            showToast({
                title: 'Profile updated',
                description: `${profile.full_name ?? profile.email ?? 'Your profile'} has been updated successfully.`,
                tone: 'success',
            });
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, profileForm.setError, {
                    onUnhandled: (message) => setGeneralFormError(message),
                });
                return;
            }

            setGeneralFormError(error instanceof Error ? error.message : 'Unable to update profile.');
        }
    }

    async function handlePasswordSubmit(values: ChangePasswordFormValues) {
        setPasswordFormError(null);

        try {
            await changePasswordMutation.mutateAsync(values);
            passwordForm.reset();
            showToast({
                title: 'Password changed',
                description: 'Your password was updated successfully.',
                tone: 'success',
            });
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, passwordForm.setError, {
                    onUnhandled: (message) => setPasswordFormError(message),
                });
                return;
            }

            setPasswordFormError(error instanceof Error ? error.message : 'Unable to change password.');
        }
    }

    if (profileQuery.isPending) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <LoadingState lines={8} />
            </div>
        );
    }

    if (profileQuery.isError) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                {isForbiddenError(profileQuery.error) ? (
                    <ProtectedErrorState description={profileQuery.error.message} />
                ) : (
                    <ErrorState description={profileQuery.error.message} title="Unable to load profile" />
                )}
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Users & Access' }, { label: 'Profile' }]}
                description="Profile and password maintenance stay inside the same admin shell so account updates and security actions feel consistent with the rest of the application."
                title="Profile"
            />

            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <ContentCard>
                    <form onSubmit={profileForm.handleSubmit(handleProfileSubmit)}>
                        <div className="space-y-6">
                            <SectionCard description="General profile fields are updated through the dedicated profile endpoint for the authenticated user." title="General profile">
                                <FormGrid>
                                    <FormField error={profileForm.formState.errors.first_name?.message} label="First Name" required>
                                        <Input error={profileForm.formState.errors.first_name?.message} {...profileForm.register('first_name')} />
                                    </FormField>
                                    <FormField error={profileForm.formState.errors.last_name?.message} label="Last Name" required>
                                        <Input error={profileForm.formState.errors.last_name?.message} {...profileForm.register('last_name')} />
                                    </FormField>
                                    <FormField error={profileForm.formState.errors.phone?.message} label="Phone">
                                        <Input error={profileForm.formState.errors.phone?.message} {...profileForm.register('phone')} />
                                    </FormField>
                                </FormGrid>
                            </SectionCard>

                            <SectionCard description="Structured address fields are kept here so the profile contract can stay aligned with the shared user payload shape." title="Address">
                                <FormGrid>
                                    <FormField error={profileForm.formState.errors.address_line1?.message} label="Address Line 1">
                                        <Input error={profileForm.formState.errors.address_line1?.message} {...profileForm.register('address_line1')} />
                                    </FormField>
                                    <FormField error={profileForm.formState.errors.address_line2?.message} label="Address Line 2">
                                        <Input error={profileForm.formState.errors.address_line2?.message} {...profileForm.register('address_line2')} />
                                    </FormField>
                                    <FormField error={profileForm.formState.errors.city?.message} label="City">
                                        <Input error={profileForm.formState.errors.city?.message} {...profileForm.register('city')} />
                                    </FormField>
                                    <FormField error={profileForm.formState.errors.state?.message} label="State / Province">
                                        <Input error={profileForm.formState.errors.state?.message} {...profileForm.register('state')} />
                                    </FormField>
                                    <FormField error={profileForm.formState.errors.postal_code?.message} label="Postal Code">
                                        <Input error={profileForm.formState.errors.postal_code?.message} {...profileForm.register('postal_code')} />
                                    </FormField>
                                    <FormField error={profileForm.formState.errors.country?.message} label="Country">
                                        <Input error={profileForm.formState.errors.country?.message} {...profileForm.register('country')} />
                                    </FormField>
                                </FormGrid>
                            </SectionCard>

                            {generalFormError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{generalFormError}</div> : null}

                            <ActionBar>
                                <Button type="submit">{updateProfileMutation.isPending ? 'Saving...' : 'Save Profile'}</Button>
                            </ActionBar>
                        </div>
                    </form>
                </ContentCard>

                <ContentCard>
                    <form onSubmit={passwordForm.handleSubmit(handlePasswordSubmit)}>
                        <div className="space-y-6">
                            <SectionCard description="Password changes use the dedicated authenticated endpoint and follow the shared form pattern instead of a detached modal flow." title="Change password">
                                <div className="space-y-4">
                                    <FormField error={passwordForm.formState.errors.current_password?.message} label="Current Password" required>
                                        <Input error={passwordForm.formState.errors.current_password?.message} type="password" {...passwordForm.register('current_password')} />
                                    </FormField>
                                    <FormField error={passwordForm.formState.errors.password?.message} label="New Password" required>
                                        <Input error={passwordForm.formState.errors.password?.message} type="password" {...passwordForm.register('password')} />
                                    </FormField>
                                    <FormField error={passwordForm.formState.errors.password_confirmation?.message} label="Confirm Password" required>
                                        <Input error={passwordForm.formState.errors.password_confirmation?.message} type="password" {...passwordForm.register('password_confirmation')} />
                                    </FormField>
                                </div>
                            </SectionCard>

                            {passwordFormError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{passwordFormError}</div> : null}

                            <ActionBar>
                                <Button type="submit">{changePasswordMutation.isPending ? 'Updating...' : 'Change Password'}</Button>
                            </ActionBar>
                        </div>
                    </form>
                </ContentCard>
            </div>
        </div>
    );
}
