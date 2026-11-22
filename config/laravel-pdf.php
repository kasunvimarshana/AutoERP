<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

return [
    'driver' => env('PDF_DRIVER', 'dompdf'),

    'dompdf' => [
        'is_remote_enabled' => (bool) env('PDF_DOMPDF_REMOTE_ENABLED', false),
        'chroot' => env('PDF_DOMPDF_CHROOT', base_path()),
    ],
];
