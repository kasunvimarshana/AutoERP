<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Validation\ValidationException;
use Modules\Core\Helpers\MathHelper;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Purchase\Enums\VendorStatus;
use Modules\Purchase\Events\VendorCreated;
use Modules\Purchase\Models\Vendor;
use Modules\Purchase\Repositories\VendorRepository;

/**
 * Vendor Service
 *
 * Handles business logic for vendor management including
 * creation, status management, and credit limit validation.
 */
class VendorService
{
    public function __construct(
        private VendorRepository $vendorRepository,
        private CodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create a new vendor.
     */
    public function create(array $data): Vendor
    {
        return TransactionHelper::execute(function () use ($data) {
            // Generate vendor code if not provided
            if (empty($data['vendor_code'])) {
                $data['vendor_code'] = $this->generateVendorCode();
            }

            // Set default status and balance
            $data['status'] = $data['status'] ?? VendorStatus::ACTIVE;
            $data['current_balance'] = $data['current_balance'] ?? '0.000000';

            // Validate unique email
            if (! empty($data['email'])) {
                $existing = $this->vendorRepository->findByEmail($data['email']);
                if ($existing) {
                    throw ValidationException::withMessages([
                        'email' => "Vendor with email {$data['email']} already exists",
                    ]);
                }
            }

            // Create vendor
            $vendor = $this->vendorRepository->create($data);

            // Fire event
            event(new VendorCreated($vendor));

            return $vendor;
        });
    }

    /**
     * Update vendor.
     */
    public function update(string $id, array $data): Vendor
    {
        $vendor = $this->vendorRepository->findOrFail($id);

        return TransactionHelper::execute(function () use ($vendor, $data) {
            // Validate unique email if changed
            if (! empty($data['email']) && $data['email'] !== $vendor->email) {
                $existing = $this->vendorRepository->findByEmail($data['email']);
                if ($existing && $existing->id !== $vendor->id) {
                    throw ValidationException::withMessages([
                        'email' => "Vendor with email {$data['email']} already exists",
                    ]);
                }
            }

            return $this->vendorRepository->update($vendor->id, $data);
        });
    }

    /**
     * Activate vendor.
     */
    public function activate(string $id): Vendor
    {
        $vendor = $this->vendorRepository->findOrFail($id);

        if ($vendor->status === VendorStatus::ACTIVE) {
            return $vendor;
        }

        return $this->vendorRepository->update($vendor->id, [
            'status' => VendorStatus::ACTIVE,
        ]);
    }

    /**
     * Deactivate vendor.
     */
    public function deactivate(string $id): Vendor
    {
        $vendor = $this->vendorRepository->findOrFail($id);

        if ($vendor->status === VendorStatus::INACTIVE) {
            return $vendor;
        }

        return $this->vendorRepository->update($vendor->id, [
            'status' => VendorStatus::INACTIVE,
        ]);
    }

    /**
     * Block vendor.
     */
    public function block(string $id, ?string $reason = null): Vendor
    {
        $vendor = $this->vendorRepository->findOrFail($id);

        if ($vendor->status === VendorStatus::BLOCKED) {
            return $vendor;
        }

        $updateData = [
            'status' => VendorStatus::BLOCKED,
        ];

        if ($reason) {
            $metadata = $vendor->metadata ?? [];
            $metadata['block_reason'] = $reason;
            $metadata['blocked_at'] = now()->toISOString();
            $updateData['metadata'] = $metadata;
        }

        return $this->vendorRepository->update($vendor->id, $updateData);
    }

    /**
     * Update vendor status.
     */
    public function updateStatus(string $id, VendorStatus $status): Vendor
    {
        $vendor = $this->vendorRepository->findOrFail($id);

        if ($vendor->status === $status) {
            return $vendor;
        }

        return $this->vendorRepository->update($vendor->id, [
            'status' => $status,
        ]);
    }

    /**
     * Delete vendor.
     */
    public function delete(string $id): bool
    {
        $vendor = $this->vendorRepository->findOrFail($id);

        return $this->vendorRepository->delete($vendor->id);
    }

    /**
     * Check if vendor can accept additional credit.
     */
    public function checkCreditLimit(string $vendorId, string $amount): bool
    {
        $vendor = $this->vendorRepository->findOrFail($vendorId);

        // No credit limit set - allow any amount
        if ($vendor->credit_limit === null) {
            return true;
        }

        // Calculate potential new balance
        $potentialBalance = MathHelper::add(
            (string) $vendor->current_balance,
            $amount
        );

        // Check if potential balance exceeds credit limit
        return MathHelper::compare($potentialBalance, (string) $vendor->credit_limit) <= 0;
    }

    /**
     * Generate unique vendor code.
     */
    private function generateVendorCode(): string
    {
        $prefix = config('purchase.vendor.code_prefix', 'VEN-');

        return $this->codeGenerator->generate(
            $prefix,
            fn (string $code) => $this->vendorRepository->findByCode($code) !== null,
            8
        );
    }
}
