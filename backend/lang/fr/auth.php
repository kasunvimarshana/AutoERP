<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines (French)
    |--------------------------------------------------------------------------
    */

    'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
    'password' => 'Le mot de passe fourni est incorrect.',
    'throttle' => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
    'inactive' => 'Votre compte est inactif. Veuillez contacter le support.',
    'locked' => 'Votre compte est verrouillé. Veuillez réessayer plus tard ou contacter le support.',
    'locked_duration' => 'Votre compte est verrouillé. Veuillez réessayer dans :minutes minutes.',
    
    // Registration
    'registration_successful' => 'Inscription réussie! Bienvenue à :app.',
    'registration_failed' => 'L\'inscription a échoué. Veuillez réessayer.',
    
    // Login
    'login_successful' => 'Connexion réussie! Bon retour.',
    'login_failed' => 'La connexion a échoué. Veuillez vérifier vos identifiants.',
    'requires_mfa' => 'Authentification multifactorielle requise.',
    'mfa_code_invalid' => 'Le code MFA fourni est incorrect.',
    
    // Logout
    'logout_successful' => 'Déconnexion réussie. À bientôt!',
    
    // Password
    'password_changed' => 'Mot de passe changé avec succès. Veuillez vous reconnecter.',
    'password_change_failed' => 'Échec du changement de mot de passe.',
    'current_password_incorrect' => 'Le mot de passe actuel est incorrect.',
    'password_reset_requested' => 'Si un compte existe avec cet email, vous recevrez des instructions pour réinitialiser le mot de passe.',
    'password_reset_successful' => 'Mot de passe réinitialisé avec succès. Veuillez vous connecter avec votre nouveau mot de passe.',
    'password_reset_failed' => 'Échec de la réinitialisation du mot de passe. Le lien peut être expiré ou invalide.',
    'password_reset_token_invalid' => 'Jeton de réinitialisation invalide ou expiré.',
    
    // MFA
    'mfa_enabled' => 'Authentification multifactorielle activée avec succès.',
    'mfa_disabled' => 'Authentification multifactorielle désactivée.',
    'mfa_setup_required' => 'Veuillez compléter la configuration MFA.',
    'mfa_verified' => 'Code MFA vérifié avec succès.',
    
    // Email Verification
    'email_verified' => 'Email vérifié avec succès.',
    'email_verification_required' => 'Veuillez vérifier votre adresse email avant de continuer.',
    
    // Account Status
    'account_locked' => 'Le compte a été verrouillé pour des raisons de sécurité.',
    'account_unlocked' => 'Le compte a été déverrouillé avec succès.',
    
    // Token
    'token_refreshed' => 'Jeton actualisé avec succès.',
    'token_invalid' => 'Jeton invalide ou expiré.',
    'token_missing' => 'Le jeton d\'authentification est manquant.',
    
    // Permissions
    'unauthorized' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
    'forbidden' => 'Accès interdit.',
    
    // Tenant
    'tenant_inactive' => 'Le locataire est inactif.',
    'tenant_subscription_expired' => 'L\'abonnement du locataire a expiré.',
    'no_tenant_assigned' => 'Aucun locataire assigné à l\'utilisateur.',
];
