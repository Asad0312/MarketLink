<?php
$page = 'markets';
$pageTitle = page_title($market['name']);
$lat = (float) $market['latitude'];
$lng = (float) $market['longitude'];
$bbox = sprintf('%.4f,%.4f,%.4f,%.4f', $lng - 0.018, $lat - 0.012, $lng + 0.018, $lat + 0.012);
?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><a href="<?= e(url('markets')) ?>">Markets</a><span>/</span><span><?= e($market['name']) ?></span></div>
        <span class="eyebrow">Community pickup point</span>
        <h1><?= e($market['name']) ?></h1>
        <p><?= e($market['description']) ?></p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:20px">
            <span class="pill pill-lime"><?= e(public_day_label($market['days'][0] ?? 0)) ?><?= count($market['days']) > 1 ? ' · '.e(public_day_label($market['days'][1] ?? 0)) : '' ?></span>
            <span class="pill"><?= e(date('g:i A', strtotime($market['open_time']))) ?> – <?= e(date('g:i A', strtotime($market['close_time']))) ?></span>
            <span class="pill pill-blue"><?= count($marketFarmers) ?> local farmers</span>
        </div>
    </div>
</section>
<section class="section-sm" style="padding-top:20px">
    <div class="container">
        <div class="detail-grid">
            <div class="card map-card reveal"><iframe title="Map showing <?= e($market['name']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.openstreetmap.org/export/embed.html?bbox=<?= e($bbox) ?>&amp;layer=mapnik&amp;marker=<?= e($lat) ?>,<?= e($lng) ?>"></iframe></div>
            <div class="card card-pad reveal reveal-delay-1">
                <span class="eyebrow">Pickup details</span>
                <h2 style="margin:0 0 8px;color:var(--green-950);font-size:1.65rem">Plan your visit</h2>
                <p class="text-muted"><?= e($market['address']) ?></p>
                <div class="spec-list">
                    <li><strong>Open</strong><span><?= e(date('g:i A', strtotime($market['open_time']))) ?> – <?= e(date('g:i A', strtotime($market['close_time']))) ?></span></li>
                    <li><strong>Payment</strong><span>Settled with each farmer at pickup</span></li>
                </div>
                <div style="display:flex;gap:9px;flex-wrap:wrap">
                    <a class="btn" href="https://www.openstreetmap.org/directions?to=<?= e($lat) ?>,<?= e($lng) ?>" target="_blank" rel="noopener">Get directions ↗</a>
                    <?php if (current_user() && current_user()['role'] === 'customer'): ?><form method="post" action="<?= e(url('favorite-toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="type" value="market"><input type="hidden" name="id" value="<?= (int)$market['id'] ?>"><button class="btn btn-light" type="submit"><?= icon('heart') ?> <?= $market['is_favorite'] ? 'Saved' : 'Save market' ?></button></form><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php if ($marketFarmers): ?>
<section class="section-sm">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">At this market</span><h2>Meet the participating farmers.</h2></div><a class="btn btn-light btn-sm" href="<?= e(url('farmers', ['market' => $market['id']])) ?>">See all farmers</a></div>
        <div class="review-grid">
            <?php foreach ($marketFarmers as $farmer): ?>
                <a class="card card-hover card-pad" href="<?= e(url('farmer', ['id' => $farmer['id']])) ?>">
                    <div style="display:flex;align-items:center;gap:12px"><span class="farmer-avatar" style="width:52px;height:52px"><?= e(initials($farmer['stall_name'])) ?></span><div><strong style="display:block;color:var(--green-950)"><?= e($farmer['stall_name']) ?></strong><small class="text-muted"><?= e($farmer['stall_label']) ?></small></div></div>
                    <p class="text-muted mt-2" style="font-size:.82rem"><?= e($farmer['description']) ?></p>
                    <div style="display:flex;justify-content:space-between;align-items:center"><?= star_display((float)$farmer['average_rating']) ?><span class="pill"><?= (int)$farmer['product_count'] ?> products</span></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php if ($marketProducts): ?>
<section class="section-sm">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Available here</span><h2>Browse this market’s fresh picks.</h2></div><a class="btn btn-light btn-sm" href="<?= e(url('explore', ['market' => $market['id']])) ?>">Filter marketplace</a></div>
        <div class="product-grid"><?php foreach ($marketProducts as $product): ?><?php require BASE_PATH . '/views/partials/product-card.php'; ?><?php endforeach; ?></div>
    </div>
</section>
<?php endif; ?>
