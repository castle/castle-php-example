<?php
$validUser = h($valid_username ?? '');
$validPw = h($valid_password ?? '');

$ui = <<<HTML
<div class="btn-row" style="margin-top:0;">
    <button class="btn btn-ghost" onclick="fillForm('new')">new user</button>
    <button class="btn btn-ghost" onclick="fillForm('existing')">existing email</button>
</div>
<div style="margin-top:1.2rem;">
    <div class="field">
        <label for="name">name</label>
        <input class="input" type="text" id="name" autocomplete="off">
    </div>
    <div class="field">
        <label for="email">email</label>
        <input class="input" type="text" id="email" autocomplete="off">
    </div>
    <div class="field">
        <label for="password">password</label>
        <input class="input" type="password" id="password">
    </div>
    <div class="btn-row">
        <button class="btn btn-primary" onclick="signup()">Create account</button>
    </div>
    <div class="form-links">
        <a href="/login">Already have an account? Log in</a>
    </div>
</div>
HTML;

$desc = <<<HTML
<p>A registration is evaluated before the account exists, so it is anonymous activity sent to <code>/filter</code> with the form <code>params</code>:</p>
<ol class="list-decimal pl-5 space-y-1">
    <li><strong>a new email</strong> &rarr; <code>\$registration / \$attempted</code>; act on the verdict (allow, challenge, deny) before creating the account.</li>
    <li><strong>an email that already exists</strong> &rarr; <code>\$registration / \$failed</code> (resolved to the existing user via <code>matching_user_id</code>).</li>
</ol>
HTML;

$scripts = <<<HTML
<script>
    const VALID_USER = "{$validUser}";
    const VALID_PW = "{$validPw}";

    function fillForm(state) {
        const name = document.getElementById("name");
        const email = document.getElementById("email");
        const password = document.getElementById("password");
        if (state === "existing") {
            name.value = "Clark Kent";
            email.value = VALID_USER;
        } else {
            name.value = "Lois Lane";
            email.value = "lois.lane@dailyplanet.com";
        }
        password.value = VALID_PW;
    }

    function signup() {
        withRequestToken(function (requestToken) {
            postJSON("/evaluate_signup", {
                name: document.getElementById("name").value,
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
    'friendly_name' => $friendly_name ?? 'sign up',
    'ui' => $ui,
    'desc' => $desc,
    'castle_pk' => $castle_pk ?? '',
    'valid_username' => $valid_username ?? '',
    'valid_password' => $valid_password ?? '',
]);
