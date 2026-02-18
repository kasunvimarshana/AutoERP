<?php

namespace Modules\IAM\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Password complexity validation rule
 * Ensures passwords meet minimum security requirements
 */
class StrongPassword implements Rule
{
    private int $minLength;
    private bool $requireUppercase;
    private bool $requireLowercase;
    private bool $requireNumbers;
    private bool $requireSpecialChars;
    private array $failedRequirements = [];

    public function __construct(
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = true
    ) {
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
    }

    public function passes($attribute, $value): bool
    {
        $this->failedRequirements = [];

        if (strlen($value) < $this->minLength) {
            $this->failedRequirements[] = "at least {$this->minLength} characters";
        }

        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $this->failedRequirements[] = 'at least one uppercase letter';
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            $this->failedRequirements[] = 'at least one lowercase letter';
        }

        if ($this->requireNumbers && !preg_match('/[0-9]/', $value)) {
            $this->failedRequirements[] = 'at least one number';
        }

        if ($this->requireSpecialChars && !preg_match('/[^A-Za-z0-9]/', $value)) {
            $this->failedRequirements[] = 'at least one special character';
        }

        return empty($this->failedRequirements);
    }

    public function message(): string
    {
        if (empty($this->failedRequirements)) {
            return 'The :attribute does not meet security requirements.';
        }

        return 'The :attribute must contain ' . implode(', ', $this->failedRequirements) . '.';
    }
}
