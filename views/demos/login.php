<?php
$validUser = h($valid_username ?? '');
$validPw = h($valid_password ?? '');
$badPw = h($invalid_password ?? '');

$ui = <<<HTML
<div class="btn-row" style="margin-top:0;">
    <button class="btn btn-ghost" onclick="fillForm('valid')">valid user + pw</button>
    <button class="btn btn-ghost" onclick="fillForm('bad_pw')">valid user, bad pw</button>
    <button class="btn btn-ghost" onclick="fillForm('bad_user')">invalid username</button>
</div>
<div style="margin-top:1.2rem;">
    <div class="field">
        <label for="email">email</label>
        <input class="input" type="text" id="email" autocomplete="off">
    </div>
    <div class="field">
        <label for="password">password</label>
        <input class="input" type="password" id="password">
    </div>
    <div class="btn-row">
        <button class="btn btn-primary" onclick="login()">Log in</button>
    </div>
    <div class="form-links">
        <a href="/password_reset">Forgot your password?</a>
        <a href="/signup">Create an account</a>
    </div>
</div>
HTML;

$desc = <<<HTML
<p>A login attempt has three common outcomes, and each maps to a different Castle endpoint:</p>
<ol class="list-decimal pl-5 space-y-1">
    <li><strong>valid username + valid password</strong> &rarr; <code>\$login / \$succeeded</code> sent to <code>/risk</code>; act on the verdict (allow, challenge, deny).</li>
    <li><strong>valid username + invalid password</strong> &rarr; <code>\$login / \$failed</code> sent to <code>/filter</code>.</li>
    <li><strong>invalid username</strong> &rarr; <code>\$login / \$failed</code> (user id = null) sent to <code>/filter</code>.</li>
</ol>
HTML;

$scripts = <<<HTML
<script>
    const VALID_USER = "{$validUser}";
    const VALID_PW = "{$validPw}";
    const BAD_PW = "{$badPw}";

    function fillForm(state) {
        const email = document.getElementById("email");
        const password = document.getElementById("password");
        if (state === "valid") {
            email.value = VALID_USER;
            password.value = VALID_PW;
        } else if (state === "bad_pw") {
            email.value = VALID_USER;
            password.value = BAD_PW;
        } else {
            email.value = "invalid_user@abc.com";
            password.value = BAD_PW;
        }
    }

    function login() {
        withRequestToken(function (requestToken) {
            postJSON("/evaluate_login", {
                email: document.getElementById("email").value,
                password: document.getElementById("password").value,
                request_token: requestToken,
            }).then(function (data) {
                renderCastleResponse(data);
                const action = data.result && data.result.policy && data.result.policy.action;
                if (action === "allow") {
                    const results = document.getElementById("results");
                    const wrap = document.createElement("div");
                    wrap.className = "result-block";
                    wrap.innerHTML =
                        '<a class="btn btn-primary" href="/account">Continue to your account &rarr;</a>';
                    results.appendChild(wrap);
                }
            });
        });
    }
</script>
HTML;

set_page_scripts($scripts);
echo render_demo_card([
    'friendly_name' => $friendly_name ?? 'login',
    'ui' => $ui,
    'desc' => $desc,
    'wsd' => $wsd ?? null,
    'castle_pk' => $castle_pk ?? '',
    'valid_username' => $valid_username ?? '',
    'valid_password' => $valid_password ?? '',
]);
