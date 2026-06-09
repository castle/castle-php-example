<?php

// ---------------------------------------------------------------------------
// Demo catalog
// ---------------------------------------------------------------------------

function demos(): array
{
    return [
        'signup' => [
            'friendly_name' => 'sign up',
            'blurb' => 'Filter a registration ($registration) before the account exists.',
        ],
        'login' => [
            'friendly_name' => 'login',
            'blurb' => 'Filter the attempt, then assess a successful login with Risk.',
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
// Single-call flows return ['api_endpoint', 'castle_type', 'castle_status',
// 'payload']. The login flow returns ['steps' => [ <decision>, ... ]] because
// it reuses one request token across a Filter + Risk sequence.
// ---------------------------------------------------------------------------

// A registration is evaluated before the account exists, so it is anonymous
// activity and always goes to the Filter API with the submitted form params
// (email/phone only). A brand-new email is an attempt; an email that already
// belongs to a user is a failed registration, resolved via matching_user_id.
function decide_signup(array $input, array $cfg): array
{
    $email = $input['email'] ?? '';
    $requestToken = $input['request_token'] ?? '';

    $type = '$registration';

    if ($email === $cfg['valid_username']) {
        $status = '$failed';
        $payload = [
            'type' => $type,
            'status' => $status,
            'params' => ['email' => $email],
            'matching_user_id' => $cfg['valid_user_id'],
            'request_token' => $requestToken,
        ];
    } else {
        $status = '$attempted';
        $payload = [
            'type' => $type,
            'status' => $status,
            'params' => ['email' => $email],
            'request_token' => $requestToken,
        ];
    }

    return [
        'api_endpoint' => 'filter',
        'castle_type' => $type,
        'castle_status' => $status,
        'payload' => $payload,
    ];
}

// A login reuses a single request token across two calls: first Filter the
// attempt while the visitor is still anonymous, then — on success — assess the
// authenticated user with Risk. A failed attempt stays on Filter.
function decide_login(array $input, array $cfg): array
{
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $requestToken = $input['request_token'] ?? '';

    $type = '$login';

    // Step 1 — always filter the attempt up front (anonymous -> params).
    $steps = [[
        'api_endpoint' => 'filter',
        'castle_type' => $type,
        'castle_status' => '$attempted',
        'payload' => [
            'type' => $type,
            'status' => '$attempted',
            'params' => ['email' => $email],
            'request_token' => $requestToken,
        ],
    ]];

    // Step 2 — the outcome, on the same request token.
    if ($email === $cfg['valid_username'] && $password === $cfg['valid_password']) {
        $steps[] = [
            'api_endpoint' => 'risk',
            'castle_type' => $type,
            'castle_status' => '$succeeded',
            'payload' => [
                'type' => $type,
                'status' => '$succeeded',
                'user' => [
                    'id' => $cfg['valid_user_id'],
                    'email' => $email,
                    'registered_at' => $cfg['registered_at'],
                ],
                'request_token' => $requestToken,
            ],
        ];
    } else {
        $payload = [
            'type' => $type,
            'status' => '$failed',
            'params' => ['email' => $email],
            'request_token' => $requestToken,
        ];
        // A known email with a wrong password resolves to the existing user.
        if ($email === $cfg['valid_username']) {
            $payload['matching_user_id'] = $cfg['valid_user_id'];
        }
        $steps[] = [
            'api_endpoint' => 'filter',
            'castle_type' => $type,
            'castle_status' => '$failed',
            'payload' => $payload,
        ];
    }

    return ['steps' => $steps];
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
