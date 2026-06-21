<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Contracts\Encryption\Encrypter;
use Modules\Configuration\Data\ConfigurationDefinition;
use RuntimeException;
use Throwable;

final class ConfigurationValueCodec
{
    public function __construct(private readonly Encrypter $encrypter) {}

    public function encode(ConfigurationDefinition $definition, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $encoded = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return $definition->sensitive
            ? $this->encrypter->encryptString($encoded)
            : $encoded;
    }

    public function decode(ConfigurationDefinition $definition, ?string $stored): mixed
    {
        if ($stored === null) {
            return null;
        }

        try {
            $payload = $definition->sensitive
                ? $this->encrypter->decryptString($stored)
                : $stored;

            return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Stored configuration value [{$definition->key}] is invalid.",
                previous: $exception,
            );
        }
    }
}
