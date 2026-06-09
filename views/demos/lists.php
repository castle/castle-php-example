<?php
$ui = <<<HTML
<div class="field">
    <label for="name">name</label>
    <input class="input" type="text" id="name" value="demo-blocklist">
</div>
<div class="field">
    <label for="color">color</label>
    <input class="input" type="text" id="color" value="\$red">
</div>
<div class="field">
    <label for="primary_field">primary field</label>
    <input class="input" type="text" id="primary_field" value="user.email">
</div>
<div class="btn-row">
    <button class="btn btn-primary" onclick="createList()">Create list</button>
</div>
HTML;

$desc = <<<HTML
<p>The <strong>Lists</strong> API lets you manage allow/block lists programmatically. This demo calls <code>createList</code> and then <code>getAllLists</code>.</p>
<p>A valid Castle API secret is required for this call to succeed.</p>
HTML;

$scripts = <<<HTML
<script>
    function createList() {
        postJSON("/create_list", {
            name: document.getElementById("name").value,
            color: document.getElementById("color").value,
            primary_field: document.getElementById("primary_field").value,
        }).then(renderCastleResponse);
    }
</script>
HTML;

set_page_scripts($scripts);
echo render_demo_card([
    'friendly_name' => $friendly_name ?? 'lists',
    'ui' => $ui,
    'desc' => $desc,
    'castle_pk' => $castle_pk ?? '',
    'valid_username' => $valid_username ?? '',
    'valid_password' => $valid_password ?? '',
]);
