<?php
return [
    'defaults' => ['guard' => 'api', 'passwords' => 'users'],
    'guards' => ['api' => ['driver' => 'custom_passport', 'provider' => 'users']],
    'providers' => ['users' => ['driver' => 'eloquent', 'model' => App\Models\User::class]],
];
