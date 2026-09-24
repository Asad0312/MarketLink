<?php $page = 'markets'; $pageTitle = page_title('Markets and map'); ?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><span>Markets</span></div>
        <span class="eyebrow">Find your pickup point</span>
        <h1>Know where the market is before you go.</h1>
        <p>Browse local markets, check their weekly days, see participating growers, and get directions without needing a paid map key.</p>
    </div>
</section>
<section class="section-sm" style="padding-top:24px">
    <div class="container">
        <form class="card card-pad" method="get" action="<?= e(url('markets')) ?>" style="display:flex;align-items:end;gap:12px;flex-wrap:wrap;margin-bottom:28px">
            <div style="flex:1;min-width:220px"><label for="market_search">Search markets</label><input id="market_search" name="q" type="search" value="<?= e($search) ?>" placeholder="Market name or area"></div>
            <div style="min-width:190px"><label for="market_day">Open on</label><select id="market_day" name="day"><option value="">Any day</option><?php foreach (['1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday','4'=>'Thursday','5'=>'Friday','6'=>'Saturday','0'=>'Sunday'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= $day === (string)$value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <button class="btn" type="submit"><?= icon('search') ?> Find markets</button>
            <?php if ($search !== '' || $day !== ''): ?><a class="btn btn-light" href="<?= e(url('markets')) ?>">Clear</a><?php endif; ?>
        </form>
        <?php if ($markets): ?>
            <div class="market-grid">
                <?php foreach ($markets as $market): ?>
                    <article class="card card-hover market-card-grid">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px"><span class="pill pill-lime"><?= e($market['day_label']) ?></span><span class="pill pill-gray"><?= (int) $market['farmer_count'] ?> farmers</span></div>
                        <h3><?= e($market['name']) ?></h3>
                        <p><?= e($market['address']) ?></p>
                        <div class="spec-list" style="grid-template-columns:1fr 1fr;margin:16px 0">
                            <li><strong><?= e(date('g:i A', strtotime($market['open_time']))) ?> – <?= e(date('g:i A', strtotime($market['close_time']))) ?></strong><span>Market hours</span></li>
                            <li><strong><?= (int) $market['product_count'] ?> listings</strong><span>Current produce</span></li>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <a class="btn btn-sm" href="<?= e(url('market', ['id' => $market['id']])) ?>">View market <?= icon('arrow-right') ?></a>
                            <a class="btn btn-sm btn-light" href="https://www.openstreetmap.org/directions?to=<?= e($market['latitude']) ?>,<?= e($market['longitude']) ?>" target="_blank" rel="noopener">Directions ↗</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card empty-state"><span class="empty-state-icon">🗺️</span><h3>No markets match that search</h3><p>Try another area or choose a different market day.</p><a class="btn" href="<?= e(url('markets')) ?>">Show all markets</a></div>
        <?php endif; ?>
    </div>
</section>
