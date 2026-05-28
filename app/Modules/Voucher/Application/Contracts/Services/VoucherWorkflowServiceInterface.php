<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VoucherWorkflowServiceInterface
{
    public function submit(int $voucherId, array $payload = []): Result;
    public function approve(int $voucherId, array $payload = []): Result;
    public function reject(int $voucherId, array $payload = []): Result;
    public function post(int $voucherId, array $payload = []): Result;
    public function cancel(int $voucherId, array $payload = []): Result;
    public function reverse(int $voucherId, array $payload = []): Result;
    public function history(int $voucherId): Result;
}