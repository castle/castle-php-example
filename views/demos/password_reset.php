<?php
$username = h($username ?? ($valid_username ?? ''));

$ui = <<<HTML
<div class="field">
    <label>email</label>
    <input class="input" type="text" value="{$username}" disabled>
</div>
<div class="field">
    <label for="password">new password</label>
    <input class="input" type="password" id="password">
</div>
<div class="btn-row">
    <button class="btn btn-primary" onclick="resetPassword()">Submit</button>
</div>
HTML;

$desc = <<<HTML
<p>This demo records the password-reset event with the non-blocking <code>/log</code> endpoint, which stores the event without returning a verdict.</p>
<p>Assume the user already passed your reset challenge (e.g. an emailed OTP). Enter a value <em>different from</em> the valid password to send <code>\$password_reset / \$succeeded</code>, or the valid password to send <code>\$password_reset / \$failed</code>. (The password is not actually changed.)</p>
HTML;

$scripts = <<<HTML
<script>
    function resetPassword() {
        withRequestToken(function (requestToken) {
            postJSON("/evaluate_new_password", {
                password: document.getElementById("password").value,
                request_token: requestToken,
            }).then(function (data) {
                renderCastleResponse(data);
                document.getElementById("desc").innerHTML =
                    "<p>Logged <code>" + data.type + " / " + data.status +
                    "</code> via the <code>/log</code> endpoint.</p>";
            });
        });
    }
</script>
HTML;

set_page_scripts($scripts);
echo render_demo_card([
    'friendly_name' => $friendly_name ?? 'password reset',
    'ui' => $ui,
    'desc' => $desc,
    'castle_pk' => $castle_pk ?? '',
    'valid_username' => $valid_username ?? '',
    'valid_password' => $valid_password ?? '',
]);
