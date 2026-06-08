<?php
/** @var array $demo_list */
?>
<section class="hero">
	<span class="tag">castle · php</span>
	<h1 class="text-[2.2rem] mt-3">Castle workflows demo</h1>
	<p class="text-[1.15rem] text-muted">A small PHP app showing how to integrate the Castle PHP SDK.</p>
</section>

<div class="grid gap-4 mt-8" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr));">
	<?php foreach (($demo_list ?? []) as $demo): ?>
	<a class="feature" href="/<?= h($demo['url']) ?>">
		<h3><?= h($demo['friendly_name']) ?></h3>
		<p><?= h($demo['blurb']) ?></p>
	</a>
	<?php endforeach; ?>
</div>
