<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Modules\User\Application\Contracts\UseCases\UserServiceInterface;

final class UserCreateCommand extends Command
{
    protected $signature = 'user:create
        {first_name : First name}
        {email : Email address}
        {password : Password hash or plain text depending on caller policy}
        {--tenant_id= : Optional tenant id}
        {--status=active : active|inactive|suspended}';

    protected $description = 'Create a user through the User module service.';

    public function __construct(private readonly UserServiceInterface $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->service->create([
            'first_name' => (string) $this->argument('first_name'),
            'email' => (string) $this->argument('email'),
            'password' => (string) $this->argument('password'),
            'tenant_id' => $this->option('tenant_id'),
            'status' => (string) $this->option('status'),
        ]);

        if ($result->isFailure()) {
            $this->error($result->errorOrFail()->message);

            return self::FAILURE;
        }

        $record = $result->valueOrFail();
        $id = method_exists($record, 'id') ? $record->id() : null;

        $this->info(sprintf('User created successfully%s.', $id !== null ? ' (ID: ' . $id . ')' : ''));

        return self::SUCCESS;
    }
}
