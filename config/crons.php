<?php

return [
    'emails_enabled' => env('EMAIL_CRONS_ENABLED', false),
    'user' => env('CRON_USER'),
    'password' => env('CRON_PASSWORD'),
];
