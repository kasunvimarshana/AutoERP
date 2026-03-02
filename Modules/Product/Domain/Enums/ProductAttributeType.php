<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Enums;

/**
 * Defines the canonical set of supported type hints for dynamic product attributes.
 *
 * The type hint is informational — it tells API consumers and front-end UIs how
 * to render and validate the attribute value, which is always stored as a string.
 * The ERP backend does not enforce value-level validation based on this type;
 * presentation-layer concerns are intentionally kept outside the domain.
 */
enum ProductAttributeType: string
{
    /** Free-form text value (default). */
    case Text = 'text';

    /** Numeric value (integer or decimal, stored as a string). */
    case Number = 'number';

    /** Boolean flag stored as "true" or "false" string. */
    case Boolean = 'boolean';

    /** ISO 8601 date string (YYYY-MM-DD). */
    case Date = 'date';

    /** Fully qualified URL string. */
    case Url = 'url';

    public function label(): string
    {
        return match ($this) {
            ProductAttributeType::Text => 'Text',
            ProductAttributeType::Number => 'Number',
            ProductAttributeType::Boolean => 'Boolean',
            ProductAttributeType::Date => 'Date',
            ProductAttributeType::Url => 'URL',
        };
    }
}
