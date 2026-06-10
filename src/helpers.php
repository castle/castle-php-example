<?php

require_once __DIR__ . '/flows.php';

// ---------------------------------------------------------------------------
// Environment
// ---------------------------------------------------------------------------

// Demo fixture defaults. Only castle_pk and castle_api_secret need to be set in
// .env; the simulated "valid user" the demo logs in falls back to these values.
const DEMO_DEFAULTS = [
    'location' => 'localhost',
    'valid_username' => 'clark.kent@dailyplanet.com',
    'valid_name' => 'Clark Kent',
    'valid_user_id' => '00000000',
    'valid_password' => '1234',
    'invalid_password' => 'qwerty',
    'webhook_url' => 'https://webhook.site',
];

const REGISTERED_AT = '2020-02-23T22:28:55.387Z';

function load_env(?string $path = null): void
{
    $path = $path ?: dirname(__DIR__) . '/.env';
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, $default = null)
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    if (array_key_exists($key, DEMO_DEFAULTS)) {
        return DEMO_DEFAULTS[$key];
    }
    return $default;
}

// ---------------------------------------------------------------------------
// Castle SDK configuration
// ---------------------------------------------------------------------------

function castle_configure(): void
{
    Castle::setApiKey(env('castle_api_secret'));

    // Set the request timeout in milliseconds (applies to both the connection
    // and the transfer).
    Castle::setRequestTimeout(5000);
}

// ---------------------------------------------------------------------------
// Page parameters
// ---------------------------------------------------------------------------

function default_params(): array
{
    return [
        'castle_pk' => env('castle_pk', ''),
        'location' => env('location'),
        'demo_list' => demo_list(),
        'username' => env('valid_username'),
        'invalid_password' => env('invalid_password'),
        'valid_password' => env('valid_password'),
        'valid_username' => env('valid_username'),
        'valid_name' => env('valid_name'),
        'valid_user_id' => env('valid_user_id'),
        'webhook_url' => env('webhook_url'),
    ];
}

function flow_cfg(): array
{
    return [
        'valid_username' => env('valid_username'),
        'valid_user_id' => env('valid_user_id'),
        'valid_password' => env('valid_password'),
        'registered_at' => REGISTERED_AT,
    ];
}

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function set_page_scripts(string $html): void
{
    $GLOBALS['castle_page_scripts'] = $html;
}

function page_scripts(): string
{
    return $GLOBALS['castle_page_scripts'] ?? '';
}

function render_view(string $view, array $params = []): string
{
    extract($params, EXTR_SKIP);
    ob_start();
    include dirname(__DIR__) . '/views/' . $view . '.php';
    return ob_get_clean();
}

function render_page(string $view, array $params = []): string
{
    set_page_scripts('');
    $params['content'] = render_view($view, $params);
    $params['page_scripts'] = page_scripts();
    return render_view('layout', $params);
}

// Shared card scaffold for a single workflow page (mirrors demo.html).
function render_demo_card(array $params): string
{
    $friendly = h($params['friendly_name'] ?? '');
    $ui = $params['ui'] ?? '';
    $desc = $params['desc'] ?? '';
    $wsd = $params['wsd'] ?? null;
    $castlePk = $params['castle_pk'] ?? '';
    $validUsername = h($params['valid_username'] ?? '');
    $validPassword = h($params['valid_password'] ?? '');

    $wsdHtml = '';
    if ($wsd) {
        $wsdHtml = '<hr class="my-5 border-border">'
            . '<a href="' . h($wsd) . '" target="_blank" rel="noopener">View the web sequence diagram &rarr;</a>';
    }

    $pkDisplay = $castlePk !== '' ? h($castlePk) : '&mdash;';

    return <<<HTML
<div class="grid gap-6 items-start md:grid-cols-[1.3fr_1fr]">
    <div>
        <div class="card">
            <div class="eyebrow">workflow</div>
            <h2 class="text-[1.15rem]">{$friendly}</h2>
            <div id="ui">{$ui}</div>
            <div id="desc" class="text-muted" style="margin-top:1.2rem;">{$desc}</div>
            {$wsdHtml}
        </div>
        <div id="results-card" class="card hidden mt-6">
            <div class="eyebrow">result</div>
            <div id="results"></div>
        </div>
    </div>
    <aside>
        <div class="card">
            <div class="eyebrow">session</div>
            <ul class="meta-list">
                <li><span class="k">publishable key</span><span class="v">{$pkDisplay}</span></li>
                <li><span class="k">valid username</span><span class="v">{$validUsername}</span></li>
                <li><span class="k">valid password</span><span class="v">{$validPassword}</span></li>
            </ul>
        </div>
    </aside>
</div>
HTML;
}

// ---------------------------------------------------------------------------
// HTTP helpers
// ---------------------------------------------------------------------------

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function send_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
}

function project_root(): string
{
    return dirname(__DIR__);
}

// Serve the Castle browser SDK straight from the npm install (node_modules)
// instead of vendoring it into the repo.
function serve_castle_js(string $filename): void
{
    $dir = project_root() . '/node_modules/@castleio/castle-js/dist';
    $path = realpath($dir . '/' . $filename);

    if ($path === false || strpos($path, realpath($dir) ?: $dir) !== 0 || !is_file($path)) {
        http_response_code(404);
        echo 'Not found';
        return;
    }

    header('Content-Type: application/javascript');
    readfile($path);
}

// ---------------------------------------------------------------------------
// Event execution (calls the Castle SDK)
// ---------------------------------------------------------------------------

// Execute a decided event flow. Returns the Castle verdict for risk/filter,
// null for the non-blocking log endpoint, or an {error} array on failure.
function run_event_flow(array $decision)
{
    $endpoint = $decision['api_endpoint'];
    $payload = $decision['payload'];

    try {
        if ($endpoint === 'risk') {
            return Castle::risk($payload)->getAttributes();
        }
        if ($endpoint === 'filter') {
            return Castle::filter($payload)->getAttributes();
        }
        Castle::log($payload);
        return null;
    } catch (Castle_Error $error) {
        return ['error' => $error->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Webhook store (file-backed so it survives across requests)
// ---------------------------------------------------------------------------

function webhook_store_path(): string
{
    return sys_get_temp_dir() . '/castle_php_example_webhooks.json';
}

function webhooks_all(): array
{
    $path = webhook_store_path();
    if (!is_file($path)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function webhooks_add(array $body): void
{
    $webhooks = webhooks_all();
    array_unshift($webhooks, [
        'id' => count($webhooks) + 1,
        'received_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'body' => $body,
    ]);
    $webhooks = array_slice($webhooks, 0, 50);
    file_put_contents(webhook_store_path(), json_encode($webhooks));
}

function webhooks_clear(): void
{
    $path = webhook_store_path();
    if (is_file($path)) {
        unlink($path);
    }
}

// Verify an incoming Castle webhook and store it. Returns the HTTP status code
// the receiver should respond with (204 when stored, 404 when verification
// fails — so the endpoint isn't revealed to unauthenticated callers).
function process_incoming_webhook(string $rawBody, ?string $signature): int
{
    try {
        Castle_Webhook::verify($rawBody, $signature);
    } catch (Castle_WebhookVerificationError $error) {
        return 404;
    }

    webhooks_add((array) json_decode($rawBody, true));
    return 204;
}
