<?php

namespace App\Enums;

enum FileDisk: string
{
    case Local = 'local';
    case S3 = 's3';
    case Public = 'public';
}
