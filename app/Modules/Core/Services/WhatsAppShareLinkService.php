<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use InvalidArgumentException;

final class WhatsAppShareLinkService
{
    public function __construct(private readonly PhoneNumberNormalizer $numbers) {}

    /** @return array{phone:string, whatsapp_url:string} */
    public function create(string $phone, string $message): array
    {
        $normalizedPhone = $this->normalize($phone);

        return [
            'phone' => $normalizedPhone,
            'whatsapp_url' => 'https://wa.me/'.$normalizedPhone.'?text='.rawurlencode($message),
        ];
    }

    public function normalize(string $phone): string
    {
        if (trim($phone) === '') {
            throw new InvalidArgumentException('The selected recipient does not have a WhatsApp number.');
        }

        try {
            return $this->numbers->normalize($phone);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException('The selected recipient has an invalid WhatsApp number.', previous: $exception);
        }
    }
}
