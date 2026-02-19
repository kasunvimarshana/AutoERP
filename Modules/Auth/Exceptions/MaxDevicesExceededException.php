<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

/**
 * Max Devices Exceeded Exception
 *
 * Thrown when a user attempts to authenticate on more devices than allowed.
 */
class MaxDevicesExceededException extends BusinessRuleException
{
    protected string $errorCode = 'MAX_DEVICES_EXCEEDED';

    /**
     * The maximum allowed devices
     */
    protected ?int $maxDevices = null;

    /**
     * Create a new max devices exceeded exception instance
     *
     * @param  string  $message  Exception message
     * @param  int|null  $maxDevices  The maximum allowed devices
     * @param  string|null  $ruleName  The name of the violated rule
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'Maximum number of authenticated devices exceeded.',
        ?int $maxDevices = null,
        ?string $ruleName = 'max_devices',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $ruleName, $code, $previous, $context);
        $this->maxDevices = $maxDevices;
    }

    /**
     * Get the maximum allowed devices
     */
    public function getMaxDevices(): ?int
    {
        return $this->maxDevices;
    }

    /**
     * Set the maximum allowed devices
     */
    public function setMaxDevices(int $maxDevices): self
    {
        $this->maxDevices = $maxDevices;

        return $this;
    }
}
