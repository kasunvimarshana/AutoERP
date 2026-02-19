<?php

return [
    'name' => [
        'required' => 'The name field is required',
    ],
    'email' => [
        'required' => 'The email field is required',
        'email' => 'The email must be a valid email address',
        'unique' => 'The email has already been taken',
    ],
    'password' => [
        'required' => 'The password field is required',
        'confirmed' => 'The password confirmation does not match',
    ],
];
