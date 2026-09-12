<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Data\WhatsAppVerificationRecipient;
use Modules\Core\Enums\WhatsAppVerificationStatus;

abstract class ManualWhatsAppVerificationService
{
    public function __construct(private readonly WhatsAppShareLinkService $links) {}

    /** @return array<string, mixed> */
    public function state(Model $party): array
    {
        try {
            $recipient = $this->resolveRecipient($party);
            $phone = $this->links->normalize($recipient->phone);
        } catch (InvalidArgumentException) {
            return $this->unavailableState($party);
        }

        return $this->stateFor($party, $recipient, $phone);
    }

    /**
     * Build the compact list state from records loaded in one batch by the owning module.
     *
     * @param  iterable<Model>  $records
     * @return array{available:bool, status:string, recipient:?array{name:string, phone:string, source:string}}
     */
    public function listState(Model $party, iterable $records): array
    {
        try {
            $recipient = $this->resolveRecipient($party);
            $phone = $this->links->normalize($recipient->phone);
        } catch (InvalidArgumentException) {
            $hasVerifiedHistory = false;
            foreach ($records as $record) {
                if ((string) $record->status === WhatsAppVerificationStatus::Verified->value) {
                    $hasVerifiedHistory = true;
                    break;
                }
            }

            return ['available' => false, 'status' => $hasVerifiedHistory ? 'number_changed' : 'unverified', 'recipient' => null];
        }

        $pending = false;
        $verified = false;
        $hasVerifiedHistory = false;
        $now = now();

        foreach ($records as $record) {
            $status = (string) $record->status;
            if ($status === WhatsAppVerificationStatus::Verified->value) {
                $hasVerifiedHistory = true;
                if ((string) $record->normalized_phone === $phone) {
                    $verified = true;
                }
            }
            if ($status === WhatsAppVerificationStatus::Pending->value
                && (string) $record->normalized_phone === $phone
                && $record->expires_at instanceof Carbon
                && $record->expires_at->isAfter($now)) {
                $pending = true;
            }
        }

        return [
            'available' => true,
            'status' => $pending ? 'pending' : ($verified ? 'verified' : ($hasVerifiedHistory ? 'number_changed' : 'unverified')),
            'recipient' => [
                'name' => $recipient->name,
                'phone' => $phone,
                'source' => $recipient->source,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function start(Model $party, int $userId, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($party, $userId, $idempotencyKey): array {
            $party = $this->lockParty($party);
            $recipient = $this->resolveRecipient($party);
            $phone = $this->links->normalize($recipient->phone);
            $seed = $this->challengeSeed($party, $phone, $idempotencyKey);
            $code = $this->challengeCode($seed);
            $now = now();

            $existing = $this->verificationQuery($party)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();
            if ($existing !== null) {
                if ((string) $existing->normalized_phone !== $phone
                    || (string) $existing->status !== WhatsAppVerificationStatus::Pending->value
                    || $existing->expires_at->isPast()) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => ['This verification request can no longer be retried. Start a new verification.'],
                    ]);
                }

                return $this->challengeResponse($party, $recipient, $phone, $code, $existing->expires_at->toISOString());
            }

            $this->verificationQuery($party)
                ->where('status', WhatsAppVerificationStatus::Pending->value)
                ->lockForUpdate()
                ->get()
                ->each(static fn (Model $record) => $record->update([
                    'status' => WhatsAppVerificationStatus::Superseded->value,
                    'superseded_at' => $now,
                ]));

            $expiresAt = $now->copy()->addMinutes($this->ttlMinutes());
            $record = $this->newVerification($party, $recipient, [
                'normalized_phone' => $phone,
                'phone_source' => $recipient->source,
                'idempotency_key' => $idempotencyKey,
                'code_hash' => Hash::make($code),
                'status' => WhatsAppVerificationStatus::Pending->value,
                'attempt_count' => 0,
                'max_attempts' => $this->maximumAttempts(),
                'expires_at' => $expiresAt,
                'created_by' => $userId,
            ]);
            $record->save();

            return $this->challengeResponse($party, $recipient, $phone, $code, $expiresAt->toISOString());
        });
    }

    /** @return array<string, mixed> */
    public function confirm(Model $party, int $userId, string $code): array
    {
        $error = DB::transaction(function () use ($party, $userId, $code): ?array {
            $party = $this->lockParty($party);
            $recipient = $this->resolveRecipient($party);
            $phone = $this->links->normalize($recipient->phone);
            $record = $this->verificationQuery($party)
                ->where('status', WhatsAppVerificationStatus::Pending->value)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                $verified = $this->verificationQuery($party)
                    ->where('status', WhatsAppVerificationStatus::Verified->value)
                    ->where('normalized_phone', $phone)
                    ->latest('verified_at')
                    ->first();

                return $verified !== null && Hash::check($code, (string) $verified->code_hash)
                    ? null
                    : ['code' => ['There is no pending verification for this number.']];
            }

            if ((string) $record->normalized_phone !== $phone) {
                $record->update([
                    'status' => WhatsAppVerificationStatus::Superseded->value,
                    'superseded_at' => now(),
                ]);

                return ['code' => ['The WhatsApp number changed. Start a new verification.']];
            }

            if ($record->expires_at->isPast()) {
                $record->update(['status' => WhatsAppVerificationStatus::Expired->value]);

                return ['code' => ['The verification code has expired. Start a new verification.']];
            }

            if (! Hash::check($code, (string) $record->code_hash)) {
                $attempts = (int) $record->attempt_count + 1;
                $remaining = max(0, (int) $record->max_attempts - $attempts);
                $record->update([
                    'attempt_count' => $attempts,
                    'status' => $remaining === 0
                        ? WhatsAppVerificationStatus::Failed->value
                        : WhatsAppVerificationStatus::Pending->value,
                ]);

                return ['code' => [$remaining === 0
                    ? 'The verification attempt limit was reached. Start a new verification.'
                    : "The verification code is incorrect. {$remaining} attempt(s) remain."]];
            }

            $record->update([
                'status' => WhatsAppVerificationStatus::Verified->value,
                'verified_by' => $userId,
                'verified_at' => now(),
            ]);

            return null;
        });

        if ($error !== null) {
            throw ValidationException::withMessages($error);
        }

        return $this->state($party->refresh());
    }

    /** @return array<string, mixed> */
    private function stateFor(Model $party, WhatsAppVerificationRecipient $recipient, string $phone): array
    {
        $pending = $this->verificationQuery($party)
            ->where('normalized_phone', $phone)
            ->where('status', WhatsAppVerificationStatus::Pending->value)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
        $verified = $this->verificationQuery($party)
            ->where('normalized_phone', $phone)
            ->where('status', WhatsAppVerificationStatus::Verified->value)
            ->latest('verified_at')
            ->first();

        $status = $pending !== null
            ? WhatsAppVerificationStatus::Pending->value
            : ($verified !== null
                ? WhatsAppVerificationStatus::Verified->value
                : ($this->verificationQuery($party)->where('status', WhatsAppVerificationStatus::Verified->value)->exists()
                    ? 'number_changed'
                    : 'unverified'));

        return [
            'available' => true,
            'status' => $status,
            'recipient' => [
                'name' => $recipient->name,
                'phone' => $phone,
                'source' => $recipient->source,
            ],
            'pending_expires_at' => $pending?->expires_at?->toISOString(),
            'verified_at' => $verified?->verified_at?->toISOString(),
            'verified_by' => $verified === null ? null : $this->userName((int) $party->tenant_id, $verified->verified_by),
            'verification_method' => $verified === null ? null : 'manual_whatsapp_reply_code',
        ];
    }

    /** @return array<string, mixed> */
    private function unavailableState(Model $party): array
    {
        return [
            'available' => false,
            'status' => $this->verificationQuery($party)
                ->where('status', WhatsAppVerificationStatus::Verified->value)
                ->exists() ? 'number_changed' : 'unverified',
            'recipient' => null,
            'pending_expires_at' => null,
            'verified_at' => null,
            'verified_by' => null,
            'verification_method' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function challengeResponse(
        Model $party,
        WhatsAppVerificationRecipient $recipient,
        string $phone,
        string $code,
        string $expiresAt,
    ): array {
        $message = strtr((string) config('whatsapp-verification.message'), [
            '{recipient_name}' => $recipient->name,
            '{verification_code}' => $code,
            '{expires_in_minutes}' => (string) $this->ttlMinutes(),
        ]);
        $link = $this->links->create($phone, $message);

        return [
            ...$this->stateFor($party, $recipient, $phone),
            'whatsapp_url' => $link['whatsapp_url'],
            'pending_expires_at' => $expiresAt,
        ];
    }

    private function challengeSeed(Model $party, string $phone, string $idempotencyKey): string
    {
        return implode('|', [(string) $party->tenant_id, $party::class, (string) $party->getKey(), $phone, $idempotencyKey]);
    }

    private function challengeCode(string $seed): string
    {
        return 'WA-'.strtoupper(substr(hash_hmac('sha256', $seed, (string) config('app.key')), 0, 8));
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) config('whatsapp-verification.code_ttl_minutes', 15));
    }

    private function maximumAttempts(): int
    {
        return max(1, (int) config('whatsapp-verification.maximum_attempts', 5));
    }

    private function userName(int $tenantId, mixed $userId): ?string
    {
        if (! is_numeric($userId)) {
            return null;
        }

        $user = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('id', (int) $userId)
            ->first(['first_name', 'last_name']);

        return $user === null ? null : trim($user->first_name.' '.($user->last_name ?? ''));
    }

    /** @return Builder<Model> */
    abstract protected function verificationQuery(Model $party): Builder;

    abstract protected function lockParty(Model $party): Model;

    abstract protected function resolveRecipient(Model $party): WhatsAppVerificationRecipient;

    /** @param array<string, mixed> $attributes */
    abstract protected function newVerification(
        Model $party,
        WhatsAppVerificationRecipient $recipient,
        array $attributes,
    ): Model;
}
