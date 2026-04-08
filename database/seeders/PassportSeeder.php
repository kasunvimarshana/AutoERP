<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class PassportSeeder extends Seeder
{
    /**
     * Create the Passport personal access grant client for the 'users' provider.
     *
     * This resolves the "Personal access client not found for 'users' user provider"
     * RuntimeException thrown by Laravel\Passport\ClientRepository::personalAccessClient().
     *
     * Run via: php artisan db:seed --class=PassportSeeder
     * or as part of: php artisan db:seed
     */
    public function run(ClientRepository $clients): void
    {
        $provider = config('auth.guards.api.provider', 'users');

        try {
            $clients->personalAccessClient($provider);
            // Personal access client already exists; skip creation.
        } catch (\RuntimeException) {
            $clients->createPersonalAccessGrantClient(
                name: 'Personal Access Client',
                provider: $provider,
            );
        }
    }
}
