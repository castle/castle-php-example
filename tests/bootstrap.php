<?php

// Deterministic env, applied before the app helpers read it.
$env = [
    'castle_api_secret' => 'test_secret',
    'castle_pk' => 'pk_test',
    'location' => 'test',
    'valid_username' => 'clark.kent@dailyplanet.com',
    'valid_password' => 'supersecret',
    'valid_user_id' => '00000000',
    'invalid_password' => 'qwerty',
    'webhook_url' => 'https://webhook.site',
];
foreach ($env as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
}

require __DIR__ . '/../vendor/autoload.php';

Castle::setApiKey(getenv('castle_api_secret'));
