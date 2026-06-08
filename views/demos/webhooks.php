<?php
$endpoint = h($webhook_endpoint ?? '');
$received = $webhooks_received ?? [];

$items = '';
if (!empty($received)) {
    foreach ($received as $wh) {
        $id = h($wh['id'] ?? '');
        $receivedAt = h($wh['received_at'] ?? '');
        $body = h(json_encode($wh['body'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $items .= <<<HTML
<div class="result-block mt-4">
    <div class="label">#{$id} · {$receivedAt}</div>
    <pre class="whitespace-pre-wrap text-[0.8rem]">{$body}</pre>
</div>
HTML;
    }
} else {
    $items = '<p class="text-muted mt-4">No webhooks received yet.</p>';
}

$ui = <<<HTML
<p>This page lists the most recent webhooks Castle has delivered to this app. Each one is signature-verified before it is stored.</p>
<div class="field">
    <label>receiver endpoint</label>
    <input class="input" type="text" value="{$endpoint}" readonly onclick="this.select()">
</div>
<div class="btn-row">
    <a class="btn" href="/webhooks">Refresh</a>
</div>
{$items}
HTML;

$desc = <<<HTML
<p>Point a webhook at <code>{$endpoint}</code> from the Castle dashboard (Settings &rarr; Webhooks). Incoming requests are verified with <code>Castle_Webhook::verify</code> against the <code>X-Castle-Signature</code> header; anything that fails verification gets a 404.</p>
<p>Because this demo runs on localhost, Castle needs a public tunnel (e.g. ngrok) to reach the receiver.</p>
HTML;

echo render_demo_card([
    'friendly_name' => $friendly_name ?? 'webhooks',
    'ui' => $ui,
    'desc' => $desc,
    'castle_pk' => $castle_pk ?? '',
    'valid_username' => $valid_username ?? '',
    'valid_password' => $valid_password ?? '',
]);
