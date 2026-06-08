<?php

// Keep PHP notices/deprecations out of HTTP responses (they would corrupt the
// JSON the demo endpoints return); errors are still written to the server log.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/vendor/autoload.php';

load_env();
castle_configure();

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if ($uri !== '/') {
    $uri = rtrim($uri, '/');
}

// Let the PHP built-in server serve real static assets as-is.
if (PHP_SAPI === 'cli-server' && $uri !== '/' && strpos($uri, '/static/') === 0) {
    if (is_file(__DIR__ . $uri)) {
        return false;
    }
}

// Castle browser SDK, served from node_modules.
if (strpos($uri, '/vendor/castle-js/') === 0) {
    serve_castle_js(substr($uri, strlen('/vendor/castle-js/')));
    return;
}

if ($method === 'POST') {
    dispatch_post($uri);
    return;
}

dispatch_get($uri);

// ---------------------------------------------------------------------------

function dispatch_get(string $uri): void
{
    if ($uri === '/') {
        echo render_page('home', default_params());
        return;
    }

    $name = ltrim($uri, '/');

    if ($name === 'webhooks') {
        render_webhooks_page();
        return;
    }

    if (in_array($name, valid_urls(), true)) {
        $params = default_params() + demos()[$name];
        $params['friendly_name'] = demos()[$name]['friendly_name'];
        $params['demo_name'] = $name;
        echo render_page('demos/' . $name, $params);
        return;
    }

    http_response_code(404);
    echo render_page('error', default_params());
}

function render_webhooks_page(): void
{
    $params = default_params() + demos()['webhooks'];
    $params['friendly_name'] = demos()['webhooks']['friendly_name'];
    $params['demo_name'] = 'webhooks';

    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ($_SERVER['REQUEST_SCHEME'] ?? 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $params['webhook_endpoint'] = $proto . '://' . $host . '/webhooks/castle';
    $params['webhooks_received'] = webhooks_all();

    echo render_page('demos/webhooks', $params);
}

function dispatch_post(string $uri): void
{
    switch ($uri) {
        case '/evaluate_signup':
            respond_event(decide_signup(read_json_body(), flow_cfg()));
            return;
        case '/evaluate_login':
            respond_event(decide_login(read_json_body(), flow_cfg()));
            return;
        case '/evaluate_profile_update':
            respond_event(decide_profile_update(read_json_body(), flow_cfg()));
            return;
        case '/evaluate_logout':
            respond_event(decide_logout(read_json_body(), flow_cfg()));
            return;
        case '/evaluate_new_password':
            respond_password_reset(decide_password_reset(read_json_body(), flow_cfg()));
            return;
        case '/create_list':
            respond_create_list(read_json_body());
            return;
        case '/privacy_user_data':
            respond_privacy(read_json_body());
            return;
        case '/webhooks/castle':
            $status = process_incoming_webhook(
                file_get_contents('php://input'),
                $_SERVER['HTTP_X_CASTLE_SIGNATURE'] ?? null
            );
            http_response_code($status);
            return;
        default:
            send_json(['error' => 'Not found'], 404);
    }
}

function respond_event(array $decision): void
{
    $result = run_event_flow($decision);

    $response = [
        'api_endpoint' => $decision['api_endpoint'],
        'payload_to_castle' => $decision['payload'],
        'castle_type' => $decision['castle_type'],
        'castle_status' => $decision['castle_status'],
    ];

    // The log endpoint is non-blocking and returns no verdict.
    if ($decision['api_endpoint'] !== 'log') {
        $response['result'] = $result;
    }

    send_json($response);
}

function respond_password_reset(array $decision): void
{
    run_event_flow($decision);

    send_json([
        'api_endpoint' => 'log',
        'payload_to_castle' => $decision['payload'],
        'type' => $decision['castle_type'],
        'status' => $decision['castle_status'],
    ]);
}

function respond_create_list(array $input): void
{
    $payload = build_create_list_payload($input);

    try {
        $created = Castle::createList($payload);
        $allLists = Castle::getAllLists();
        $result = ['created' => $created, 'all_lists' => $allLists];
    } catch (Castle_Error $error) {
        $result = ['error' => $error->getMessage()];
    }

    send_json([
        'api_endpoint' => 'lists',
        'payload_to_castle' => $payload,
        'result' => $result,
    ]);
}

function respond_privacy(array $input): void
{
    $action = $input['action'] ?? 'request';
    $payload = build_privacy_payload($input, flow_cfg());

    try {
        if ($action === 'delete') {
            $apiEndpoint = 'privacy (delete)';
            $result = Castle::deleteUserData($payload);
        } else {
            $apiEndpoint = 'privacy (request)';
            $result = Castle::requestUserData($payload);
        }
    } catch (Castle_Error $error) {
        $apiEndpoint = 'privacy';
        $result = ['error' => $error->getMessage()];
    }

    send_json([
        'api_endpoint' => $apiEndpoint,
        'payload_to_castle' => $payload,
        'result' => $result,
    ]);
}
