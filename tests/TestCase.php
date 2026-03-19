<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\ClientRepository;

abstract class TestCase extends BaseTestCase
{
    protected bool $setUpPassportClient = false;

    /** @var bool Tracks whether Passport RSA keys have been generated for this run */
    protected static bool $passportKeysGenerated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->setUpPassportClient) {
            $this->setUpPassport();
        }
    }

    protected function setUpPassport(): void
    {
        // Generate RSA key files once per test-suite run (they are file-based, not DB-based).
        if (!static::$passportKeysGenerated) {
            Artisan::call('passport:keys', ['--force' => true]);
            static::$passportKeysGenerated = true;
        }

        try {
            $clientRepository = $this->app->make(ClientRepository::class);
            $clientRepository->personalAccessClient();
        } catch (\RuntimeException) {
            try {
                $clientRepository = $this->app->make(ClientRepository::class);
                $clientRepository->createPersonalAccessClient(
                    userId: null,
                    name: 'Test Personal Access Client',
                    redirect: 'http://localhost'
                );
            } catch (\Exception) {
                // DB not migrated; skip
            }
        }
    }
}
