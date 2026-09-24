<?php $page = 'farmers'; $pageTitle = page_title('Local farmers'); ?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><span>Farmers</span></div>
        <span class="eyebrow">The people behind your produce</span>
        <h1>Meet the growers in your neighborhood.</h1>
        <p>Follow their stalls, see what they have available this week, and save the farms you want to visit again.</p>
    </div>
</section>
<section class="section-sm" style="padding-top:24px">
    <div class="container">
        <form class="card card-pad" method="get" action="<?= e(url('farmers')) ?>" style="display:flex;align-items:end;gap:12px;flex-wrap:wrap;margin-bottom:28px">
            <div style="flex:1;min-width:220px"><label for="farmer_search">Search farmers</label><input id="farmer_search" name="q" type="search" value="<?= e($search) ?>" placeholder="Farm or stall name"></div>
            <div style="min-width:210px"><label for="farmer_market">At market</label><select id="farmer_market" name="market"><option value="">All markets</option><?php foreach ($markets as $market): ?><option value="<?= (int)$market['id'] ?>" <?= $marketId === (int)$market['id'] ? 'selected' : '' ?>><?= e($market['name']) ?></option><?php endforeach; ?></select></div>
            <button class="btn" type="submit"><?= icon('search') ?> Find farmers</button>
            <?php if ($search !== '' || $marketId): ?><a class="btn btn-light" href="<?= e(url('farmers')) ?>">Clear</a><?php endif; ?>
        </form>
        <?php if ($farmers): ?>
            <div class="market-grid">
                <?php foreach ($farmers as $farmer): ?>
                    <a class="card card-hover card-pad" href="<?= e(url('farmer', ['id' => $farmer['id']])) ?>">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px"><span class="farmer-avatar" style="width:64px;height:64px;font-size:1.25rem;border-radius:19px"><?= e(initials($farmer['stall_name'])) ?></span><?php if (current_user() && current_user()['role'] === 'customer'): ?><span class="pill pill-lime"><?= is_favorite('farmer',(int)$farmer['id']) ? '♥ Saved' : 'Local' ?></span><?php else: ?><span class="pill pill-lime">Approved</span><?php endif; ?></div>
                        <h2 style="margin:18px 0 4px;color:var(--green-950);font-size:1.25rem;letter-spacing:-.03em"><?= e($farmer['stall_name']) ?></h2>
                        <p class="text-muted" style="font-size:.85rem;min-height:48px"><?= e($farmer['description']) ?></p>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px"><?= star_display((float)$farmer['average_rating']) ?><span class="pill"><?= (int)$farmer['product_count'] ?> products</span></div>
                        <p class="text-muted mt-2" style="font-size:.75rem;margin-bottom:0"><?= icon('location') ?> <?= e($farmer['market_names'] ?: 'Location coming soon') ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card empty-state"><span class="empty-state-icon">🌾</span><h3>No farmers match those filters</h3><p>Try another market or a broader farm search.</p><a class="btn" href="<?= e(url('farmers')) ?>">Show all farmers</a></div>
        <?php endif; ?>
    </div>
</section>
