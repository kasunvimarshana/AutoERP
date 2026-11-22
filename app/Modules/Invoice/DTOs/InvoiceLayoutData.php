<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\DTOs;

use Modules\Invoice\Enums\InvoiceLayoutTemplate;
use Modules\Invoice\Enums\InvoiceOrientation;
use Modules\Invoice\Enums\InvoicePaperSize;

final readonly class InvoiceLayoutData
{
    /**
     * @param array<string, mixed>|null $settings
     */
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description = null,
        public string $paperSize = InvoicePaperSize::A4->value,
        public string $orientation = InvoiceOrientation::Portrait->value,
        public string $viewTemplate = InvoiceLayoutTemplate::Standard->value,
        public bool $isActive = true,
        public ?string $customCss = null,
        public ?string $headerContent = null,
        public ?string $footerContent = null,
        public ?array $settings = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            code: trim((string) ($data['code'] ?? '')),
            name: trim((string) ($data['name'] ?? '')),
            description: isset($data['description']) && trim((string) $data['description']) !== ''
                ? trim((string) $data['description'])
                : null,
            paperSize: (string) ($data['paper_size'] ?? InvoicePaperSize::A4->value),
            orientation: (string) ($data['orientation'] ?? InvoiceOrientation::Portrait->value),
            viewTemplate: (string) ($data['view_template'] ?? InvoiceLayoutTemplate::Standard->value),
            isActive: (bool) ($data['is_active'] ?? true),
            customCss: isset($data['custom_css']) && trim((string) $data['custom_css']) !== ''
                ? trim((string) $data['custom_css'])
                : null,
            headerContent: isset($data['header_content']) && trim((string) $data['header_content']) !== ''
                ? trim((string) $data['header_content'])
                : null,
            footerContent: isset($data['footer_content']) && trim((string) $data['footer_content']) !== ''
                ? trim((string) $data['footer_content'])
                : null,
            settings: isset($data['settings']) && is_array($data['settings'])
                ? $data['settings']
                : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'paper_size' => $this->paperSize,
            'orientation' => $this->orientation,
            'view_template' => $this->viewTemplate,
            'is_active' => $this->isActive,
            'custom_css' => $this->customCss,
            'header_content' => $this->headerContent,
            'footer_content' => $this->footerContent,
            'settings' => $this->settings,
        ];
    }
}
