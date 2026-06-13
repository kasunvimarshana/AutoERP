<?php

declare(strict_types=1);

return [
    'export_row_limit' => (int) env('REPORT_EXPORT_ROW_LIMIT', 5000),

    'pdf' => [
        'paper_size' => env('PDF_PAPER_SIZE', 'A4'),
        'orientation' => env('PDF_ORIENTATION', 'portrait'),
        'margins' => [
            'top' => 12,
            'right' => 10,
            'bottom' => 12,
            'left' => 10,
        ],
    ],
];
