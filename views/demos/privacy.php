<?php
$validUser = h($valid_username ?? '');

$ui = <<<HTML
<div class="field">
    <label for="identifier">identifier</label>
    <input class="input" type="text" id="identifier" value="{$validUser}">
</div>
<div class="field">
    <label for="identifier_type">identifier type</label>
    <input class="input" type="text" id="identifier_type" value="\$email">
</div>
<div class="btn-row">
    <button class="btn btn-primary" onclick="privacy('request')">Request user data</button>
    <button class="btn" onclick="privacy('delete')">Delete user data</button>
</div>
HTML;

$desc = <<<HTML
<p>The <strong>Privacy</strong> API helps you honor GDPR/CCPA requests via <code>requestUserData</code> and <code>deleteUserData</code>.</p>
<p>A valid Castle API secret is required for these calls to succeed.</p>
HTML;

$scripts = <<<HTML
<script>
    function privacy(action) {
        postJSON("/privacy_user_data", {
            action: action,
            identifier: document.getElementById("identifier").value,
            identifier_type: document.getElementById("identifier_type").value,
        }).then(renderCastleResponse);
    }
</script>
HTML;

set_page_scripts($scripts);
echo render_demo_card([
    'friendly_name' => $friendly_name ?? 'privacy',
    'ui' => $ui,
    'desc' => $desc,
    'castle_pk' => $castle_pk ?? '',
    'valid_username' => $valid_username ?? '',
    'valid_password' => $valid_password ?? '',
]);
