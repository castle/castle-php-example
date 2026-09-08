<?php
/** @var string $content */
/** @var array $demo_list */
$castlePk = $castle_pk ?? '';
$pageTitle = $page_title ?? ($location ?? 'localhost');
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Castle workflows · <?= h($pageTitle) ?></title>

	<link rel="icon" type="image/x-icon" href="https://castle.io/favicon-32x32.png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link href="/static/styles.css" rel="stylesheet">

	<script>
		if (!window.Castle) {
			window.exports = window.exports || {};
			window.module = window.module || { exports: window.exports };
			window.Castle = window.module.exports;
		}
	</script>
	<script src="/vendor/castle-js/castle.umd.js"></script>
	<script>
		window.Castle = window.Castle || (window.module && window.module.exports) || window["@castleio/castle-js"];
		if (window.Castle && "<?= h($castlePk) ?>") {
			window.__castle = Castle.configure({ pk: "<?= h($castlePk) ?>" }) || window.Castle;
		}
	</script>
	<script src="/static/app.js" defer></script>
</head>

<body>

	<nav class="navbar">
		<a class="brand" href="/"><svg class="brand-logo" viewBox="0 0 158 158" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M79 158c43.63 0 79-35.37 79-79S122.63 0 79 0 0 35.37 0 79s35.37 79 79 79ZM31 57h24v12h12V57h24v12h12V57h24v24c-6.627 0-12 5.373-12 12v12H43V93c0-6.627-5.373-12-12-12V57Z"/></svg> Castle <span class="text-muted" style="font-weight:400">demo</span></a>
		<div class="nav-links">
			<?php foreach (($demo_list ?? []) as $demo): ?>
			<a href="/<?= h($demo['url']) ?>"><?= h($demo['friendly_name']) ?></a>
			<?php endforeach; ?>
			<a href="https://github.com/castle/castle-php-example" target="_blank" rel="noopener">GitHub</a>
			<a href="https://docs.castle.io" target="_blank" rel="noopener">Docs</a>
		</div>
	</nav>

	<main class="container-page">
		<?= $content ?>
	</main>

	<?= $page_scripts ?? '' ?>

</body>

</html>
