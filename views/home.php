<?php
$page = 'home';
$pageTitle = page_title();
$featuredProducts = $featuredProducts ?? [];
$featuredMarkets = $featuredMarkets ?? [];
$featuredFarmers = $featuredFarmers ?? [];
$stats = $stats ?? ['products' => 0, 'farmers' => 0, 'markets' => 0];
$announcement = $announcement ?? null;
?>
<?php if ($announcement): ?>
    <div class="container mt-3">
        <div class="alert alert-info mb-0"><?= icon('bell') ?><span><strong>Market update:</strong> <?= e($announcement['message']) ?></span></div>
    </div>
<?php endif; ?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><span class="live-dot"></span> Fresh this week</span>
            <h1>Good food starts <span class="highlight">closer to home.</span></h1>
            <p class="hero-lead">Meet local growers, see what is really available, and reserve your weekly basket for an easy market-day pickup.</p>
            <div class="hero-actions">
                <a class="btn btn-lg" href="<?= e(url('explore')) ?>">Explore this week <?= icon('arrow-right') ?></a>
                <a class="btn btn-lg btn-light" href="<?= e(url('register', ['role' => 'farmer'])) ?>">I’m a farmer</a>
            </div>
            <div class="trust-row">
                <span><?= icon('check') ?> No delivery fee</span>
                <span><?= icon('check') ?> Pay at pickup</span>
                <span><?= icon('check') ?> Verified by admin</span>
            </div>
        </div>

        <div class="hero-visual" aria-label="Example MarketLink produce basket">
            <div class="hero-orbit"></div>
            <div class="floating-chip one"><?= icon('calendar') ?><span><strong>Saturday pickup</strong><br>8:00 AM – 1:00 PM</span></div>
            <div class="floating-chip two"><?= icon('leaf') ?><span><strong>100% local</strong><br>Grown nearby</span></div>
            <div class="floating-chip three"><?= icon('shield') ?><span><strong>Admin approved</strong><br>Community trusted</span></div>

            <div class="market-card">
                <div class="market-card-top">
                    <div style="display:flex;align-items:center;gap:11px">
                        <div class="market-logo">🌿</div>
                        <div><h3>This week’s basket</h3><small>3 items from nearby growers</small></div>
                    </div>
                    <span class="pill pill-lime">Fresh</span>
                </div>
                <div class="market-card-items">
                    <?php foreach (array_slice($featuredProducts, 0, 3) as $heroProduct): ?>
                        <div class="mini-product">
                            <span class="mini-product-art"><?= e(product_placeholder($heroProduct['category_name'] ?? '')) ?></span>
                            <div><strong><?= e($heroProduct['name']) ?></strong><span><?= e($heroProduct['farmer_name']) ?> · <?= money($heroProduct['price']) ?></span></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$featuredProducts): ?>
                        <div class="mini-product"><span class="mini-product-art">🧺</span><div><strong>Fresh listings are coming</strong><span>Check back after market setup.</span></div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="feature-grid">
            <article class="card feature-card reveal"><span class="feature-icon"><?= icon('search') ?></span><h3>Know what is available</h3><p>Browse current weekly stock, real prices, and pickup details before you leave home.</p></article>
            <article class="card feature-card reveal reveal-delay-1"><span class="feature-icon"><?= icon('calendar') ?></span><h3>Reserve in a few taps</h3><p>Choose a valid pickup window, place a pre-order, and follow every status update.</p></article>
            <article class="card feature-card reveal reveal-delay-2"><span class="feature-icon"><?= icon('heart') ?></span><h3>Build lasting favorites</h3><p>Keep trusted farmers and seasonal favorites ready for the next market morning.</p></article>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Fresh this week</span><h2>From nearby fields to your basket.</h2></div>
            <a class="btn btn-light" href="<?= e(url('explore')) ?>">See all produce <?= icon('arrow-right') ?></a>
        </div>
        <div class="product-grid">
            <?php foreach (array_slice($featuredProducts, 0, 8) as $product): ?>
                <?php require BASE_PATH . '/views/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php if (!$featuredProducts): ?>
            <div class="card empty-state"><span class="empty-state-icon">🌱</span><h3>No produce listed yet</h3><p>An approved farmer can add the first weekly listing.</p></div>
        <?php endif; ?>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Explore by category</span><h2>Everything for a good market basket.</h2></div>
            <p>Fresh produce, pantry staples, and small-batch goods from approved local sellers.</p>
        </div>
        <div class="category-grid">
            <?php foreach (($categories ?? []) as $category): ?>
                <a class="card card-hover category-card" href="<?= e(url('explore', ['category' => $category['id']])) ?>">
                    <span class="pill pill-lime"><?= (int) $category['product_count'] ?> items</span>
                    <h3><?= e($category['name']) ?></h3>
                    <p><?= e($category['description'] ?: 'Fresh local selection') ?></p>
                    <span class="category-emoji"><?= e(product_placeholder($category['name'])) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (!$categories): ?>
                <a class="card category-card" href="<?= e(url('explore')) ?>"><span class="pill pill-lime">Fresh</span><h3>Vegetables</h3><p>Leafy greens and seasonal picks</p><span class="category-emoji">🥬</span></a>
                <a class="card category-card" href="<?= e(url('explore')) ?>"><span class="pill pill-lime">Fresh</span><h3>Fruits</h3><p>Orchard fruit and sweet favorites</p><span class="category-emoji">🍎</span></a>
                <a class="card category-card" href="<?= e(url('explore')) ?>"><span class="pill pill-lime">Fresh</span><h3>Dairy</h3><p>Milk, yogurt, and farm fresh</p><span class="category-emoji">🥛</span></a>
                <a class="card category-card" href="<?= e(url('explore')) ?>"><span class="pill pill-lime">Fresh</span><h3>Bakery</h3><p>Small-batch bread and treats</p><span class="category-emoji">🥖</span></a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Market days nearby</span><h2>Find your regular pickup point.</h2></div>
            <a class="btn btn-light" href="<?= e(url('markets')) ?>">Open market map <?= icon('arrow-right') ?></a>
        </div>
        <div class="market-grid">
            <?php foreach (array_slice($featuredMarkets, 0, 3) as $market): ?>
                <a class="card card-hover market-card-grid" href="<?= e(url('market', ['id' => $market['id']])) ?>">
                    <span class="pill pill-lime"><?= e($market['day_label'] ?? 'Weekly market') ?></span>
                    <h3><?= e($market['name']) ?></h3>
                    <p><?= e($market['address']) ?></p>
                    <div class="days-row"><?= e($market['time_label'] ?? 'Open weekly') ?></div>
                    <span class="text-green" style="font-size:.82rem;font-weight:750">View market details →</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head text-center" style="display:block;max-width:720px;margin-inline:auto">
            <span class="eyebrow">Meet the growers</span>
            <h2>Real people behind every basket.</h2>
            <p>See who grows your food, when their stall is open, and what is coming into stock this week.</p>
        </div>
        <div class="review-grid">
            <?php foreach (array_slice($featuredFarmers, 0, 3) as $farmer): ?>
                <a class="card card-hover card-pad" href="<?= e(url('farmer', ['id' => $farmer['id']])) ?>">
                    <div style="display:flex;align-items:center;gap:12px">
                        <span class="farmer-avatar" style="width:54px;height:54px;font-size:1.2rem"><?= e(initials($farmer['stall_name'])) ?></span>
                        <div><strong style="display:block;color:var(--green-950)"><?= e($farmer['stall_name']) ?></strong><small class="text-muted"><?= e($farmer['market_name'] ?? 'Local market grower') ?></small></div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:17px">
                        <?= star_display((float) $farmer['average_rating']) ?>
                        <span class="pill"><?= (int) $farmer['product_count'] ?> products</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="narrow">
        <div class="card card-pad" style="text-align:center;background:linear-gradient(145deg,var(--green-100),white)">
            <div class="stat-grid">
                <div><span class="stat-value"><?= (int) $stats['products'] ?></span><span class="text-muted">fresh listings</span></div>
                <div><span class="stat-value"><?= (int) $stats['farmers'] ?></span><span class="text-muted">local growers</span></div>
                <div><span class="stat-value"><?= (int) $stats['markets'] ?></span><span class="text-muted">pickup markets</span></div>
                <div><span class="stat-value">0</span><span class="text-muted">delivery fees</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="cta-panel reveal">
            <div><h2>Your market morning can start here.</h2><p>Join neighbors supporting nearby growers and make your next pickup simple.</p></div>
            <a class="btn btn-lime btn-lg" href="<?= e(url('register')) ?>">Create free account <?= icon('arrow-right') ?></a>
        </div>
    </div>
</section>
