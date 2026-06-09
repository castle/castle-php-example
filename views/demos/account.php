<?php
$validUser = h($valid_username ?? '');
$validName = h($valid_name ?? '');
$validUserId = h($valid_user_id ?? '');

$ui = <<<HTML
<p class="text-muted" style="margin-top:0;">Signed in as <code>{$validUser}</code>. These actions run once a user is authenticated.</p>
<div style="margin-top:1.2rem;">
    <div class="field">
        <label for="name">name</label>
        <input class="input" type="text" id="name" value="{$validName}">
    </div>
    <div class="field">
        <label for="email">email</label>
        <input class="input" type="text" id="email" value="{$validUser}">
    </div>
    <div class="btn-row">
        <button class="btn btn-primary" onclick="updateProfile()">Save changes</button>
        <button class="btn btn-ghost" onclick="sendCustomEvent()">Send a custom event</button>
        <button class="btn" onclick="logout()">Log out</button>
    </div>
</div>
HTML;

$desc = <<<HTML
<p>The post-login actions each mint a fresh request token from <code>castle.js</code>:</p>
<ol class="list-decimal pl-5 space-y-1">
    <li><strong>profile update</strong> &rarr; <code>\$profile_update</code> sent to <code>/risk</code>.</li>
    <li><strong>custom event</strong> &rarr; <code>Castle.custom()</code> in the browser.</li>
    <li><strong>logout</strong> &rarr; <code>\$logout</code> via the non-blocking <code>/log</code> endpoint.</li>
</ol>
HTML;

$scripts = <<<HTML
<script>
    const VALID_USER = "{$validUser}";
    const VALID_USER_ID = "{$validUserId}";

    function updateProfile() {
        withRequestToken(function (requestToken) {
            postJSON("/evaluate_profile_update", {
                name: document.getElementById("name").value,
                email: document.getElementById("email").value,
                request_token: requestToken,
            }).then(renderCastleResponse);
        });
    }

    function sendCustomEvent() {
        if (window.Castle && typeof Castle.custom === "function") {
            try {
                Castle.custom({
                    name: "\$custom",
                    user: { id: VALID_USER_ID || VALID_USER, email: VALID_USER },
                    properties: { source: "account-page" },
                });
            } catch (e) {
                console.error("Castle.custom failed", e);
            }
        }
        clearResults();
        addJSONBlock("Custom event sent (browser)", { name: "\$custom", user: { email: VALID_USER } });
        showResultsCard();
    }

    function logout() {
        withRequestToken(function (requestToken) {
            postJSON("/evaluate_logout", {
                request_token: requestToken,
            }).then(renderCastleResponse);
        });
    }
</script>
HTML;

set_page_scripts($scripts);
echo render_demo_card([
    'friendly_name' => $friendly_name ?? 'account',
    'ui' => $ui,
    'desc' => $desc,
    'castle_pk' => $castle_pk ?? '',
    'valid_username' => $valid_username ?? '',
    'valid_password' => $valid_password ?? '',
]);
