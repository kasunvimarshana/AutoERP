<?php

namespace App\Modules\Reporting\DTOs;

use App\Modules\Reporting\Enums\WidgetType;

/**
 * Add Widget DTO
 */
class AddWidgetDTO
{
    public function __construct(
        public int $dashboardId,
        public WidgetType $widgetType,
        public string $title,
        public array $config,
        public int $positionX = 0,
        public int $positionY = 0,
        public int $width = 4,
        public int $height = 3,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            dashboardId: $data['dashboard_id'],
            widgetType: WidgetType::from($data['widget_type']),
            title: $data['title'],
            config: $data['config'],
            positionX: $data['position_x'] ?? 0,
            positionY: $data['position_y'] ?? 0,
            width: $data['width'] ?? 4,
            height: $data['height'] ?? 3,
        );
    }

    public function toArray(): array
    {
        return [
            'dashboard_id' => $this->dashboardId,
            'widget_type' => $this->widgetType->value,
            'title' => $this->title,
            'config' => $this->config,
            'position_x' => $this->positionX,
            'position_y' => $this->positionY,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
