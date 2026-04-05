<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\Entities;

/**
 * A reusable label design template used to render barcode labels for printing.
 *
 * Templates support multiple output formats (zpl, epl, svg) and use
 * {{ variable }} placeholders that are replaced at render-time by the
 * PrintBarcodeLabelService.
 *
 * Built-in placeholder keys available at render time:
 *   - {{ barcode_value }}   – raw barcode value
 *   - {{ barcode_label }}   – human-readable label from BarcodeDefinition
 *   - {{ barcode_type }}    – symbology name (e.g. "code128")
 *   - {{ barcode_svg }}     – rendered SVG markup (for svg/html drivers)
 *   - Any key passed in the `variables` array when printing
 */
class LabelTemplate
{
    public function __construct(
        private readonly ?int    $id,
        private readonly int     $tenantId,
        private string           $name,
        private string           $format,     // zpl | epl | svg
        private string           $content,    // raw template body with {{ }} placeholders
        private array            $defaultVariables,
        private bool             $isActive,
        private readonly ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface          $updatedAt,
    ) {}

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getId(): ?int                       { return $this->id; }
    public function getTenantId(): int                  { return $this->tenantId; }
    public function getName(): string                   { return $this->name; }
    public function getFormat(): string                 { return $this->format; }
    public function getContent(): string                { return $this->content; }
    public function getDefaultVariables(): array        { return $this->defaultVariables; }
    public function isActive(): bool                    { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ── Domain methods ────────────────────────────────────────────────────────

    public function updateName(string $name): void
    {
        $this->name      = $name;
        $this->updatedAt = new \DateTime();
    }

    public function updateContent(string $content, string $format): void
    {
        $this->content   = $content;
        $this->format    = $format;
        $this->updatedAt = new \DateTime();
    }

    public function setDefaultVariables(array $variables): void
    {
        $this->defaultVariables = $variables;
        $this->updatedAt        = new \DateTime();
    }

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTime();
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Render the template by substituting {{ key }} placeholders with
     * the provided variables merged on top of the stored defaults.
     *
     * @param  array<string,string> $variables
     */
    public function render(array $variables = []): string
    {
        $merged  = array_merge($this->defaultVariables, $variables);
        $content = $this->content;

        foreach ($merged as $key => $value) {
            $content = str_replace('{{ ' . $key . ' }}', (string) $value, $content);
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        return $content;
    }

    /** @return string[] Placeholder key names referenced in the template */
    public function getPlaceholders(): array
    {
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $this->content, $matches);
        return array_unique($matches[1] ?? []);
    }
}
