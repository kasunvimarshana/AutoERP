<?php
return [
    'default_channel' => env('NOTIFICATION_DEFAULT_CHANNEL', 'in_app'),
    'queue' => env('NOTIFICATION_QUEUE', 'notifications'),
];
