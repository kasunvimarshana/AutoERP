<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'inactive' => 'Your account is inactive. Please contact support.',
    'locked' => 'Your account is locked. Please try again later or contact support.',
    'locked_duration' => 'Your account is locked. Please try again in :minutes minutes.',
    
    // Registration
    'registration_successful' => 'Registration successful! Welcome to :app.',
    'registration_failed' => 'Registration failed. Please try again.',
    
    // Login
    'login_successful' => 'Login successful! Welcome back.',
    'login_failed' => 'Login failed. Please check your credentials.',
    'requires_mfa' => 'Multi-factor authentication required.',
    'mfa_code_invalid' => 'The provided MFA code is incorrect.',
    
    // Logout
    'logout_successful' => 'Logout successful. See you soon!',
    
    // Password
    'password_changed' => 'Password changed successfully. Please login again.',
    'password_change_failed' => 'Failed to change password.',
    'current_password_incorrect' => 'The current password is incorrect.',
    'password_reset_requested' => 'If an account exists with this email, you will receive password reset instructions.',
    'password_reset_successful' => 'Password reset successfully. Please login with your new password.',
    'password_reset_failed' => 'Password reset failed. The link may be expired or invalid.',
    'password_reset_token_invalid' => 'Invalid or expired reset token.',
    
    // MFA
    'mfa_enabled' => 'Multi-factor authentication enabled successfully.',
    'mfa_disabled' => 'Multi-factor authentication disabled.',
    'mfa_setup_required' => 'Please complete MFA setup.',
    'mfa_verified' => 'MFA code verified successfully.',
    
    // Email Verification
    'email_verified' => 'Email verified successfully.',
    'email_verification_required' => 'Please verify your email address before continuing.',
    
    // Account Status
    'account_locked' => 'Account has been locked due to security reasons.',
    'account_unlocked' => 'Account has been unlocked successfully.',
    
    // Token
    'token_refreshed' => 'Token refreshed successfully.',
    'token_invalid' => 'Invalid or expired token.',
    'token_missing' => 'Authentication token is missing.',
    
    // Permissions
    'unauthorized' => 'You are not authorized to perform this action.',
    'forbidden' => 'Access forbidden.',
    
    // Tenant
    'tenant_inactive' => 'Tenant is inactive.',
    'tenant_subscription_expired' => 'Tenant subscription has expired.',
    'no_tenant_assigned' => 'No tenant assigned to user.',
];
