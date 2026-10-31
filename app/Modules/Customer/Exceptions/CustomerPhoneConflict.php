<?php

declare(strict_types=1);

namespace Modules\Customer\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;

class CustomerPhoneConflict extends DomainException
{
    /** @param array<string, mixed>|null $customer */
    public function __construct(public string $field, public ?array $customer)
    {
        $owner = $customer === null ? 'a customer in another branch' : "{$customer['name']} ({$customer['code']})";
        parent::__construct("This number already belongs to {$owner}. Use the existing customer or enter a different number.");
    }

    public function render(): JsonResponse
    {
        return app(ApiErrorResponseFactory::class)->make('CUSTOMER_PHONE_IN_USE', $this->getMessage(), 422, 'validation', [
            'fields' => [$this->field => [$this->getMessage()]],
            'existing_customer' => $this->customer,
        ]);
    }
}
