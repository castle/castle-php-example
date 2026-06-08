<?php

// ---------------------------------------------------------------------------
// Demo catalog
// ---------------------------------------------------------------------------

function demos(): array
{
    return [
        'signup' => [
            'friendly_name' => 'sign up',
            'blurb' => 'Evaluate a registration ($registration) with the risk endpoint.',
        ],
        'login' => [
            'friendly_name' => 'login',
            'blurb' => 'Evaluate a login with the risk and filter endpoints.',
            'wsd' => 'https://www.websequencediagrams.com/files/render?link=Q9WYp8rNThVZhA1inf2FSLfjChYZTdHXyGB9zqvMNpsaAvKvJPARgo5LI5fM5K4D',
        ],
        'account' => [
            'friendly_name' => 'account',
            'blurb' => 'Update your profile, send a custom event, and log out.',
        ],
        'password_reset' => [
            'friendly_name' => 'password reset',
            'blurb' => 'Record a password-reset event with the non-blocking log endpoint.',
        ],
        'lists' => [
            'friendly_name' => 'lists',
            'blurb' => 'Create and fetch lists with the Lists API.',
        ],
        'privacy' => [
            'friendly_name' => 'privacy',
            'blurb' => "Request or delete a user's data with the Privacy API.",
        ],
        'webhooks' => [
            'friendly_name' => 'webhooks',
            'blurb' => 'Verify and inspect incoming Castle webhooks.',
        ],
    ];
}

function demo_list(): array
{
    $list = [];
    foreach (demos() as $url => $demo) {
        $list[] = ['url' => $url] + $demo;
    }
    return $list;
}

function valid_urls(): array
{
    return array_keys(demos());
}

// ---------------------------------------------------------------------------
// Event flows (pure: decide which endpoint/payload, no network calls)
//
// Each returns ['api_endpoint', 'castle_type', 'castle_status', 'payload'].
// ---------------------------------------------------------------------------

function decide_signup(array $input, array $cfg): array
{
    $name = $input['name'] ?? null;
    $email = $input['email'] ?? '';
    $requestToken = $input['request_token'] ?? '';

    $type = '$registration';

    // An email that's already taken (the known demo user) is a failed
    // registration and goes to /filter; a fresh sign-up is risk-assessed.
    if ($email === $cfg['valid_username']) {
        $status = '$failed';
        $endpoint = 'filter';
    } else {
        $status = '$succeeded';
        $endpoint = 'risk';
    }

    $payload = [
        'type' => $type,
        'status' => $status,
        'user' => [
            'id' => $cfg['valid_user_id'],
            'email' => $email,
            'name' => $name,
        ],
        'request_token' => $requestToken,
    ];

    return [
        'api_endpoint' => $endpoint,
        'castle_type' => $type,
        'castle_status' => $status,
        'payload' => $payload,
    ];
}

function decide_login(array $input, array $cfg): array
{
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $requestToken = $input['request_token'] ?? '';

    $type = '$login';
    $registeredAt = $cfg['registered_at'];

    if ($email === $cfg['valid_username']) {
        $userId = $cfg['valid_user_id'];
        if ($password === $cfg['valid_password']) {
            $status = '$succeeded';
            $endpoint = 'risk';
        } else {
            $status = '$failed';
            $endpoint = 'filter';
        }
    } else {
        $endpoint = 'filter';
        $status = '$failed';
        $userId = null;
        $registeredAt = null;
    }

    $user = ['id' => $userId, 'email' => $email];
    if ($registeredAt) {
        $user['registered_at'] = $registeredAt;
    }

    $payload = [
        'type' => $type,
        'status' => $status,
        'user' => $user,
        'request_token' => $requestToken,
    ];

    return [
        'api_endpoint' => $endpoint,
        'castle_type' => $type,
        'castle_status' => $status,
        'payload' => $payload,
    ];
}

function decide_profile_update(array $input, array $cfg): array
{
    $name = $input['name'] ?? null;
    $email = ($input['email'] ?? '') ?: $cfg['valid_username'];
    $requestToken = $input['request_token'] ?? '';

    $type = '$profile_update';
    $status = '$succeeded';

    $payload = [
        'type' => $type,
        'status' => $status,
        'user' => [
            'id' => $cfg['valid_user_id'],
            'email' => $email,
            'name' => $name,
            'registered_at' => $cfg['registered_at'],
        ],
        'request_token' => $requestToken,
    ];

    return [
        'api_endpoint' => 'risk',
        'castle_type' => $type,
        'castle_status' => $status,
        'payload' => $payload,
    ];
}

function decide_password_reset(array $input, array $cfg): array
{
    $password = $input['password'] ?? '';
    $requestToken = $input['request_token'] ?? '';

    // A new password that differs from the current one is a successful reset.
    $status = $password === $cfg['valid_password'] ? '$failed' : '$succeeded';
    $type = '$password_reset';

    $payload = [
        'type' => $type,
        'status' => $status,
        'user' => [
            'id' => $cfg['valid_user_id'],
            'email' => $cfg['valid_username'],
            'registered_at' => $cfg['registered_at'],
        ],
        'request_token' => $requestToken,
    ];

    return [
        'api_endpoint' => 'log',
        'castle_type' => $type,
        'castle_status' => $status,
        'payload' => $payload,
    ];
}

function decide_logout(array $input, array $cfg): array
{
    $requestToken = $input['request_token'] ?? '';

    $type = '$logout';
    $status = '$succeeded';

    $payload = [
        'type' => $type,
        'status' => $status,
        'user' => [
            'id' => $cfg['valid_user_id'],
            'email' => $cfg['valid_username'],
        ],
        'request_token' => $requestToken,
    ];

    return [
        'api_endpoint' => 'log',
        'castle_type' => $type,
        'castle_status' => $status,
        'payload' => $payload,
    ];
}

// ---------------------------------------------------------------------------
// Lists / Privacy payload builders
// ---------------------------------------------------------------------------

function build_create_list_payload(array $input): array
{
    return [
        'name' => ($input['name'] ?? '') ?: 'demo-blocklist',
        'color' => ($input['color'] ?? '') ?: '$red',
        'primary_field' => ($input['primary_field'] ?? '') ?: 'user.email',
    ];
}

function build_privacy_payload(array $input, array $cfg): array
{
    return [
        'identifier' => ($input['identifier'] ?? '') ?: $cfg['valid_username'],
        'identifier_type' => ($input['identifier_type'] ?? '') ?: '$email',
    ];
}
