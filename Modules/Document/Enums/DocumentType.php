<?php

declare(strict_types=1);

namespace Modules\Document\Enums;

enum DocumentType: string
{
    case FILE = 'file';
    case LINK = 'link';
    case FOLDER = 'folder';

    public function label(): string
    {
        return match ($this) {
            self::FILE => 'File',
            self::LINK => 'Link',
            self::FOLDER => 'Folder',
        };
    }
}
