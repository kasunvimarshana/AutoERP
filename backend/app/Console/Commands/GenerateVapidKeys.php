<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WebPush\WebPushService;
use Illuminate\Console\Command;

/**
 * Generate VAPID Keys Command
 *
 * Generates VAPID keys for Web Push notifications
 */
class GenerateVapidKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webpush:generate-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate VAPID keys for Web Push notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating VAPID keys for Web Push notifications...');

        try {
            $keys = WebPushService::generateVapidKeys();

            $this->newLine();
            $this->info('VAPID keys generated successfully!');
            $this->newLine();
            $this->line('Add these to your .env file:');
            $this->newLine();
            $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
            $this->line('VAPID_SUBJECT=mailto:admin@autoerp.com');
            $this->newLine();
            $this->warn('IMPORTANT: Keep the private key secure and never commit it to version control!');
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate VAPID keys: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
