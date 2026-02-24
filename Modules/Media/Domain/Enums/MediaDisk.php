<?php
namespace Modules\Media\Domain\Enums;
enum MediaDisk: string
{
    case Local = 'local';
    case S3 = 's3';
    case Gcs = 'gcs';
    case Azure = 'azure';
}
